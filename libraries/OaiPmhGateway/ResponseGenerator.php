<?php
/**
 * Generates the XML responses to OAI-PMH requests received by a registered
 * static repository.
 *
 * The gateway is designed for small repositories (< 5000 records). Because this
 * is static, the response is always a simple extract from an existing xml file.
 *
 * @internal To speed process, the Xml response is built via a streamed and
 * ordered read of the static repository and written via a streamed writer. The
 * node object is used only on small portions of the tree.
 *
 * @see http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm
 * @see OaiPmhRepository_ResponseGenerator
 * @package OaiPmhGateway
 */
class OaiPmhGateway_ResponseGenerator extends OaiPmhRepository_OaiXmlGeneratorAbstract
{
    // Makes output "pretty" XML. Good for debugging and small repositories.
    const XML_INDENT = true;

    /**
     * HTTP query string or POST vars formatted as an associative array.
     * @var array
     */
    private $query;

    /**
     * Full path to the xml file that contains data of the static repository.
     */
    private $xmlpath;

    /**
     * The HTTP URL where the Static Repository is accessible.
     *
     * This source is required for the verb Identify.
     */
    private $source;

    /**
     * The XML reader for the static repository.
     */
    private $reader;

    /**
     * Array of all supported metadata formats.
     * $metdataFormats['metadataPrefix'] = ImplementingClassName
     * @var array
     */
    private $metadataFormats;

    private $_listLimit;

    private $_tokenExpirationTime;

    /**
     * Flags if the request is written (allows to manage errors).
     * @var bool
     */
    protected $isRequestWritten;

    /**
     * Constructor
     *
     * Creates the XmlWriter object, and adds XML elements common to all OAI-PMH
     * responses. Dispatches control to appropriate verb, if any.
     *
     * @param array $query HTTP POST/GET query key-value pair array.
     * @param string $xmlpath Checked path to the xml file of the repository.
     * @param string $source The url where the static repository is accessible.
     * @uses dispatchRequest()
     */
    public function __construct($query, $xmlpath, $source)
    {
        $this->_loadConfig();

        $this->error = false;
        $this->query = $query;
        $this->xmlpath = $xmlpath;
        $this->source = $source;

        // Create the xml for the response.
        $writer = &$this->document;

        // Create the reader to extract content from the xml file.
        $reader = &$this->reader;
        $reader = new XMLReader;
        $reader->open($this->xmlpath, null, LIBXML_NSCLEAN);

        $namespaceId = parse_url($this->source, PHP_URL_HOST);
        OaiPmhGateway_OaiIdentifier::initializeNamespace($namespaceId);

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(self::XML_INDENT);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');

        if (get_option('oaipmh_repository_add_human_stylesheet')) {
            $stylesheet = absolute_url('oai-pmh-gateway/xsl');
            $writer->writePi('xml-stylesheet', 'type="text/xsl" href="' . $stylesheet . '"');
        }

        $writer->startElement('OAI-PMH');
        $writer->writeAttribute('xmlns', self::OAI_PMH_NAMESPACE_URI);
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation', self::OAI_PMH_NAMESPACE_URI . ' ' . self::OAI_PMH_SCHEMA_URI);

        $writer->writeElement('responseDate', OaiPmhRepository_Date::unixToUtc(time()));

        // Metadata formats is used in most requests, so fill it now.
        $this->metadataFormats = $this->_getFormats();

        $this->dispatchRequest();

        $writer->endElement();

        // End the static repository. It will be outputed via __toString().
        $writer->endDocument();

        $reader->close();
    }

    private function _loadConfig()
    {
        $iniFile = PLUGIN_DIR
            . DIRECTORY_SEPARATOR . 'OaiPmhGateway'
            . DIRECTORY_SEPARATOR . 'config.ini';

        $ini = new Zend_Config_Ini($iniFile, 'oai-pmh-gateway');

        $this->_listLimit = $ini->list_limit;
        $this->_tokenExpirationTime = $ini->token_expiration_time;
    }

