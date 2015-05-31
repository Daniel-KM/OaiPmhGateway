<?php
class OaiPmhGateway_RequestTest extends OaiPmhGateway_Test_AppTestCase
{
    protected $_isAdminTest = false;

    /**
     * @todo Use routes.
     */
    public function testGateway()
    {
        $url = '/gateway';
        $this->dispatch($url);
        $this->assertNotRedirectTo('/users/login');
        $this->assertResponseCode(200);
    }

    public function testQuery()
    {
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->save();

        // The test file is directly copied to avoid a fresh check.
        $source = TEST_FILES_DIR . '/' . $this->_cacheFile;
        $destination = $gateway->getCachePath();
        copy($source, $destination);
        $this->assertTrue(file_exists($destination));

        // $url = $gateway->getBaseUrl();
        $url = '/gateway/' . substr(strstr($this->_repositoryUrl, '://'), 3);
        $this->dispatch($url);
        // $this->assertResponseCode(200);
        // Incomplete, but largely tested via the response generator.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testQueryInvalidRepository()
    {
        $url = '/gateway/example.com/wrong_static_repository.xml';
        $this->dispatch($url);
        $this->assertResponseCode(404);
    }

    public function testQueryUnavailable()
    {
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->status = OaiPmhGateway::STATUS_TERMINATED;
        $gateway->save();

        $url = '/gateway/' . substr(strstr($this->_repositoryUrl, '://'), 3) . '?verb=' . 'Identify';
        $this->dispatch($url);
        $this->assertResponseCode(502);
    }

    public function testInitiate()
    {
        $url = '/gateway?initiate=' . $this->_repositoryUrl;

        $gateway = $this->db->getTable('OaiPmhGateway')->findByUrl($this->_repositoryUrl);
        $this->assertEmpty($gateway);

        $this->dispatch($url);
        $this->assertResponseCode(200);
    }

    public function testInitiateTwice()
    {
        $url = '/gateway?initiate=' . $this->_repositoryUrl;
        $this->dispatch($url);
        $gateway = $this->db->getTable('OaiPmhGateway')->findByUrl($this->_repositoryUrl);
        $this->assertNotEmpty($gateway);

        $this->resetResponse();
        $this->dispatch($url);
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('#primary p', 'This url is already managed.');
    }

    public function testTerminate()
    {
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->save();

        $url = '/gateway?terminate=' . $this->_repositoryUrl;
        $this->dispatch($url);
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('#primary p', 'This static repository has been marked "Terminated".');
    }

    public function testTerminateUnknown()
    {
        $this->dispatch('/gateway?terminate=' . 'http://example.org/wrongexample.xml');
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('#primary p', 'This url is not managed.');
    }

    public function testTerminateTwice()
    {
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->save();

        $url = '/gateway?terminate=' . $this->_repositoryUrl;
        $this->dispatch($url);
        $this->resetResponse();
        $this->dispatch($url);
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('#primary p', 'This static repository is already terminated.');
    }

    public function testInitiateTerminated()
    {
        $gateway = new OaiPmhGateway();
        $gateway->url = $this->_repositoryUrl;
        $gateway->status = OaiPmhGateway::STATUS_TERMINATED;
        $gateway->save();

        $url = '/gateway?initiate=' . $this->_repositoryUrl;
        $this->dispatch($url);
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('#primary p', 'This url is marked "Terminated".');
    }
}
