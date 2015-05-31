<?php
/**
 * Utility class for dealing with OAI identifiers.
 *
 * OaiPmhGateway_OaiIdentifier represents an instance of a unique identifier
 * for the repository conforming to the oai-identifier recommendation.
 *
 * Because this is a gateway, the scheme "oai" can be a requirement, and this
 * is required for this gateway.
 *
 * @package OaiPmhGateway
 */
class OaiPmhGateway_OaiIdentifier
{
    const OAI_SCHEME = 'oai';
    const OAI_IDENTIFIER_NAMESPACE_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier';
    const OAI_IDENTIFIER_SCHEMA_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier.xsd';

    private static $namespaceId;

    public static function initializeNamespace($namespaceId)
    {
        self::$namespaceId = $namespaceId;
    }

    /**
     * Check the format of  the given OAI identifier.
     *
     * @param string $oaiId OAI identifier.
     * @return boolean
     */
    public static function checkFormat($oaiId)
    {
        $scheme = strtok($oaiId, ':');
        $namespaceId = strtok(':');
        // All the end of the string is the identifier.
        $localId = strtok('');
        return $scheme == self::OAI_SCHEME
            && $namespaceId == self::$namespaceId
            && !empty($localId);
    }

    /**
     * Outputs description element child describing the repository's OAI
     * identifier implementation.
     *
     * @internal The format of the identifier should have been checked before
     * and the scheme should be "oai".
     *
     * @param XMLWriter $writer Xml writer for XML output.
     * @param string $identifier Sample identifier.
     */
    public static function describeIdentifier($writer, $identifier)
    {
        $writer->startElement('oai-identifier');
        $writer->writeAttribute('xmlns', self::OAI_IDENTIFIER_NAMESPACE_URI);
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation',
            self::OAI_IDENTIFIER_NAMESPACE_URI . ' ' . self::OAI_IDENTIFIER_SCHEMA_URI);

        $writer->writeElement('scheme', self::OAI_SCHEME);
        $writer->writeElement('repositoryIdentifier', self::$namespaceId);
        $writer->writeElement('delimiter', ':');
        $writer->writeElement('sampleIdentifier', $identifier);

        $writer->endElement();
   }
}
