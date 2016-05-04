<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine;

use EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint;
use eZ\Publish\API\Repository\Values\Content\Query;

/**
 * The Content Search Gateway provides the implementation for one database to
 * retrieve the desired content objects.
 */
abstract class Gateway
{
    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    abstract public function findContent(Query $query, array $targetEndpoints);

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    abstract public function findLocations(Query $query, array $targetEndpoints);

    /**
     * Indexes an array of documents.
     *
     * Documents are given as an array of the array of documents. The array of documents
     * holds documents for all translations of the particular entity.
     *
     * @param \eZ\Publish\SPI\Search\Document[] $documents
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint $endpoint
     */
    abstract public function bulkIndexDocuments(array $documents, Endpoint $endpoint);

    /**
     * Deletes documents by the given $query.
     *
     * @param string $query
     */
    abstract public function deleteByQuery($query);

    /**
     * Purges all contents from the index.
     */
    abstract public function purgeIndex();

    /**
     * Commits the data to the Solr index, making it available for search.
     *
     * This will perform Solr 'soft commit', which means there is no guarantee that data
     * is actually written to the stable storage, it is only made available for search.
     * Passing true will also write the data to the safe storage, ensuring durability.
     *
     * @param bool $flush
     */
    abstract public function commit($flush = false);
}
