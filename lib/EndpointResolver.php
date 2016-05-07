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

/**
 * Endpoint resolver resolves Solr backend endpoints.
 */
interface EndpointResolver
{
    /**
     * Returns name of the Endpoint used as entry point for distributed search.
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Endpoint
     */
    public function getEntryEndpoint();

    /**
     * Returns name of the Endpoint that indexes Content translations in the given $languageCode.
     *
     * @param string $languageCode
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Endpoint
     */
    public function getIndexingTarget($languageCode);

    /**
     * Checks if the Endpoint used to index translations in main languages exists.
     *
     * @return bool
     */
    public function hasMainLanguagesEndpoint();

    /**
     * Returns name of the Endpoint used to index translations in main languages.
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Endpoint
     */
    public function getMainLanguagesEndpoint();

    /**
     * Returns an array of Endpoint names for the given $languageSettings.
     *
     * @param array $languageSettings
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Endpoint[]
     */
    public function getSearchTargets(array $languageSettings);

    /**
     * Returns names of all Endpoints.
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Endpoint[]
     */
    public function getEndpoints();
}
