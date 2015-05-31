<?php
/**
 * @copyright Daniel Berthereau, 2015
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package OaiPmhGateway
 */

/**
 * Base class for OAI-PMH Gateway tests.
 */
class OaiPmhGateway_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'OaiPmhGateway';

    protected $_repositoryUrl = 'http://www.openarchives.org/StaticRepositoryExample.xml';
    protected $_cacheFile = 'www.openarchives.org_StaticRepositoryExample.xml';

    public function setUp()
    {
        parent::setUp();

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        // OaiPmhRepository is a required plugin.
        // OaiPmhRepository need the server name to be initialized.
        $_SERVER['SERVER_NAME'] = parse_url(WEB_ROOT, PHP_URL_HOST);
        $pluginHelper->setUp('OaiPmhRepository');
        $pluginHelper->setUp(self::PLUGIN_NAME);
    }

    public function assertPreConditions()
    {
        $gateways = $this->db->getTable('OaiPmhGateway')->findAll();
        $this->assertEquals(0, count($gateways), 'There should be no gateway.');
    }

    protected function _deleteAllRecords()
    {
        $records = $this->db->getTable('OaiPmhGateway')->findAll();
        foreach($records as $record) {
            $record->delete();
        }
        $records = $this->db->getTable('OaiPmhGateway')->findAll();
        $this->assertEquals(0, count($records), 'There should be no gateway.');
    }
}
