<?php
/**
 * The OaiPmhGateway Ajax controller class.
 *
 * @package OaiPmhGateway
 */
class OaiPmhGateway_AjaxController extends Omeka_Controller_AbstractActionController
{
    /**
     * Controller-wide initialization. Sets the underlying model to use.
     */
    public function init()
    {
        // Don't render the view script.
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_helper->db->setDefaultModelName('OaiPmhGateway');
    }

    /**
     * Handle AJAX requests to update a record.
     */
    public function updateAction()
    {
        if (!$this->_checkAjax('update')) {
            return;
        }

        // Handle action.
        try {
            $public = $this->_getParam('public');
            $status = $this->_getParam('status');
            $friend = $this->_getParam('friend');
            if (!empty($public)) {
                if (!in_array($public, array('true', 'false'))) {
                    $this->getResponse()->setHttpResponseCode(400);
                    return;
                }
            }
            elseif (!empty($status)) {
                if (!in_array($status, array(
                        OaiPmhGateway::STATUS_INITIATED,
                        OaiPmhGateway::STATUS_TERMINATED,
                    ))) {
                    $this->getResponse()->setHttpResponseCode(400);
                    return;
                }
            }
            elseif (!empty($friend)) {
                if (!in_array($friend, array('true', 'false'))) {
                    $this->getResponse()->setHttpResponseCode(400);
                    return;
                }
            }
            else {
                $this->getResponse()->setHttpResponseCode(400);
                return;
            }

            $id = (integer) $this->_getParam('id');
            $gateway = $this->_helper->db->find($id);
            if (!$gateway) {
                $this->getResponse()->setHttpResponseCode(400);
                return;
            }

            if (!empty($public)) {
                $gateway->savePublic($public == 'true');
            }
            elseif (!empty($status)) {
                $gateway->saveStatus($status);
            }
            else {
                $gateway->saveFriend($friend == 'true');
            }
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    /**
     * Handle AJAX requests to delete a record.
     */
    public function deleteAction()
    {
        if (!$this->_checkAjax('delete')) {
            return;
        }

        // Handle action.
        try {
            $id = (integer) $this->_getParam('id');
            $gateway = $this->_helper->db->find($id);
            if (!$gateway) {
                $this->getResponse()->setHttpResponseCode(400);
                return;
            }
            $gateway->delete();
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    /**
     * Handle AJAX requests to check a static repository.
     */
    public function checkAction()
    {
        if (!$this->_checkAjax('check')) {
            return;
        }

        // Handle action.
        try {
            $id = (integer) $this->_getParam('id');
            $gateway = $this->_helper->db->find($id);
            if (!$gateway) {
                $this->getResponse()->setHttpResponseCode(400);
                return;
            }
            $result = $gateway->check();
            $responseData = array('result' => $result);
            $this->_helper->json($responseData);
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    /**
     * Check AJAX requests.
     *
     * 400 Bad Request
     * 403 Forbidden
     * 500 Internal Server Error
     *
     * @param string $action
     */
    protected function _checkAjax($action)
    {
        // Only allow AJAX requests.
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            $this->getResponse()->setHttpResponseCode(403);
            return false;
        }

        // Allow only valid calls.
        if ($request->getControllerName() != 'ajax'
                || $request->getActionName() != $action
            ) {
            $this->getResponse()->setHttpResponseCode(400);
            return false;
        }

        // Allow only allowed users.
        if (!is_allowed('OaiPmhGateway_Index', $action)) {
            $this->getResponse()->setHttpResponseCode(403);
            return false;
        }

        return true;
    }
}
