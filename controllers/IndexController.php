<?php
/**
 * Controller for Achive Gateway admin pages.
 *
 * @package OaiPmhGateway
 */
class OaiPmhGateway_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * The number of records to browse per page.
     *
     * @var string
     */
    protected $_browseRecordsPerPage = 100;

    protected $_autoCsrfProtection = true;

    /**
     * Controller-wide initialization. Sets the underlying model to use.
     */
    public function init()
    {
        $this->_db = $this->_helper->db;
        $this->_db->setDefaultModelName('OaiPmhGateway');
    }

    /**
     * Retrieve and render a set of records for the controller's model.
     *
     * @uses Omeka_Controller_Action_Helper_Db::getDefaultModelName()
     * @uses Omeka_Db_Table::findBy()
     */
    public function browseAction()
    {
        $this->view->addForm = $this->_getAddForm();
        $this->view->addForm->setAction($this->_helper->url('add'));

        if (!$this->_hasParam('sort_field')) {
            $this->_setParam('sort_field', 'added');
        }

        if (!$this->_hasParam('sort_dir')) {
            $this->_setParam('sort_dir', 'd');
        }

        parent::browseAction();
    }

    /**
     * Add an OAI-PMH Gateway.
     *
     * @internal Same as parent, but with a redirect.
     */
    public function addAction()
    {
        // From parent::addAction(), to allow to set parameters as array.
        $class = $this->_helper->db->getDefaultModelName();
        $varName = $this->view->singularize($class);

        if ($this->_autoCsrfProtection) {
            $csrf = new Omeka_Form_SessionCsrf;
            $this->view->csrf = $csrf;
        }

        $record = new $class();
        if ($this->getRequest()->isPost()) {
            if ($this->_autoCsrfProtection && !$csrf->isValid($_POST)) {
                $this->_helper->_flashMessenger(__('There was an error on the form. Please try again.'), 'error');
                $this->view->$varName = $record;
                $this->_redirectAfterAdd($record);
            }
            $record->setPostData($_POST);

            if ($record->save(false)) {
                $successMessage = $this->_getAddSuccessMessage($record);
                if ($successMessage != '') {
                    $this->_helper->flashMessenger($successMessage, 'success');
                }
                $this->_redirectAfterAdd($record);
            } else {
                $this->_helper->flashMessenger($record->getErrors());
            }
        }
        $this->view->$varName = $record;
        $this->_redirectAfterAdd($record);
    }

    protected function _getAddForm()
    {
        require dirname(__FILE__)
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'forms'
            . DIRECTORY_SEPARATOR . 'Add.php';
        return new OaiPmhGateway_Form_Add();
    }

    public function harvestAction()
    {
        if (!plugin_is_active('OaipmhHarvester')) {
            $message = __('Plugin OAI-PMH Harvester is not enabled.');
            $this->_helper->flashMessenger($message, 'error');
            return $this->_helper->redirector->goto('browse');
        }

        $gateway = $this->_db->findById();
        if (empty($gateway)) {
            $id = $this->_getParam('id');
            $message = __('Gateway #%d does not exist.', $id);
            $this->_helper->flashMessenger($message, 'error');
            return $this->_helper->redirector->goto('browse');
        }

        // Check if the repository is already set and go to Oai-Pmh Harvester.
        // Note: redirect, forward and gotoRoute cannot be used.
        $prefix = $this->_getParam('metadata_prefix');
        $harvest = $gateway->getHarvest($prefix);
        // Reharvest an existing harvest.
        if ($harvest) {
            $options = array(
                'harvest_id' => $harvest->id,
            );
            $url = absolute_url(array(
                    'module' => 'oaipmh-harvester',
                    'controller' => 'index',
                    'action' => 'harvest',
                ), null, $options);
        }
        // Go to set a new harvest.
        else {
            if ($prefix) {
                $options = array(
                    'base_url' => $gateway->getBaseUrl(),
                    'metadata_spec' => $prefix,
                    'set_spec' => null,
                );
                $updateMetadata = $this->getParam('update_metadata');
                if ($updateMetadata) {
                    $options['update_metadata'] = $updateMetadata;
                }
                $updateFiles = $this->getParam('update_files');
                if ($updateFiles) {
                    $options['update_files'] = $updateFiles;
                }
                $url = absolute_url(array(
                        'module' => 'oaipmh-harvester',
                        'controller' => 'index',
                        'action' => 'harvest',
                    ), null, $options);
            }
            // Need to set a prefix manually.
            else {
                $options = array(
                    'base_url' => $gateway->getBaseUrl(),
                );
                $url = absolute_url(array(
                        'module' => 'oaipmh-harvester',
                        'controller' => 'index',
                        'action' => 'sets',
                    ), null, $options);
            }
        }

        $this->redirect($url);
    }

    /**
     * Check an OAI-PMH Gateway and return to browse.
     */
    public function checkAction()
    {
        $singularName = $this->view->singularize($this->_helper->db->getDefaultModelName());
        $record = $this->_helper->db->findById();
        if ($record) {
            $record->check();
        }
        $this->_forward('browse');
    }

    /**
     * Return the number of records to display per page.
     *
     * @return integer|null
     */
    protected function _getBrowseRecordsPerPage($pluralName = null)
    {
        return $this->_browseRecordsPerPage;
    }
}
