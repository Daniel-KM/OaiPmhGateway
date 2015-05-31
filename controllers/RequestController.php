<?php
/**
 * The Request controller class.
 *
 * @todo Comply with all specs (checks of xml, auto freshness...).
 *
 * @package OaiPmhGateway
 * @uses OaiPmhGateway_ResponseGenerator
 */
class OaiPmhGateway_RequestController extends Omeka_Controller_AbstractActionController
{
    // Number of seconds to wait after a timeout.
    const RETRY_AFTER = '120';

    // Requested parameters.
    protected $_repository;

    // Cleaned url used to initiate or terminate a repository.
    protected $_url;

    // Resulting registered oai-pmh gateway, if any.
    protected $_gateway;

    /**
     * Initialize the controller.
     */
    public function init()
    {
        $this->_helper->db->setDefaultModelName('OaiPmhGateway');
    }

    /**
     * Forward to the 'query' action
     *
     * @see self::queryAction()
     */
    public function indexAction()
    {
        $this->_forward('query');
    }

    /**
     * Check request and return xml via the OAI-PMH Static Repository gateway.
     */
    public function queryAction()
    {
        // Check post.
        if (!$this->_checkPost()) {
            return $this->_error();
        }

        // Check if this is a public gateway and, if not, a localhost request.
        if (!$this->_gateway->isPublic()) {
            $ip = $this->getRequest()->getServer('REMOTE_ADDR');
            $whitelist = array( '127.0.0.1', '::1');
            if(!in_array($ip, $whitelist)){
                return $this->_error();
            }
        }

        if ($this->_gateway->isTerminated()) {
            return $this->_error(502, __('The static repository "%s" is registered by this OAI-PMH gateway, but not available.',
                $this->_gateway->getRepository()));
        }

        // TODO Manage internal overload here?
        $isGatewayOverload = false;

        // Check freshness and conformity of the repository.
        $isFresh = $this->_gateway->isFresh();
        if ($isFresh === false) {
            return $this->_error(502, __('The static repository "%s" is registered by this OAI-PMH gateway, but not available currently.',
                $this->_gateway->getRepository()));
        }
        // "503 (Service unavailable)" is used for an issue in this gateway.
        elseif ($isGatewayOverload) {
            $this->getResponse()
                ->setHeader('Retry-After', self::RETRY_AFTER);
            return $this->_error(503, null);
        }
        // "504 (Gateway Timeout)" is used for an issue in the original server.
        elseif (is_null($isFresh) || is_string($isFresh)) {
            $this->getResponse()
                ->setHeader('Retry-After', $isFresh ?: self::RETRY_AFTER);
            return $this->_error(504, null);
        }

        // TODO If freshness change during token, throw error too.

        $isConform = $this->_gateway->isConform();
        if (!$isConform) {
            return $this->_error(502, __('The static repository "%s" is registered by this OAI-PMH gateway, but not conform.',
                $this->_gateway->getRepository()));
        }

        $isBaseUrlValid = $this->_gateway->isBaseUrlValid();
        if (!$isBaseUrlValid) {
            return $this->_error(502, __('The static repository "%s" is registered by this OAI-PMH gateway, but the base url is not conform.',
                $this->_gateway->getRepository()));
        }

        // A last check to comply with specification (introduction of part #4).
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $uri = $request->getScheme() . '://' . $request->getHttpHost() . strtok($request->getRequestUri(), '?');
        if ($uri != $this->_gateway->getBaseUrl()) {
            return $this->_error(502, __('The static repository "%s" is registered by this OAI-PMH gateway, but the request and the base url differ.',
                $this->_gateway->getRepository()));
        }

        switch($_SERVER['REQUEST_METHOD'])
        {
            case 'GET': $query = &$_GET; break;
            case 'POST': $query = &$_POST; break;
            default: return $this->_error(405, __('Error determining request type.'));
        }

        $xmlResponse = new OaiPmhGateway_ResponseGenerator(
            $query, $this->_gateway->getCachePath(), $this->_gateway->url);

        $this->_helper->viewRenderer->setNoRender();
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/xml');
        $response->appendBody($xmlResponse);
    }

    /**
     * Manage a static repository via query (initiate and terminate).
     *
     * Currently, anybody can initiate and terminate a gateway for a static
     * repository.
     */
    public function gatewayAction()
    {
        $url = $this->getParam('initiate');
        if (!empty($url)) {
            $this->forward('initiate', null, null, array('url' => $url));
        }

        $url = $this->getParam('terminate');
        if (!empty($url)) {
            $this->forward('terminate', null, null, array('url' => $url));
        }

        // Display an error of parameters page.
        $this->view->url = '';
        $this->view->message = __('This OAI-PMH gateway allows to get records from a static repository.');
    }