    /**
     * Parses the HTTP query and dispatches to the correct verb handler.
     *
     * Checks arguments for each verb type, and sets XML request tag.
     *
     * @uses checkArguments()
     */
    private function dispatchRequest()
    {
        $requiredArgs = array();
        $optionalArgs = array();
        if (empty($this->query['verb'])) {
            $this->throwError(self::OAI_ERR_BAD_VERB, 'No verb specified.');
            return;
        }
        $resumptionToken = $this->_getParam('resumptionToken');

        if ($resumptionToken) {
            $requiredArgs = array('resumptionToken');
        }
        else {
            switch($this->query['verb'])
            {
                case 'Identify':
                    break;
                case 'GetRecord':
                    $requiredArgs = array('identifier', 'metadataPrefix');
                    break;
                case 'ListRecords':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;
                case 'ListIdentifiers':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;
                case 'ListSets':
                    break;
                case 'ListMetadataFormats':
                    $optionalArgs = array('identifier');
                    break;
                default:
                    $this->throwError(self::OAI_ERR_BAD_VERB);
            }
        }

        $this->checkArguments($requiredArgs, $optionalArgs);

        if (!$this->error) {
            $this->_writeRequest();
            if ($resumptionToken) {
                $this->resumeListResponse($resumptionToken);
            }
            else {
                /* This Inflector use means verb-implementing functions must be
                   the lowerCamelCased version of the verb name. */
                $functionName = Inflector::variablize($this->query['verb']);
                $this->$functionName();
            }
        }
    }

    /**
     * Write the request tag with or without attribute.
     *
     * @param boolean $withAttributes Optional
     */
    private function _writeRequest($withAttributes = true)
    {
        $writer = $this->document;

        $writer->startElement('request');
        if ($withAttributes) {
            foreach($this->query as $key => $value) {
                $writer->writeAttribute($key, $value);
            }
        }
        $writer->text(parse_url(WEB_ROOT, PHP_URL_SCHEME) . '://'
            . parse_url(WEB_ROOT, PHP_URL_HOST)
            . current_url());
        $writer->endElement();

        $this->isRequestWritten = true;
    }

