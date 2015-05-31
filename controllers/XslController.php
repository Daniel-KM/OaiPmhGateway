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

        $xml = simplexml_load_file($stylesheet);
        $xml->registerXPathNamespace('xsl', 'http://www.w3.org/1999/XSL/Transform');
        foreach (array(
                WEB_ROOT => "/xsl:stylesheet/xsl:param[@name = 'homepage']",
                absolute_url(array(), 'oaipmhgateway_url') => "/xsl:stylesheet/xsl:param[@name = 'gateway']",
                "/xsl:stylesheet/xsl:param[@name = 'css-bootstrap']",
                "/xsl:stylesheet/xsl:param[@name = 'css-bootstrap-theme']",
                "/xsl:stylesheet/xsl:param[@name = 'css-oai-pmh-repository']",
                "/xsl:stylesheet/xsl:param[@name = 'javascript-jquery']",
                "/xsl:stylesheet/xsl:param[@name = 'javascript-bootstrap']",
            ) as $replace => $xpath) {
            $params = $xml->xpath($xpath);
            if (isset($params[0]['select'])) {
                $param = $params[0];
                if (is_string($replace)) {
                    $paramDom = dom_import_simplexml($param);
                    $paramDom->setAttribute('select', "'" . $replace . "'");
                }
                // The url should be checked before update.
                else {
                    $url = (string) $param['select'];
                    $url = trim($url, "'");
                    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '/') !== 0) {
                        $absoluteUrl = WEB_ROOT . '/' . str_replace('../', '', $url);
                        $paramDom = dom_import_simplexml($param);
                        $paramDom->setAttribute('select', "'" . $absoluteUrl . "'");
                    }
                }
            }
        }

        $this->_helper->viewRenderer->setNoRender();
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/xml');
        $response->appendBody($xml->asXml());
    }
}
