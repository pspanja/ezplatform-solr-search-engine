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
use SplObjectStorage;

/**
 */
class DocumentIndexer
{
    /**
     * Content locator gateway.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\Gateway
     */
    protected $gateway;

    /**
     * Document mapper.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper
     */
    protected $mapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\EndpointResolver
     */
    protected $endpointResolver;

    /**
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway $gateway
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper $mapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\EndpointResolver $endpointResolver
     */
    public function __construct(
        Gateway $gateway,
        DocumentMapper $mapper,
        EndpointResolver $endpointResolver
    ) {
        $this->gateway = $gateway;
        $this->mapper = $mapper;
        $this->endpointResolver = $endpointResolver;
    }

    /**
     * @param \eZ\Publish\SPI\Persistence\Content[] $contentObjects
     */
    public function bulkIndexContent(array $contentObjects)
    {
        $documents = [];

        foreach ($contentObjects as $content) {
            $documents[] = $this->mapper->mapContentBlock($content);
        }

        $mainTranslationsDocuments = $this->getMainTranslationCoreDocuments($documents);
        $endpointDocumentMap = $this->getEndpointDocumentMap($documents, $mainTranslationsDocuments);

        foreach ($endpointDocumentMap as $endpoint) {
            $this->gateway->bulkIndexDocuments($endpointDocumentMap[$endpoint], $endpoint);
        }
    }

    /**
     * @param array $documents
     * @param array $mainTranslationsDocuments
     *
     * @return \SplObjectStorage
     */
    private function getEndpointDocumentMap(array $documents, array $mainTranslationsDocuments)
    {
        $translationDocumentMap = $this->getTranslationDocumentMap($documents);
        $endpointDocumentMap = new SplObjectStorage();

        foreach ($translationDocumentMap as $languageCode => $translationDocuments) {
            $languageTarget = $this->endpointResolver->getIndexingTarget($languageCode);

            if ($endpointDocumentMap->contains($languageTarget)) {
                $existingTranslationDocuments = $endpointDocumentMap[$languageTarget];
                $translationDocuments = array_merge($translationDocuments, $existingTranslationDocuments);
            }

            $endpointDocumentMap->attach($languageTarget, $translationDocuments);
        }

        if (!empty($mainTranslationsDocuments)) {
            $mainTranslationsEndpoint = $this->endpointResolver->getMainLanguagesEndpoint();

            if ($endpointDocumentMap->contains($mainTranslationsEndpoint)) {
                $existingDocuments = $endpointDocumentMap[$mainTranslationsEndpoint];
                $mainTranslationsDocuments = array_merge($mainTranslationsDocuments, $existingDocuments);
            }

            $endpointDocumentMap->attach($mainTranslationsEndpoint, $mainTranslationsDocuments);
        }

        return $endpointDocumentMap;
    }

    /**
     * @param array $documents
     *
     * @return array
     */
    private function getTranslationDocumentMap(array $documents)
    {
        $documentMap = [];

        foreach ($documents as $translationDocuments) {
            foreach ($translationDocuments as $document) {
                $documentMap[$document->languageCode][] = $document;
            }
        }

        return $documentMap;
    }

    /**
     * @param array $documents
     *
     * @return array
     */
    private function getMainTranslationCoreDocuments(array $documents)
    {
        $mainTranslationsDocuments = [];

        foreach ($documents as $translationDocuments) {
            foreach ($translationDocuments as $document) {
                if ($this->endpointResolver->hasMainLanguagesEndpoint() && $document->isMainTranslation) {
                    $mainTranslationsDocuments[] = $this->mapper->getMainTranslationDocument($document);
                }
            }
        }

        return $mainTranslationsDocuments;
    }
}