    public function initiateAction()
    {
        $this->_helper->viewRenderer->setRender('gateway');
        $url = $this->getParam('url');
        $this->view->url = $url;
        if ($this->_checkUrl($url)) {
            // Check if a gateway exists already.
            $gateway = $this->_helper->db->findByUrl($url);
            if ($gateway) {
                switch ($gateway->status) {
                    case OaiPmhGateway::STATUS_INITIATED:
                        $this->view->message = __('This url is already managed. Contact the administrator if needed.');
                        $this->view->message_type = 'info';
                        $this->view->gateway = $gateway;
                        break;
                    case OaiPmhGateway::STATUS_TERMINATED:
                        // Only a simple check is done to avoid reinitiating of
                        // repositories anonymously terminated.
                        $user = current_user();
                        if (empty($user) || !is_allowed('OaiPmhGateway_Request', 'initiate')) {
                            $this->view->message = __('This url is marked "Terminated". Contact the administrator if needed.');
                            $this->view->message_type = 'error';
                        }
                        else {
                            $gateway->saveStatus(OaiPmhGateway::STATUS_INITIATED);
                            $this->view->message = __('This url has been reinitiated.');
                            $this->view->message_type = 'success';
                            $this->view->gateway = $gateway;
                        }
                        break;
                }
            }
            // If not exists, add it and display a message.
            else {
                $owner = current_user();
                $gateway = new OaiPmhGateway();
                $gateway->url = $url;
                $gateway->owner_id = $owner ? $owner->id : 0;
                // No check of the url here, but only via request.
                $gateway->save();
                $this->view->message = __('This url has been registered. A check against the base url is going to be done.');
                $this->view->message_type = 'success';
                $this->view->gateway = $gateway;
            }
        }
        else {
            $this->view->message = __('This url does not conform to the OAI-PMH protocol.');
            $this->view->message_type = 'error';
        }
    }

    public function terminateAction()
    {
        $this->_helper->viewRenderer->setRender('gateway');
        $url = $this->getParam('url');
        $this->view->url = $url;
        if ($this->_checkUrl($url)) {
            // Check if the gateway exists.
            $gateway = $this->_helper->db->findByUrl($url);
            if ($gateway) {
                switch ($gateway->status) {
                    case OaiPmhGateway::STATUS_INITIATED:
                        // Only a simple check is done to avoid termination of
                        // repositories set by a user.
                        $user = current_user();
                        if (empty($user) || !is_allowed('OaiPmhGateway_Index', null)) {
                            $owner = $gateway->getOwner();
                            if (empty($owner)) {
                                $gateway->saveStatus(OaiPmhGateway::STATUS_TERMINATED);
                                $this->view->message = __('This static repository has been marked "Terminated". Contact the administrator to reinitiate it.');
                                $this->view->message_type = 'success';
                            }
                            else {
                                $this->view->message = __('Contact the administrator to terminate this static repository.');
                                $this->view->message_type = 'error';
                                $this->view->gateway = $gateway;
                            }
                        }
                        else {
                            $gateway->saveStatus(OaiPmhGateway::STATUS_TERMINATED);
                            $this->view->message = __('This static repository has been terminated.');
                            $this->view->message_type = 'success';
                        }
                        break;
                    case OaiPmhGateway::STATUS_TERMINATED:
                        $this->view->message = __('This static repository is already terminated.');
                        $this->view->message_type = 'info';
                        break;
                }
            }
            // If not exists, display a message.
            else {
                $this->view->message = __('This url is not managed. Contact the administrator if needed.');
                $this->view->message_type = 'error';
            }
        }
        else {
            $this->view->message = __('This url does not conform to the OAI-PMH protocol.');
            $this->view->message_type = 'error';
        }
    }

    protected function _checkUrl($url)
    {
        return Zend_Uri::check($url);
    }

    /**
     * Check if the post is good.
     *
     * @return boolean
     */
    protected function _checkPost()
    {
        if (!$this->_getRepository()) {
            return false;
        }

        if (!$this->_getOaiPmhGateway()) {
            return false;
        }

        return true;
    }

    /**
     * Get and set the full repository identifier.
     *
     * @return string.
     */
    protected function _getRepository()
    {
        if (is_null($this->_repository)) {
            $this->_repository = $this->getParam('repository');
        }

        return $this->_repository;
    }

    /**
     * Get and set the oai-pmh gateway.
     *
     * @return OaiPmhGateway.
     */
    protected function _getOaiPmhGateway()
    {
        if (is_null($this->_gateway)) {
            $this->_gateway = $this->_helper->db->getTable()
                ->findByRepository($this->_repository);
        }

        return $this->_gateway;
    }

    /**
     * Handle error requests.
     *
     * @param integer $httpCode Optional http code (404 by default)..
     * @param string $message Optional message, or no message if null.
     * @return void
     */
    protected function _error($httpCode = 404, $message = '')
    {
        if ($message === '') {
            $message = __('The requested static repository is not registered by this OAI-PMH gateway.');
        }

        if (!empty($message)) {
            _log($message, Zend_Log::NOTICE);
            $this->getResponse()
                ->setHeader('Reason-Phrase', $message);
        }
        $this->view->message = $message;

        $this->getResponse()
            ->setHttpResponseCode($httpCode);
    }
}
