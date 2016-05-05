<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\EndpointResolver;

use EzSystems\EzPlatformSolrSearchEngine\EndpointRegistry;
use EzSystems\EzPlatformSolrSearchEngine\EndpointResolver;
use RuntimeException;

/**
 * NativeEndpointResolver provides Solr endpoints for Content translations.
 */
class NativeEndpointResolver implements EndpointResolver
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\EndpointRegistry
     */
    private $endpointRegistry;

    /**
     * Holds an array of Solr entry endpoint names.
     *
     * @var string[]
     */
    private $entryEndpoints;

    /**
     * Holds a map of translations to Endpoint names, with language code as key
     * and Endpoint name as value.
     *
     * <code>
     *  array(
     *      "cro-HR" => "endpoint1",
     *      "eng-GB" => "endpoint2",
     *  );
     * </code>
     *
     * @var string[]
     */
    private $endpointMap;

    /**
     * Holds a name of the default Endpoint used for translations, if configured.
     *
     * @var null|string
     */
    private $defaultEndpoint;

    /**
     * Holds a name of the Endpoint used to index translations in main languages, if configured.
     *
     * @var null|string
     */
    private $mainLanguagesEndpoint;

    /**
     * Create from Endpoint names.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\EndpointRegistry $endpointRegistry
     * @param string[] $entryEndpoints
     * @param string[] $endpointMap
     * @param null|string $defaultEndpoint
     * @param null|string $mainLanguagesEndpoint
     */
    public function __construct(
        EndpointRegistry $endpointRegistry,
        array $entryEndpoints = array(),
        array $endpointMap = array(),
        $defaultEndpoint = null,
        $mainLanguagesEndpoint = null
    ) {
        $this->entryEndpoints = $entryEndpoints;
        $this->endpointMap = $endpointMap;
        $this->defaultEndpoint = $defaultEndpoint;
        $this->mainLanguagesEndpoint = $mainLanguagesEndpoint;
        $this->endpointRegistry = $endpointRegistry;
    }

    public function getEntryEndpoint()
    {
        if (empty($this->entryEndpoints)) {
            throw new RuntimeException('Not entry endpoints defined');
        }

        return $this->endpointRegistry->getEndpoint(
            reset($this->entryEndpoints)
        );
    }

    public function getIndexingTarget($languageCode)
    {
        if (isset($this->endpointMap[$languageCode])) {
            return $this->endpointRegistry->getEndpoint(
                $this->endpointMap[$languageCode]
            );
        }

        if (isset($this->defaultEndpoint)) {
            return $this->endpointRegistry->getEndpoint(
                $this->defaultEndpoint
            );
        }

        throw new RuntimeException(
            "Language '{$languageCode}' is not mapped to Solr endpoint"
        );
    }

    public function getMainLanguagesEndpoint()
    {
        if ($this->mainLanguagesEndpoint === null) {
            return null;
        }

        return $this->endpointRegistry->getEndpoint(
            $this->mainLanguagesEndpoint
        );
    }

    public function getSearchTargets(array $languageSettings)
    {
        $languages = (
            empty($languageSettings['languages']) ?
                array() :
                $languageSettings['languages']
        );
        $useAlwaysAvailable = (
            !isset($languageSettings['useAlwaysAvailable']) ||
            $languageSettings['useAlwaysAvailable'] === true
        );

        if (($useAlwaysAvailable || empty($languages)) && !isset($this->mainLanguagesEndpoint)) {
            return $this->getEndpoints();
        }

        $targetSet = array();

        foreach ($languages as $languageCode) {
            if (isset($this->endpointMap[$languageCode])) {
                $targetSet[$this->endpointMap[$languageCode]] = true;
            } elseif (isset($this->defaultEndpoint)) {
                $targetSet[$this->defaultEndpoint] = true;
            } else {
                throw new RuntimeException(
                    "Language '{$languageCode}' is not mapped to Solr endpoint"
                );
            }
        }

        if (($useAlwaysAvailable || empty($targetSet)) && isset($this->mainLanguagesEndpoint)) {
            $targetSet[$this->mainLanguagesEndpoint] = true;
        }

        if (empty($targetSet)) {
            throw new RuntimeException('No endpoints defined for given language settings');
        }

        return $this->internalGetEndpoints(array_keys($targetSet));
    }

    public function getEndpoints()
    {
        $endpointSet = array_flip($this->endpointMap);

        if (isset($this->defaultEndpoint)) {
            $endpointSet[$this->defaultEndpoint] = true;
        }

        if (isset($this->mainLanguagesEndpoint)) {
            $endpointSet[$this->mainLanguagesEndpoint] = true;
        }

        if (empty($endpointSet)) {
            throw new RuntimeException('No endpoints defined');
        }

        return $this->internalGetEndpoints(array_keys($endpointSet));
    }

    private function internalGetEndpoints(array $endpointNames)
    {
        $endpoints = [];

        foreach ($endpointNames as $endpointName) {
            $endpoints[] = $this->endpointRegistry->getEndpoint($endpointName);
        }

        return $endpoints;
    }
}
