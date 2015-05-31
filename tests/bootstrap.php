<?php
define('OAIPMH_GATEWAY_DIR', dirname(dirname(__FILE__)));
define('TEST_FILES_DIR', OAIPMH_GATEWAY_DIR
    . DIRECTORY_SEPARATOR . 'tests'
    . DIRECTORY_SEPARATOR . 'suite'
    . DIRECTORY_SEPARATOR . '_files');
require_once dirname(dirname(OAIPMH_GATEWAY_DIR)) . '/application/tests/bootstrap.php';
require_once 'OaiPmhGateway_Test_AppTestCase.php';
