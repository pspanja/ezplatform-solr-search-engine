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
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $entryEndpoint
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    abstract public function findContent(
        Query $query,
        Endpoint $entryEndpoint,
        array $targetEndpoints
    );

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $entryEndpoint
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    abstract public function findLocations(
        Query $query,
        Endpoint $entryEndpoint,
        array $targetEndpoints
    );

    /**
     * Indexes given $documents in the given $endpoint.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Document[] $documents
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $endpoint
     */
    abstract public function bulkIndexDocuments(array $documents, Endpoint $endpoint);

    /**
     * Deletes documents by the given $query.
     *
     * @param string $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $endpoints
     */
    abstract public function deleteByQuery($query, array $endpoints);

    /**
     * Purges all contents from the index.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $endpoints
     */
    abstract public function purgeIndex(array $endpoints);

    /**
     * Commits the data to the Solr index, making it available for search.
     *
     * This will perform Solr 'soft commit', which means there is no guarantee that data
     * is actually written to the stable storage, it is only made available for search.
     * Passing true will also write the data to the safe storage, ensuring durability.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $endpoints
     * @param bool $flush
     */
    abstract public function commit(array $endpoints, $flush = false);
}
