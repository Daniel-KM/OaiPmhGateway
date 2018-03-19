<?php
/**
 * OAI-PMH Gateway
 *
 * Gateway to expose files and metadata of a standard OAI-PMH static repository.
 *
 * @link http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm
 *
 * @copyright Copyright Daniel Berthereau, 2015
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package OaiPmhGateway
 */

/**
 * The OAI-PMH Gateway plugin.
 */
class OaiPmhGatewayPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'upgrade',
        'uninstall',
        'uninstall_message',
        'config_form',
        'config',
        'define_routes',
        'define_acl',
    );

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = array(
        'admin_navigation_main',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        'oaipmh_gateway_url' => '',
        'oaipmh_gateway_cache_dir' => 'gateway',
        'oaipmh_gateway_identify_friends' => true,
        'oaipmh_gateway_notes_url' => '',
        'oaipmh_gateway_notes' => '<p>This gateway is an <a href="https://www.openarchives.org/OAI/2.0/openarchivesprotocol.htm" target="_blank">OAI-PMH</a> data provider for&nbsp;<a href="https://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm" target="_blank">static repositories</a>.</p>
<p>It is available through the floss plugin <a href="https://github.com/Daniel-KM/Omeka-plugin-OaiPmhGateway">OAI-PMH Gateway</a> for <a href="https://omeka.org">Omeka</a>.</p>
<p>Feel free to use it and to initiate new repositories!</p>',
        'oaipmh_gateway_check_xml' => true,
        // With roles, in particular if Guest User is installed.
        'oaipmh_gateway_allow_roles' => 'a:1:{i:0;s:5:"super";}',
    );

    /**
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->OaiPmhGateway}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `public` tinyint(4) NOT NULL DEFAULT '1',
            `status` enum('initiated', 'terminated') NOT NULL,
            `friend` tinyint(1) NOT NULL DEFAULT '1',
            `cachefile` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            `owner_id` int unsigned NOT NULL DEFAULT '0',
            `added` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
            `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `url` (`url`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ";
        $db->query($sql);

        // The Static Repository Gateway URL is saved to allow easier and
        // quicker build of Static Repository Gateway URL, in particular with
        // background processes.
        $this->_options['oaipmh_gateway_url'] = $this->_getGatewayUrl();

        $this->_installOptions();

        // Check if there is a folder for the static repositories files, else
        // create one and protect it.
        $staticDir = FILES_DIR . DIRECTORY_SEPARATOR . get_option('oaipmh_gateway_cache_dir');
        if (!file_exists($staticDir)) {
            mkdir($staticDir, 0775, true);
            copy(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . 'index.html',
                $staticDir . DIRECTORY_SEPARATOR . 'index.html');
        }
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        if (version_compare($oldVersion, '2.2.1', '<')) {
            $sql = "
                ALTER TABLE `{$db->OaiPmhGateway}`
                ALTER `added` SET DEFAULT '2000-01-01 00:00:00'
            ";
            $db->query($sql);
        }
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->OaiPmhGateway`";
        $db->query($sql);

        // Remove the cache folder.
        $path = get_option('oaipmh_gateway_cache_dir');
        array_map('unlink', glob($path . '*'));
        @rmdir($path);

        $this->_uninstallOptions();
    }

    /**
     * Add a message to the confirm form for uninstallation of the plugin.
     */
    public function hookUninstallMessage()
    {
        echo __('The cache folder "%s" where the xml files of the static repositories are saved will be removed.', get_option('oaipmh_gateway_cache_dir'));
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/oai-pmh-gateway-config-form.php'
        );
    }

    /**
     * Saves plugin configuration page and creates folders if needed.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $optionValue) {
            if (in_array($optionKey, array(
                    'oaipmh_gateway_allow_roles',
                ))) {
               $post[$optionKey] = serialize($post[$optionKey]) ?: serialize(array());
            }
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }

    }

    /**
     * Defines route for direct download count.
     */
    public function hookDefineRoutes($args)
    {
        $args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . '/routes.ini', 'routes'));
    }

    /**
     * Define the plugin's access control list.
     *
     * @param array $args This array contains a reference to the zend ACL.
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $resource = 'OaiPmhGateway_Index';

        // TODO This is currently needed for tests for an undetermined reason.
        if (!$acl->has($resource)) {
            $acl->addResource($resource);
        }
        // Hack to disable CRUD actions.
        $acl->deny(null, $resource, array('show', 'add', 'edit', 'delete'));
        $acl->deny(null, $resource);

        $roles = $acl->getRoles();

        // Check that all the roles exist, in case a plugin-added role has
        // been removed (e.g. GuestUser).
        $allowRoles = unserialize(get_option('oaipmh_gateway_allow_roles')) ?: array();
        $allowRoles = array_intersect($roles, $allowRoles);
        if ($allowRoles) {
            $acl->allow($allowRoles, $resource);
        }

        $denyRoles = array_diff($roles, $allowRoles);
        if ($denyRoles) {
            $acl->deny($denyRoles, $resource);
        }

        $resource = 'OaiPmhGateway_Request';
        if (!$acl->has($resource)) {
            $acl->addResource($resource);
        }
        $acl->allow(null, $resource,
            array('query', 'gateway', 'initiate', 'terminate'));
    }

    /**
     * Add the plugin link to the admin main navigation.
     *
     * @param array Navigation array.
     * @return array Filtered navigation array.
    */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('OAI-PMH Gateway'),
            'uri' => url('oai-pmh-gateway'),
            'resource' => 'OaiPmhGateway_Index',
            'privilege' => 'index',
        );
        return $nav;
    }

    /**
     * Get the The Static Repository Gateway URL from the routing system.
     *
     *@internal This is used only during install.
     *
     *  @return string The Static Repository Gateway URL.
     */
    private function _getGatewayUrl()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $this->hookDefineRoutes(array('router' => $router));

        // There is no function for absolute public url.
        set_theme_base_url('public');
        $url = absolute_url(array(), 'oaipmhgateway_url', array(), false, false);
        revert_theme_base_url();
        return $url;
    }
}
