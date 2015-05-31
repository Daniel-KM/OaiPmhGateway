<?php

/**
 * Model class for a OAI-PMH Gateway table.
 *
 * @package OaiPmhGateway
 */
class Table_OaiPmhGateway extends Omeka_Db_Table
{
    /**
     * Retrieve a repository by its url, that should be unique without protocol.
     *
     * @uses Omeka_Db_Table::getSelectForFindBy()
     * @param string $url The url to search. The extra part are removed.
     * @return OaiPmhGateway|null The existing repository or null.
     */
    public function findByUrl($url)
    {
        // Quick clean of the url in case of a direct input, because anyway,
        // there will be no result.
        $parsed = parse_url(rtrim(trim($url), '/.'));
        if (empty($parsed['scheme']) || empty($parsed['host']) || empty($parsed['path'])) {
            return null;
        }
        if (!in_array($parsed['scheme'], array('http', 'https'))) {
            return null;
        }
        // No more check, because the check is  done against the existing ones.

        // The colon of the port should be url encoded.
        $repository = $parsed['host'] . (empty($parsed['port']) ? '' : ':' . $parsed['port']) . $parsed['path'];

        return $this->findByRepository($repository);
    }

    /**
     * Retrieve a repository by its short url, that should be unique.
     *
     * @internal The separator of the port (colon) is always url decoded
     * ("%3A" => ":") before this process, by the server or by Zend.
     *
     * @uses Omeka_Db_Table::getSelectForFindBy()
     * @param string $repository The static repository url without protocol.
     * @return OaiPmhGateway|null The existing repository or null.
     */
    public function findByRepository($repository)
    {
        $alias = $this->getTableAlias();
        $select = $this->getSelect();
        $select
            ->where("`$alias`.`url` = ?", 'http://' . $repository)
            ->orWhere("`$alias`.`url` = ?", 'https://' . $repository);
        return $this->fetchObject($select);
    }

    /**
     * Returns list of friends (accessible repositories for this gateway).
     *
     * @uses Omeka_Db_Table::getSelectForFindBy()
     *
     * @param boolean $publicOnly
     * @return array List of friends of the gateway.
     */
    public function getFriends($publicOnly = true)
    {
        $args = array(
            'friend' => true,
        );

        if ($publicOnly) {
            $args['public'] = true;
        }

        return $this->findBy($args);
    }

    /**
     * @param Omeka_Db_Select
     * @param array
     * @return void
     */
    public function applySearchFilters($select, $params)
    {
        $alias = $this->getTableAlias();
        $boolean = new Omeka_Filter_Boolean;
        $genericParams = array();
        foreach ($params as $key => $value) {
            if ($value === null || (is_string($value) && trim($value) == '')) {
                continue;
            }
            switch ($key) {
                case 'owner_id':
                    $this->filterByUser($select, $value, 'owner_id');
                    break;
                default:
                    $genericParams[$key] = $value;
            }
        }

        if (!empty($genericParams)) {
            parent::applySearchFilters($select, $genericParams);
        }
    }
}