    /**
     * Checks the argument list from the POST/GET query.
     *
     * Checks if the required arguments are present, and no invalid extra
     * arguments are present.  All valid arguments must be in either the
     * required or optional array.
     *
     * @param array requiredArgs Array of required argument names.
     * @param array optionalArgs Array of optional, but valid argument names.
     */
    private function checkArguments($requiredArgs = array(), $optionalArgs = array())
    {
        $requiredArgs[] = 'verb';

        /* Checks (essentially), if there are more arguments in the query string
           than in PHP's returned array, if so there were duplicate arguments,
           which is not allowed. */
        if($_SERVER['REQUEST_METHOD'] == 'GET' && (urldecode($_SERVER['QUERY_STRING']) != urldecode(http_build_query($this->query))))
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Duplicate arguments in request.");

        $keys = array_keys($this->query);

        foreach(array_diff($requiredArgs, $keys) as $arg)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Missing required argument $arg.");
        foreach(array_diff($keys, $requiredArgs, $optionalArgs) as $arg)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Unknown argument $arg.");

        $from = $this->_getParam('from');
        $until = $this->_getParam('until');

        $fromGran = OaiPmhRepository_Date::getGranularity($from);
        $untilGran = OaiPmhRepository_Date::getGranularity($until);

        if($from && !$fromGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        if($until && !$untilGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        if($from && $until && $fromGran != $untilGran)
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Date/time arguments of differing granularity.");

        $metadataPrefix = $this->_getParam('metadataPrefix');

        if($metadataPrefix && !array_key_exists($metadataPrefix, $this->metadataFormats))
            $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
    }

    /**
     * Responds to the Identify verb.
     *
     * Appends the Identify element for the repository to the response.
     */
    public function identify()
    {
        if ($this->error) {
            return;
        }

        $writer = $this->document;
        $reader = $this->reader;

        // Move to the identify node.
        while ($reader->read()
            && $reader->nodeType !== XMLReader::END_ELEMENT
            && $reader->name !== 'Identify');

        $writer->startElement('Identify');
        $this->_writeRawWithoutPrefixOai(true);

        if (get_option('oaipmh_gateway_identify_friends')) {
            $writer->startElement('description');
            $writer->startElement('friends');
            $writer->writeAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/friends/');
            $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $writer->writeAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/friends/ http://www.openarchives.org/OAI/2.0/friends.xsd');
            $db = get_db();
            $gateways = $db->getTable('OaiPmhGateway')->getFriends();
            foreach ($gateways as $gateway) {
                $writer->writeElement('baseURL', $gateway->getBaseUrl());
            }
            $writer->endElement();
            $writer->endElement();
        }

        $writer->startElement('description');
        $writer->startElement('gateway');
        $writer->writeAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/gateway/');
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/gateway/ http://www.openarchives.org/OAI/2.0/gateway.xsd');
        $writer->writeElement('source', $this->source);
        $writer->writeElement('gatewayDescription', 'http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm');
        $writer->writeElement('gatewayAdmin', get_option('administrator_email'));
        $writer->writeElement('gatewayURL', get_option('oaipmh_gateway_url'));
        if (get_option('oaipmh_gateway_notes_url') || get_option('oaipmh_gateway_notes')) {
            $notesUrl = get_option('oaipmh_gateway_notes_url');
            if (empty($notesUrl)) {
                $serverUrlHelper = new Zend_View_Helper_ServerUrl;
                $notesUrl = $serverUrlHelper->serverUrl() . public_url('/oai-pmh-gateway/policy');
            }
            $writer->writeElement('gatewayNotes', $notesUrl);
        }
        $writer->endElement();
        $writer->endElement();

        // Add a description for Oai Identifier (get the first one).
        $writer->startElement('description');
        while ($reader->read() && $reader->name !== 'oai:identifier');
        OaiPmhGateway_OaiIdentifier::describeIdentifier($writer, $reader->readString());
        $writer->endElement();

        $writer->endElement();
    }

    /**
     * Responds to the GetRecord verb.
     *
     * Outputs the header and metadata in the specified format for the specified
     * identifier.
     */
    private function getRecord()
    {
        $identifier = $this->_getParam('identifier');
        $metadataPrefix = $this->_getParam('metadataPrefix');

        $record = $this->_getRecord($identifier, $metadataPrefix, true);
        if (empty($record)) {
            if (is_null($record)) {
                $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
            }
            return;
        }

        $writer = $this->document;
        $writer->startElement('GetRecord');
        $writer->writeRaw($record);
        $writer->endElement();
    }

    /**
     * Responds to the ListMetadataFormats verb.
     *
     * Outputs records for all of the items in the static repository in the
     * specified metadata format.
     *
     * If there is an identifier, returns only formats for this identifier.
     */
    private function listMetadataFormats()
    {
        $identifier = $this->_getParam('identifier');
        // The Identifier is not used for lookup, simply checked if exists.
        if ($identifier) {
            $formattedRecords = array();
            foreach ($this->metadataFormats as $metadataPrefix => $metadataFormat) {
                $record = $this->_getRecord($identifier, $metadataPrefix);
                // If the record is false, an error occurs, that is already set.
                if ($record === false) {
                    return;
                }
                $formattedRecords[$metadataPrefix] = $record;
            }
            $formattedRecords = array_filter($formattedRecords);
            if (empty($formattedRecords)) {
                $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
                return;
            }
            $metadataFormats = array_intersect_key($this->metadataFormats, $formattedRecords);
        }
        else {
            $metadataFormats = $this->metadataFormats;
        }

        if (!$this->error) {
            $writer = $this->document;

            $writer->startElement('ListMetadataFormats');
            foreach ($metadataFormats as $metadataPrefix => $metadataFormat) {
                // $this->_writeRawWithoutPrefixOai(false, false, $metadataFormat);
                $writer->writeRaw($metadataFormat);
            }
            $writer->endElement();
        }
    }

    /**
     * Responds to the ListSets verb: no set for a static repository.
     */
    private function listSets()
    {
        $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
    }

    /**
     * Responds to the ListRecords verb.
     *
     * @uses initListResponse()
     */
    private function listRecords()
    {
        $this->_initListResponse('ListRecords');
    }

    /**
     * Responds to the ListIdentifiers verb.
     *
     * @uses initListResponse()
     */
    private function listIdentifiers()
    {
        $this->_initListResponse('ListIdentifiers');
    }

    /**
     * Responds to the ListIdentifiers and ListRecords verbs.
     *
     * Only called for the initial request in the case of multiple incomplete
     * list responses
     *
     * @internal ListRecords and ListIdentifiers use a common code base and
     * share all possible arguments, and are handled by one function.
     *
     * @uses listResponse()
     * @param string $verb Verb can be ListRecords or ListIdentifiers.
     */
    private function _initListResponse($verb)
    {
        $fromDate = null;
        $untilDate = null;

        $from = $this->_getParam('from');
        if ($from) {
            $fromDate = OaiPmhRepository_Date::utcToDb($from);
        }
        $until = $this->_getParam('until');
        if ($until) {
            $untilDate = OaiPmhRepository_Date::utcToDb($until, true);
        }

        $this->listResponse($verb,
                                $this->query['metadataPrefix'],
                                0,
                                $this->_getParam('set'),
                                $fromDate,
                                $untilDate);
    }

    /**
     * Returns the next incomplete list response based on the given resumption
     * token.
     *
     * @param string $token Resumption token
     * @uses listResponse()
     */
    private function resumeListResponse($token)
    {
        $tokenTable = get_db()->getTable('OaiPmhRepositoryToken');
        $tokenTable->purgeExpiredTokens();

        $tokenObject = $tokenTable->find($token);

        if(!$tokenObject || ($tokenObject->verb != $this->query['verb']))
            $this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
        else
            $this->listResponse($tokenObject->verb,
                                $tokenObject->metadata_prefix,
                                $tokenObject->cursor,
                                $tokenObject->set,
                                $tokenObject->from,
                                $tokenObject->until);
    }

    /**
     * Responds to the two main List verbs, includes resumption and limiting.
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @uses createResumptionToken()
     */
    private function listResponse($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $writer = $this->document;
        $reader = $this->reader;

        $listLimit = $this->_listLimit;

        // This number is used to get the total of records and to fetch only the
        // limited part of the result.
        $recordNumber = 0;
        $limitNumber = $cursor + $listLimit;
        // This flag allows to set the verb only if there is a result.
        $first = true;

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT
                    && $reader->name == 'ListRecords'
                    && $reader->getAttribute('metadataPrefix') === $metadataPrefix
                ) {
                // Loop on all records until the one of the identifier (if
                // prefix is not found above, it's bypassed because there is no
                // new element to read.
                while ($reader->read()) {
                    if ($reader->nodeType == XMLReader::ELEMENT
                            && $reader->name === 'oai:record'
                        ) {
                        // Because XMLReader is a stream reader, forward only,
                        // and the identifier is not the first element, it is
                        // saved temporary.
                        $currentRecord = $reader->readOuterXml();
                        $recordXml = @simplexml_load_string($currentRecord, 'SimpleXMLElement', 0, 'oai', true);
                        if ($recordXml !== false) {
                            // Check conditions.
                            $isSet = $set
                                ? (string) $recordXml->header->setSpec === $set
                                : true;
                            $isAfter = $from
                                ? $this->_isDateAfter($from, (string) $recordXml->header->datestamp)
                                : true;
                            $isBefore = $until
                                ? $this->_isDateBefore($until, (string) $recordXml->header->datestamp)
                                : true;

                            if ($isSet && $isAfter && $isBefore) {
                                $recordNumber++;
                                // Check the cursor and the limit for response.
                                if (empty($listLimit) || ($recordNumber > $cursor && $recordNumber <= $limitNumber)) {
                                    // Add the verb to wrap the list of results.
                                    if ($first) {
                                        $first = false;
                                        $writer->startElement($verb);
                                    }

                                    switch ($verb) {
                                        // Add the header only.
                                        case 'ListIdentifiers':
                                            $writer->startElement('header');
                                            $writer->writeElement('identifier', $recordXml->header->identifier);
                                            $writer->writeElement('datestamp', $recordXml->header->datestamp);
                                            if (!empty($recordXml->header->setSpec)) {
                                                $writer->writeElement('setSpec', $recordXml->header->setSpec);
                                            }
                                            $writer->endElement();
                                            break;

                                        // Add the full record.
                                        case 'ListRecords':
                                            // The command below adds the xsi at
                                            // the record level, not under the
                                            // metadata level.
                                            // $this->_writeRawWithoutPrefixOai(false, false, $currentRecord);
                                            $doc = new DOMDocument;
                                            // Furthermore, the dom import of
                                            // the recordXml may produce errors
                                            // in namespaces for the metadata...
                                            // $recordDom = dom_import_simplexml($recordXml);
                                            // $recordDom = $doc->importNode($recordDom, true);
                                            // $recordDom = $doc->appendChild($recordDom);
                                            $doc->loadXML($currentRecord, LIBXML_NSCLEAN);
                                            $recordDom = $doc->childNodes->item(0);
                                            $recordDom->removeAttributeNS('http://www.openarchives.org/OAI/2.0/', 'oai');
                                            $writer->startElement('record');
                                            foreach ($recordDom->childNodes as $child) {
                                                $writer->writeRaw($child->ownerDocument->saveXML($child));
                                            }
                                            $writer->endElement();
                                            break;
                                    }
                                }
                            }
                        }

                        // All records should be processed to get the total.
                        $reader->next();
                    }

                    // Don't continue to list records with another prefix.
                    if ($reader->nodeType == XMLReader::END_ELEMENT
                            && $reader->name == 'ListRecords'
                        ) {
                        break 2;
                    }
                }
            }
        }

        // A true "first" means no record.
        if ($first) {
            $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, 'No records match the given criteria.');
        }
        // Add a token if needed.
        else {
            // No token for a full list.
            if (empty($listLimit)) {
            }
            // Token.
            elseif ($recordNumber > ($cursor + $listLimit)) {
                $token = $this->createResumptionToken($verb,
                                                      $metadataPrefix,
                                                      $cursor + $listLimit,
                                                      $set,
                                                      $from,
                                                      $until);

                $writer->startElement('resumptionToken');
                $writer->writeAttribute('expirationDate', OaiPmhRepository_Date::dbToUtc($token->expiration));
                $writer->writeAttribute('completeListSize', $recordNumber);
                $writer->writeAttribute('cursor', $cursor);
                $writer->text($token->id);
                $writer->endElement();
            }
            // Last token.
            elseif ($cursor != 0) {
                $writer->writeElement('resumptionToken');
            }

            // End ListRecords / ListIdentifiers.
            $writer->endElement();
        }
    }

    /**
     * Compare two GMT dates.
     *
     * @param string $date Date to check.
     * @param string $reference The date to check against.
     * @return boolean True if the date is before the reference, else false.
     */
    protected function _isDateBefore($date, $reference)
    {
         return strtotime($date) < strtotime($reference);
    }

    /**
     * Compare two GMT dates.
     *
     * @param string $date Date to check.
     * @param string $reference The date to check against.
     * @return boolean True if the date is after the reference, else false.
     */
    protected function _isDateAfter($date, $reference)
    {
         return strtotime($date) > strtotime($reference);
    }

    /**
     * Stores a new resumption token record in the database
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @return OaiPmhRepositoryToken Token model object
     */
    private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $tokenTable = get_db()->getTable('OaiPmhRepositoryToken');

        $resumptionToken = new OaiPmhRepositoryToken();
        $resumptionToken->verb = $verb;
        $resumptionToken->metadata_prefix = $metadataPrefix;
        $resumptionToken->cursor = $cursor;
        if($set)
            $resumptionToken->set = $set;
        if($from)
            $resumptionToken->from = $from;
        if($until)
            $resumptionToken->until = $until;
        $resumptionToken->expiration = OaiPmhRepository_Date::unixToDb(
            time() + ($this->_tokenExpirationTime * 60 ) );
        $resumptionToken->save();

        return $resumptionToken;
    }

    /**
     * Builds an array of entries for all managed metadata.
     *
     * @return array A simple list of prefixes.
     */
    private function _getFormats()
    {
        if (is_null($this->metadataFormats)) {
            // Read the xml file from the beginning.
            $reader = new XMLReader;
            $reader->open($this->xmlpath, null, LIBXML_NSCLEAN);
            while ($reader->read() && $reader->name !== 'ListMetadataFormats');
            $xml = $reader->readOuterXml();
            $reader->close();
            // Because this is a part of an xml, the cast to the xml object may
            // produce notices: the prefix "oai:" may or may not be set in each
            // tag.
            $doc = new DOMDocument;
            $doc->loadXML($xml, LIBXML_NSCLEAN);
            $element = $doc->childNodes->item(0);
            $element->removeAttributeNS('http://www.openarchives.org/OAI/2.0/', 'oai');
            $xml = simplexml_import_dom($doc);
            $formats = array();
            foreach ($xml->metadataFormat as $format) {
                $formats[(string) $format->metadataPrefix] = $format->asXML();
            }
            $this->metadataFormats = $formats;
        }
        return $this->metadataFormats;
    }

    /**
     * Get the xml content of a record for the specified prefix.
     *
     * @see ArchiveFolder_Builder::_getRecord()
     *
     * @param string $identifier
     * @param string $prefix
     * @param boolean $cleanPrefixOai Clean the record of the "oai" prefix.
     * @return string|null|boolean The record if found, null if not found, false
     * if error (incorrect format). The error is set if any.
     */
    private function _getRecord($identifier, $metadataPrefix, $cleanPrefixOai = false)
    {
        if (!OaiPmhGateway_OaiIdentifier::checkFormat($identifier)) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
            return false;
        }

        $record = null;

        // Read the xml file from the beginning.
        $reader = new XMLReader;
        $reader->open($this->xmlpath);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT
                    && $reader->name == 'ListRecords'
                    && $reader->getAttribute('metadataPrefix') === $metadataPrefix
                ) {
                // Loop on all records until the one of the identifier (if
                // prefix is not found above, it's bypassed because there is no
                // new element to read.
                while ($reader->read()) {
                    if ($reader->nodeType == XMLReader::ELEMENT
                            && $reader->name === 'oai:record'
                        ) {
                        // Because XMLReader is a stream reader, forward only,
                        // and the identifier is not the first element, it is
                        // saved temporary.
                        $currentRecord = $reader->readOuterXml();
                        $recordXml = @simplexml_load_string($currentRecord, 'SimpleXMLElement', 0, 'oai', true);

                        // Check conditions.
                        if ((string) $recordXml->header->identifier === $identifier) {
                            if ($cleanPrefixOai) {
                                $record = $this->_writeRawWithoutPrefixOai(false, true, $currentRecord);
                            }
                            else {
                                $record = &$currentRecord;
                            }
                            break 2;
                        }
                        $reader->next();
                    }

                    // Don't continue to list records with another prefix.
                    if ($reader->nodeType == XMLReader::END_ELEMENT
                            && $reader->name == 'ListRecords'
                        ) {
                        break 2;
                    }
                }
            }
        }

        $reader->close();
        return $record;
    }

    private function _getParam($param)
    {
        if (array_key_exists($param, $this->query)) {
            return $this->query[$param];
        }
        return null;
    }

    /**
     * The static repository has multiple namespaces, but the main namespace of
     * the response is "oai", so the namespace "oai" is useless and may be
     * removed.
     *
     * @param boolean $inner Keep only the inner of the xml, not the whole root.
     * To read inner directly is possible only when there is one subroot!
     * @param boolean $return Return the result, else write directly to writer.
     * @param string $xml Xml to use instead of the reader.
     * @return null|string
     */
    private function _writeRawWithoutPrefixOai($inner = false, $return = false, $xml = null)
    {
        $writer = $this->document;
        $reader = $this->reader;
        if (is_null($xml)) {
            $xml = $reader->readOuterXml();
        }
        if (empty($xml)) {
            return;
        }

        $doc = new DOMDocument;
        $doc->loadXML($xml, LIBXML_NSCLEAN);
        $element = $doc->childNodes->item(0);
        $element->removeAttributeNS('http://www.openarchives.org/OAI/2.0/', 'oai');
        if ($inner) {
            $output = '';
            foreach ($element->childNodes as $child) {
                $output .= $child->ownerDocument->saveXML($child);
            }
        }
        // Outer.
        else {
            $output = $doc->saveXML($element);
        }

        if ($return) {
            return $output;
        }
        // Else direct write.
        $writer->writeRaw($output);
    }

    /**
     * Outputs the XML response as a string
     *
     * Called once processing is complete to return the XML to the client.
     *
     * @return string the response XML
     */
    public function __toString()
    {
        $writer = $this->document;
        return $writer->outputMemory();
    }

    /**
     * Throws an OAI-PMH error on the given response.
     *
     * @param string $error OAI-PMH error code.
     * @param string $message Optional human-readable error message.
     */
    public function throwError($error, $message = null)
    {
        $writer = $this->document;

        if (!$this->isRequestWritten) {
            $this->_writeRequest(false);
        }

        if (is_null($message)) {
            $message = $this->_oaiErrorMessages[$error];
        }

        $this->error = true;

        $writer->startElement('error');
        $writer->writeAttribute('code', $error);
        $writer->text($message);
        $writer->endElement();
    }
}
