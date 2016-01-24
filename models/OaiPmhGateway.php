<?php

/**
 * @package OaiPmhGateway
 */
class OaiPmhGateway extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    const STATIC_REPOSITORY_SCHEMA = 'http://www.openarchives.org/OAI/2.0/static-repository.xsd';

    const STATUS_INITIATED = 'initiated';
    const STATUS_TERMINATED = 'terminated';

    public $id;
    public $url;
    public $public;
    public $status;
    public $friend;
    public $cachefile;
    public $owner_id;
    public $added;
    public $modified;

    // The client to check freshness and to fetch the static repository.
    protected $_client;

    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Owner($this);
        $this->_mixins[] = new Mixin_Timestamp($this, 'added', 'modified');
    }

    /**
     * Get the user object.
     *
     * @return User|null
     */
    public function getOwner()
    {
        if ($this->owner_id) {
            return $this->getTable('User')->find($this->owner_id);
        }
    }

    public function savePublic($public)
    {
        if (empty($this->id) || $this->public != $public) {
            $this->public = (boolean) $public;
            $this->save();
        }
    }

    public function saveStatus($status)
    {
        if (empty($this->id) || $this->status != $status) {
            $this->status= (string) $status;
            $this->save();
        }
    }

    public function saveFriend($friend)
    {
        if (empty($this->id) || $this->friend != $friend) {
            $this->friend = (boolean) $friend;
            $this->save();
        }
    }

    public function isPublic()
    {
        return (boolean) $this->public;
    }

    public function isInitiated()
    {
        return $this->status == self::STATUS_INITIATED;
    }

    public function isTerminated()
    {
        return $this->status == self::STATUS_TERMINATED;
    }

    public function isFriend()
    {
        return (boolean) $this->friend;
    }

    /**
     * Return the repository, that is the url without scheme.
     *
     * The url should be a conform one, else use checkUrlForRepository().
     *
     * @param boolean $encodeColonPort If true, encode the colon of the port.
     * This is required to set the base Url (see guidelines 4.4.1). This should
     * not be set ifi
     * .
     * @return string|null Return null if the url is not conform.
     */
    public function getRepository($encodeColonPort = true)
    {
        $parsed = parse_url($this->url);
        if (empty($parsed['port'])) {
            $port = '';
        }
        else {
            $port = ($encodeColonPort ? '%3A' : ':') . $parsed['port'];
        }
        return $parsed['host'] . $port . $parsed['path'];
    }

    /**
     * Check and return the repository, that is the url without scheme.
     *
     * This function is used to check a url. Once clean, use getRepository().
     *
     * @return string|boolean Return false if not conform, else the repository.
     */
    protected function _checkUrlForRepository()
    {
        // Quick clean of the url in case of a direct input.
        $parsed = parse_url(rtrim(trim($this->url), '/.'));

        if (empty($parsed['scheme']) || empty($parsed['host']) || empty($parsed['path'])) {
            return false;
        }

        if (!in_array($parsed['scheme'], array('http', 'https'))) {
            return false;
        }

        // The colon of the port should be url encoded.
        $repository = $parsed['host'] . (empty($parsed['port']) ? '' : ':' . $parsed['port']) . $parsed['path'];
        $url = $parsed['scheme'] . '://' . $repository;
        if (!Zend_Uri::check($url)) {
            return false;
        }

        return $repository;
    }

    /**
     * Return the  base url of this static repository.
     *
     * @internal url() cannot be used because the repository can contain "/".
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return get_option('oaipmh_gateway_url') . '/' . $this->getRepository(true);
    }

    /**
     * Return the OaipmhHarvester_Harvest objects for this repository, if any.
     *
     * @return array of OaipmhHarvester_Harvest
     */
    public function getHarvests()
    {
        if (!plugin_is_active('OaipmhHarvester')) {
            return array();
        }

        // A repository can be harvested with multiple formats (usually not), so
        // there is no default function.
        $table = $this->_db->getTable('OaipmhHarvester_Harvest');
        $tableAlias = $table->getTableAlias();
        $select = $table->getSelect()
            ->where("$tableAlias.base_url = ?", $this->getBaseUrl())
            ->where("$tableAlias.set_spec IS NULL");
        $harvests = $table->fetchObjects($select);

        return $harvests;
    }

    /**
     * Return the OaipmhHarvester_Harvest object for this repository, if any.
     *
     * @param string $prefix Metadata prefix, if any. If empty, the first
     * harvest will be returned.
     * @return OaipmhHarvester_Harvest|null.
     */
    public function getHarvest($prefix = '')
    {
        if (!plugin_is_active('OaipmhHarvester')) {
            return null;
        }

        if ($prefix) {
            $harvest = $this->_db->getTable('OaipmhHarvester_Harvest')
                ->findUniqueHarvest($this->getBaseUrl(), null, $prefix);
        }
        // A repository can be harvested with multiple formats (usually not), so
        // there is no default function.
        else {
            $table = $this->_db->getTable('OaipmhHarvester_Harvest');
            $tableAlias = $table->getTableAlias();
            $select = $table->getSelect()
                ->where("$tableAlias.base_url = ?", $this->getBaseUrl())
                ->where("$tableAlias.set_spec IS NULL")
                ->order("$tableAlias.id ASC")
                ->limit(1);
            $harvest = $table->fetchObject($select);
        }

        return $harvest;
    }

    /**
     * Harvest the repository.
     *
     * @see OaipmhHarvester_IndexController::harvestAction()
     *
     * @param string $prefix Metadata prefix, if any. If empty, the first
     * harvest will be returned.
     * @param array $options Associative array with "update_metadata" and
     * "update_files".
     * @return OaipmhHarvester_Harvest|null|false False means no harvest and no
     * prefix.
     */
    public function harvest($prefix = '', $options = array())
    {
        if (!plugin_is_active('OaipmhHarvester')) {
            return null;
        }

        $harvest = $this->getHarvest($prefix);

        // If true, this is a re-harvest, all parameters will be the same
        if ($harvest) {
            if ($harvest->status == OaipmhHarvester_Harvest::STATUS_COMPLETED) {
                $harvest->start_from = $harvest->initiated;
            } else {
                $harvest->start_from = null;
            }
        }
        // This is a new harvest.
        else {
            // A prefix is required and can only be added manually.
            if (!$prefix) {
                return false;
            }

            // There is no existing identical harvest, create a new entry.
            $harvest = new OaipmhHarvester_Harvest;
            $harvest->base_url = $this->getBaseUrl();
            $harvest->set_spec = null;
            $harvest->set_name = null;
            $harvest->set_description = null;
            $harvest->metadata_prefix = $prefix;
        }

        if (!empty($options['update_metadata'])) {
            $harvest->update_metadata = $options['update_metadata'];
        }
        if (!empty($options['update_files'])) {
            $harvest->update_files = $options['update_files'];
        }

        // Insert the harvest.
        $harvest->status = OaipmhHarvester_Harvest::STATUS_QUEUED;
        $harvest->initiated = date('Y:m:d H:i:s');
        $harvest->save();

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
        $jobDispatcher->setQueueName('imports');

        try {
            $jobDispatcher->sendLongRunning('OaipmhHarvester_Job', array('harvestId' => $harvest->id));
        } catch (Exception $e) {
            $harvest->status = OaipmhHarvester_Harvest::STATUS_ERROR;
            $harvest->addStatusMessage(
                get_class($e) . ': ' . $e->getMessage(),
                OaipmhHarvester_Harvest_Abstract::MESSAGE_CODE_ERROR
            );
            throw $e;
        }

        return $harvest;
    }

    /**
     * Executes before the record is saved.
     *
     * @param array $args
     */
    protected function beforeSave($args)
    {
        if (empty($this->status)) {
            $this->status = self::STATUS_INITIATED;
        }

        // By default, a repository is public.
        $this->public = is_null($this->public) ? true : (boolean) $this->public;

        // By default, a repository isn't friendly (used for internal purpose).
        $this->friend = (boolean) $this->friend;

        if (is_null($this->owner_id)) {
            $this->owner_id = 0;
        }

        // Create an empty cache file if needed.
        if (empty($this->cachefile)) {
            $this->_prepareCacheFile();
        }
    }

    /**
     * Executes after the record is deleted.
     *
     * @param array $args
     */
    protected function afterDelete($args)
    {
        $path = $this->getCachePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Get a HTTP client for checking/retrieving the static repository.
     *
     * @param string $source Source URI.
     * @return Zend_Http_Client
     */
    public function getClient()
    {
        if (is_null($this->_client)) {
            $this->setClient();
        }
        return $this->_client;
    }

    /**
     * Set a HTTP client for checking/retrieving the static repository.
     *
     * @param string $source Source URI.
     * @return Zend_Http_Client
     */
    public function setClient(Zend_Http_Client $client = null)
    {
        if (is_null($client)) {
            $client = new Zend_Http_Client($this->url, array(
               'useragent' => 'Omeka/' . OMEKA_VERSION,
            ));
        }
        $this->_client = $client;
    }

    /**
     * Check the static repository is fresh and conform.
     *
     * @return boolean|null|string True if the repository is fresh and conform,
     * false if an error occurs, null or the retry-after value if timeout.
     */
    public function check()
    {
        $isFresh = $this->isFresh();
        if ($isFresh !== true) {
            return $isFresh;
        }

        $isConform = $this->isConform();
        if ($isConform !== true) {
            return $isConform;
        }

        $isBaseUrlValid = $this->isBaseUrlValid();
        return $isBaseUrlValid;
    }

    /**
     * Check if the original static repository is newer than the cached one.
     *
     * The response, if newer, is cached, so the return is always true except in
     * case of an error or a timeout.
     *
     * According to guidellines, a check should be done for each request.
     *
     * @uses _checkFreshness()
     *
     * @return boolean|null|string True if the cache is cooler, false if an
     * error occurs, null or the retry-after value if timeout.
     */
    public function isFresh()
    {
        $path = $this->getCachePath();
        $isFresh = $this->_checkFreshness($path);
        if ($isFresh === false) {
            $this->saveStatus(self::STATUS_TERMINATED);
        }
        // The timeout is managed in request, but if there is no "Retry-After",
        // there is an issue in the original server.
        elseif ($isFresh !== true) {
            if (is_null($isFresh)) {
                $this->saveStatus(self::STATUS_TERMINATED);
            }
        }
        return $isFresh;
    }

    /**
     * Check if the original static repository is newer than the cached one.
     *
     * The response, if newer, is cached, so the return is always true except in
     * case of an error or a timeout.
     *
     * @param string $path Path to the xml to check.
     * @return boolean|null|string True if the cache is cooler, false if an
     * error occurs, null or the retry-after value if timeout.
     */
    protected function _checkFreshness($path)
    {
         // A true check is done, so the date is not saved in the base.
        $cacheTime = (file_exists($path) && is_file($path) && filesize($path))
            ? gmdate('D, d M Y H:i:s T', filemtime($path))
            :  0;

        $tmpCachePath = tempnam(sys_get_temp_dir(), 'omeka_oaipmhgateway_');
        touch($tmpCachePath);

        $client = $this->getClient();

        $client->setUri($this->url, array(
            'useragent' => 'Omeka/' . OMEKA_VERSION,
        ));
        $client->setStream($tmpCachePath);
        // TODO There is no default proxy + stream adapter in Zend.
        if ($cacheTime) {
            $client->setHeaders('If-Modified-Since', $cacheTime);
        }
        try {
            $response = $client->request('GET');
        } catch (Zend_Http_Client_Adapter_Exception $e) {
            if (!empty($response)
                    &&  $e->getCode() == Zend_Http_Client_Adapter_Exception::READ_TIMEOUT
                ) {
                return $response->getHeader('Retry-After');
            }
            return false;
        }

        // The response can be unsuccessful, but without error if not fresher.
        if ($response->isError()) {
            $result = false;
        }
        // Check the response.
        else {
            $result = true;
            // Update the cache only if the fetched file is newer.
            if ($response->isSuccessful()) {
                // The static repository must not support compressed files.
                // Nevertheless, the file can be compressed at http level.
                // Because the response is streamed to a temp file, this
                // temp file should be decoded here.
                // Furthermore, there is no simple support of proxy stream.
                $encoding = strtolower($response->getHeader('content-encoding'));
                if (in_array($encoding, array('gzip', 'deflate'))) {
                    $success = file_put_contents($tmpCachePath, $response->getBody());
                    if (empty($success)) {
                        @unlink($tmpCachePath);
                        return false;
                    }
                }
                $result = copy($tmpCachePath, $this->getCachePath());
            }
        }

        @unlink($tmpCachePath);
        return $result;
    }

    /**
     * Check if the xml of the original static repository is conform.
     *
     * @return boolean True if conform.
     */
    public function isConform()
    {
        $xmlpath = $this->getCachePath();
        $isConform = $this->_checkConformity($xmlpath);
        if (!$isConform) {
            $this->saveStatus(self::STATUS_TERMINATED);
        }
        return $isConform;
    }

    /**
     * Check if the original static repository is conform (base url...).
     *
     * @todo Full xml check (the cache should be checked one time only).
     *
     * @param string $xmlpath Path to the xml to check.
     * @return boolean True if conform.
     */
    protected function _checkConformity($xmlpath)
    {
        $checkFile = file_exists($xmlpath)
            && filesize($xmlpath)
            && Zend_Loader::isReadable($xmlpath);
        if (!$checkFile) {
            return false;
        }

        if (!get_option('oaipmh_gateway_check_xml')) {
            return true;
        }

        return $this->_validateXml($xmlpath);
    }

    /**
     * Validate the xml conformity of a file.
     *
     * @param string $xmlpath Path to the xml to check.
     * @return boolean
     */
    protected function _validateXml($xmlpath)
    {
        // Use XmlReader because a static repository can be very big.
        $reader = new XMLReader;
        // Don't use LIBXML_NOWARNING | LIBXML_NOERROR, because the check is
        // done below and internally.
        $reader->open($xmlpath);
        $reader->setParserProperty(XMLReader::VALIDATE, true);
        // The errors are removed to manage connections issues.
        // TODO Use XML_CATALOG_FILES?
        $result = @$reader->setSchema(self::STATIC_REPOSITORY_SCHEMA)
            && $reader->isValid();
        // Note: isValid() checks only the current node.
        if ($result) {
            // The libxml errors are used to avoid special warnings with bad or
            // non xml files.
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            // Because the xml error are managed by libxml, the full xml should
            // be processed until first true error.
            while ($reader->read()) {
                $reader->isValid();
                // Remove the strict errors related to declarations.
                $errors = libxml_get_errors();
                libxml_clear_errors();
                if (!empty($errors)) {
                    foreach ($errors as $key => $error) {
                        if ($error->code == 1845 || $error->code == 522) {
                            unset($errors[$key]);
                        }
                    }
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            _log(__('[OaiPmhGateway] Could not parse XML: file %s (line %d, column %d), code %d: %s',
                            $error->file, $error->code, $error->line, $error->column, $error->message));
                        }
                        $result = false;
                        break;
                    }
                }
            }
            libxml_use_internal_errors(false);
            libxml_clear_errors();
        }
        $reader->close();

        return $result;
    }

    /**
     * Check if the base url of the static repository is valid.
     *
     * @return boolean True if conform.
     */
    public function isBaseUrlValid()
    {
        $xmlpath = $this->getCachePath();
        $isBaseUrlValid = $this->_checkBaseUrl($xmlpath);
        if (!$isBaseUrlValid) {
            $this->saveStatus(self::STATUS_TERMINATED);
        }
        return $isBaseUrlValid;
    }

    /**
     * Check if the base url of an xml is valid (starts with the gateway url).
     *
     * @param string $xmlpath Path to the xml to check.
     * @return boolean True if the base url is valid.
     */
    protected function _checkBaseUrl($xmlpath)
    {
        $checkFile = file_exists($xmlpath)
            && filesize($xmlpath)
            && Zend_Loader::isReadable($xmlpath);
        if (!$checkFile) {
            return false;
        }

        // Get the base url of the file.
        $baseUrl = '';

        // Use XmlReader because a static repository can be very big.
        $reader = new XMLReader;
        $reader->open($xmlpath, null, LIBXML_NSCLEAN);
        // No validation: this is done somewhere else.
        $reader->setParserProperty(XMLReader::LOADDTD, false);
        $reader->setParserProperty(XMLReader::DEFAULTATTRS, false);
        $reader->setParserProperty(XMLReader::VALIDATE, false);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT
                    && $reader->name == 'Identify'
                ) {
                while ($reader->read()) {
                    if ($reader->nodeType == XMLReader::ELEMENT
                            && $reader->name === 'oai:baseURL'
                        ) {
                        $baseUrl = $reader->readString();
                        break 2;
                    }

                    // Stop to end of Identify.
                    if ($reader->nodeType == XMLReader::END_ELEMENT
                            && $reader->name == 'Identify'
                        ) {
                        break 2;
                    }
                }
            }
        }
        $reader->close();

        if (empty($baseUrl)) {
            return false;
        }

        return $this->getBaseUrl() == $baseUrl;
    }

    /**
     * Record validation rules used by the parent abstract class.
     *
     * @todo Put some of these checks in the form.
     */
    protected function _validate()
    {
        $repository = $this-> _checkUrlForRepository();
        if (empty($repository)) {
            $this->addError('url', __('An url is required to set the the static repository and it should conforms to the http/https protocol.'));
        }

        // Quick clean of the url in case of a direct input.
        $this->url = parse_url($this->url, PHP_URL_SCHEME) . '://' . $repository;

        if (empty($this->id)) {
            $gateway = $this->getTable()->findByRepository($repository);
            if (!empty($gateway)) {
               $this->addError('url', __('The gateway for url "%s" exists already.', $this->url));
            }
        }

        if (!in_array($this->status, array(
                null,
                self::STATUS_INITIATED,
                self::STATUS_TERMINATED,
            ))) {
            $this->addError('status', __('The status "%s" does not exist.', $this->status));
        }
    }

    /**
     * Get the full filepath of the internal cache.
     *
     * @todo Don't cache internal repositories, in particular those created with
     * OAI-PMH Static Repository.
     */
    public function getCachePath()
    {
        return FILES_DIR
            . DIRECTORY_SEPARATOR . get_option('oaipmh_gateway_cache_dir')
            . DIRECTORY_SEPARATOR . $this->cachefile;
    }

    /**
     * Create filename of the cached repository and touch it.
     *
     * @return string The cache filename.
     */
    protected function _prepareCacheFile()
    {
        $base = FILES_DIR . DIRECTORY_SEPARATOR
            . trim(get_option('oaipmh_gateway_cache_dir'), '/\\') . DIRECTORY_SEPARATOR;
        $extension = '.xml';

        $path = parse_url($this->url, PHP_URL_HOST)
            . pathinfo(str_replace('/', '_', parse_url($this->url, PHP_URL_PATH)), PATHINFO_FILENAME)
            . $extension;
        $path = substr($path, -240);

        $i = 0;
        $testpath = $path;
        while (file_exists($base . $testpath)) {
            $testpath = substr($path, 0, -strlen($extension)) . '_' . ++$i . $extension;
        }

        touch($base . $testpath);
        $this->cachefile = $testpath;
        return $this->cachefile;
    }

    public function getProperty($property)
    {
        switch($property) {
            case 'owner_name':
                $owner = $this->getOwner();
                return $owner
                    ? $owner->username
                    : __('Anonymous');
            default:
                return parent::getProperty($property);
        }
    }

    public function getResourceId()
    {
        return 'OaiPmhGateways';
    }
}
