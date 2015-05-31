<?php
class OaiPmhGateway_ResponseGeneratorTest extends OaiPmhGateway_Test_AppTestCase
{
    protected $_isAdminTest = false;

    protected $_gateway;
    protected $_baseUrl;

    /*
    public function testBaseUrl()
    {
    }

    public function testInvalidBaseUrl()
    {
    }
    */

    public function testRequestEmptyQuery()
    {
        $this->_prepareGatewayTest();
        $response = $this->_getResponse();
        $this->_checkResponse($response, 'NoVerb.xml');
    }

    public function testRequestBadVerb()
    {
        $this->_prepareGatewayTest();
        $query = 'FooBar';
        $response = $this->_getResponse($query);
        $this->_checkResponse($response, 'BadVerb.xml');
    }

    public function testQueryIdentify()
    {
        $this->_prepareGatewayTest();
        $query = 'Identify';
        $response = $this->_getResponse($query);
        $this->_checkResponse($response, 'Identify.xml');
    }

    public function testQueryListMetadataFormats()
    {
        $this->_prepareGatewayTest();
        $query = 'ListMetadataFormats';
        $response = $this->_getResponse($query);
        $this->_checkResponse($response, 'ListMetadataFormats.xml');
    }

    public function testQueryListRecords()
    {
        $this->_prepareGatewayTest();
        $query = array('verb' => 'ListRecords', 'metadataPrefix' => 'oai_dc');
        $response = $this->_getResponse($query);
        $this->_checkResponse($response, 'ListRecords.oai_dc.xml');

        $query = array('verb' => 'ListRecords', 'metadataPrefix' => 'oai_rfc1807');
        $response = $this->_getResponse($query);
        $this->_checkResponse($response, 'ListRecords.oai_rfc1807.xml');
    }

    /**
     * Prepare a correct gateway to the OpenArchive.org example.
     *
     * @see http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm#SR_URL
     *
     * @todo Currently, use only the cache, so no issue about refresh, etc.
     */
    protected function _prepareGatewayTest($parameters = array())
    {
        $gateway = &$this->_gateway;
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->friend = true;
        $gateway->save();

        // The test file is directly copied to avoid a fresh check.
        $source = TEST_FILES_DIR . '/' . $this->_cacheFile;
        $destination = $gateway->getCachePath();
        copy($source, $destination);
        $this->assertTrue(file_exists($destination));

        $gateway = $this->db->getTable('OaiPmhGateway')->findByUrl($this->_repositoryUrl);
        $this->assertNotEmpty($gateway);
        $this->assertEquals(OaiPmhGateway::STATUS_INITIATED, $gateway->status);

        // The base url should not have the host to simplify the response check.
        $this->_baseUrl = substr($gateway->getBaseUrl(), strlen(WEB_ROOT));
    }

    /**
     * Return a response from a a query.
     *
     * @param array|string $args Arguments to query. If string, this is a verb.
     * @return string The body of the response.
     */
    protected function _getResponse($args = array())
    {
        if (is_string($args)) {
            $args = array('verb' => $args);
        }
        $query = http_build_query($args);

        $url = $this->_baseUrl;
        if ($query) {
            $url .= '?' . $query;
        }

        // This is needed for good checks (Get is simpler to manage here).
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = $query;

        // The dispatch is used only to set the current url for the request tag.
        $this->dispatch($url);

        $response = new OaiPmhGateway_ResponseGenerator(
            $args, $this->_gateway->getCachePath(), $this->_gateway->url);
        return $response->__toString();
    }

    /**
     * Assert true if the xml response is equal to a prepared file.
     *
     * @param string $response
     * @param string $filename The file to use to check the response.
     */
    protected function _checkResponse($response, $filename)
    {
        $expected = file_get_contents(TEST_FILES_DIR . '/' . $filename);
        $actual = &$response;

        // The response can be saved to simplify update of the tests.
        // file_put_contents(sys_get_temp_dir() . '/' . basename($filename), $actual);

        // Because the xml is known and small, it's possible to manipulate it
        // via string functions. This is only used to clean dates.

        // Get the date from the original file.
        $needle = '<responseDate>';
        $expectedTime = substr(strstr($expected, $needle), strlen($needle), 20);
        $expectedDate = substr($expectedTime, 0, 10);
        $actualTime = substr(strstr($actual, $needle), strlen($needle), 20);
        $actualDate = substr($actualTime, 0, 10);

        // Use the new date and time in the original file.
        $expected = str_replace(
            $needle . $expectedTime . '</responseDate>',
            $needle . $actualTime . '</responseDate>',
            $expected);

        // Remove all whitespaces to manage different implementations of xml
        // on different systems.
        $expected = preg_replace('/\s+/', '', $expected);
        $actual = preg_replace('/\s+/', '', $actual);

        // This assert allows to quick check the value.
        $this->assertEquals(strlen($expected), strlen($actual), __('The result in "%s" is not the same as expected.', basename($filename)));

        $this->assertEquals($expected, $actual);
    }
}