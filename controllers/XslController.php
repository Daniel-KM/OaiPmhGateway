<?php
/**
 * Controller to get a stylesheet adapted to the repository for Archive Gateway.
 *
 * @package OaiPmhGateway
 */
class OaiPmhGateway_XslController extends Omeka_Controller_AbstractActionController
{
    /**
     * Return the xsl stylesheet adapted to the Archive Gateway.
     *
     * Replace relative urls by absolute ones, that depends on the url of the
     * gateway for the static repository.
     */
    public function indexAction()
    {
        try {
            $stylesheet = physical_path_to('xsl/oai-pmh-repository.xsl');
        } catch (Exception $e) {
            throw new Omeka_Controller_Exception_404;
        }

        // The stylesheet can be the default one in OAI-PMH Repository plugin,
        // or set in the theme.
        $uri = explode('?', $this->getRequest()->getRequestUri());
        $uri = $uri[0];

        $xml = simplexml_load_file($stylesheet, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_PARSEHUGE);
        $xml->registerXPathNamespace('xsl', 'http://www.w3.org/1999/XSL/Transform');
        foreach (array(
                "/xsl:stylesheet/xsl:param[@name = 'homepage-url']" => get_option('oaipmh_gateway_url'),
                "/xsl:stylesheet/xsl:param[@name = 'homepage-text']" => __('OAI-PMH Gateway for Static Repositories'),
                "/xsl:stylesheet/xsl:param[@name = 'gateway-url']" => absolute_url(array(), 'oaipmhgateway_url'),
                "/xsl:stylesheet/xsl:param[@name = 'css-bootstrap']" => null,
                "/xsl:stylesheet/xsl:param[@name = 'css-bootstrap-theme']" => null,
                "/xsl:stylesheet/xsl:param[@name = 'css-oai-pmh-repository']" => null,
                "/xsl:stylesheet/xsl:param[@name = 'javascript-jquery']" => null,
                "/xsl:stylesheet/xsl:param[@name = 'javascript-bootstrap']" => null,
            ) as $xpath => $replace) {
            $params = $xml->xpath($xpath);
            if (isset($params[0]['select'])) {
                $param = $params[0];
                // The url should be checked before update.
                if (is_null($replace)) {
                    $url = (string) $param['select'];
                    $url = trim($url, "'");
                    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '/') !== 0) {
                        $absoluteUrl = WEB_ROOT . '/' . str_replace('../', '', $url);
                        $paramDom = dom_import_simplexml($param);
                        $paramDom->setAttribute('select', "'" . $absoluteUrl . "'");
                    }
                }
                // Use the provided text.
                else {
                    $paramDom = dom_import_simplexml($param);
                    $paramDom->setAttribute('select', "'" . $replace . "'");
                }
            }
        }

        $this->_helper->viewRenderer->setNoRender();
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/xml');
        $response->appendBody($xml->asXml());
    }
}
