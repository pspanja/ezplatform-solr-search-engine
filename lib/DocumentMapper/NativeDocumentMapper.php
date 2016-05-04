<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\DocumentMapper;

use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content as ContentFieldMapper;
use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation as ContentTranslationFieldMapper;
use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Location as LocationFieldMapper;
use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\LocationTranslation as LocationTranslationFieldMapper;
use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper;
use eZ\Publish\SPI\Persistence\Content;
use eZ\Publish\SPI\Persistence\Content\Location;
use eZ\Publish\SPI\Persistence\Content\Location\Handler as LocationHandler;
use eZ\Publish\SPI\Search\Document;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;

/**
 * NativeDocumentMapper maps Solr backend documents per Content translation.
 */
class NativeDocumentMapper implements DocumentMapper
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content
     */
    private $blockFieldMapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation
     */
    private $blockTranslationFieldMapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content
     */
    private $contentFieldMapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation
     */
    private $contentTranslationFieldMapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Location
     */
    private $locationFieldMapper;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\LocationTranslation
     */
    private $locationTranslationFieldMapper;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Location\Handler
     */
    private $locationHandler;

    /**
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content $blockFieldMapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation $blockTranslationFieldMapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content $contentFieldMapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation $contentTranslationFieldMapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Location $locationFieldMapper
     * @param \EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\LocationTranslation $locationTranslationFieldMapper
     * @param \eZ\Publish\SPI\Persistence\Content\Location\Handler $locationHandler
     */
    public function __construct(
        ContentFieldMapper $blockFieldMapper,
        ContentTranslationFieldMapper $blockTranslationFieldMapper,
        ContentFieldMapper $contentFieldMapper,
        ContentTranslationFieldMapper $contentTranslationFieldMapper,
        LocationFieldMapper $locationFieldMapper,
        LocationTranslationFieldMapper $locationTranslationFieldMapper,
        LocationHandler $locationHandler
    ) {
        $this->blockFieldMapper = $blockFieldMapper;
        $this->blockTranslationFieldMapper = $blockTranslationFieldMapper;
        $this->contentFieldMapper = $contentFieldMapper;
        $this->contentTranslationFieldMapper = $contentTranslationFieldMapper;
        $this->locationFieldMapper = $locationFieldMapper;
        $this->locationTranslationFieldMapper = $locationTranslationFieldMapper;
        $this->locationHandler = $locationHandler;
    }

    public function mapContentBlock(Content $content)
    {
        $contentInfo = $content->versionInfo->contentInfo;
        $locations = $this->locationHandler->loadLocationsByContent($contentInfo->id);
        $blockFields = $this->getBlockFields($content);
        $contentFields = $this->getContentFields($content);
        $documents = [];
        $locationFieldsMap = [];

        foreach ($locations as $location) {
            $locationFieldsMap[$location->id] = $this->getLocationFields($location);
        }

        foreach (array_keys($content->versionInfo->names) as $languageCode) {
            $blockTranslationFields = $this->getBlockTranslationFields($content, $languageCode);

            $translationLocationDocuments = array();
            foreach ($locations as $location) {
                $locationTranslationFields = $this->getLocationTranslationFields($location, $languageCode);

                $translationLocationDocuments[] = new Document(
                    array(
                        'id' => $this->generateLocationDocumentId($location->id, $languageCode),
                        'fields' => array_merge(
                            $blockFields,
                            $locationFieldsMap[$location->id],
                            $blockTranslationFields,
                            $locationTranslationFields
                        ),
                    )
                );
            }

            $isMainTranslation = ($contentInfo->mainLanguageCode === $languageCode);
            $alwaysAvailable = ($isMainTranslation && $contentInfo->alwaysAvailable);
            $contentTranslationFields = $this->getContentTranslationFields($content, $languageCode);

            $documents[] = new Document(
                array(
                    'id' => $this->generateContentDocumentId($contentInfo->id, $languageCode),
                    'languageCode' => $languageCode,
                    'alwaysAvailable' => $alwaysAvailable,
                    'isMainTranslation' => $isMainTranslation,
                    'fields' => array_merge(
                        $blockFields,
                        $contentFields,
                        $blockTranslationFields,
                        $contentTranslationFields
                    ),
                    'documents' => $translationLocationDocuments,
                )
            );
        }

        return $documents;
    }

    public function generateContentDocumentId($contentId, $languageCode = null)
    {
        return strtolower("content{$contentId}{$languageCode}");
    }

    public function generateLocationDocumentId($locationId, $languageCode = null)
    {
        return strtolower("location{$locationId}{$languageCode}");
    }

    public function getMainTranslationDocument(Document $document)
    {
        // Clone to prevent mutation
        $document = clone $document;
        $subDocuments = array();

        $document->id .= 'mt';
        $document->fields[] = new Field(
            'meta_indexed_main_translation',
            true,
            new FieldType\BooleanField()
        );

        foreach ($document->documents as $subDocument) {
            // Clone to prevent mutation
            $subDocument = clone $subDocument;

            $subDocument->id .= 'mt';
            $subDocument->fields[] = new Field(
                'meta_indexed_main_translation',
                true,
                new FieldType\BooleanField()
            );

            $subDocuments[] = $subDocument;
        }

        $document->documents = $subDocuments;

        return $document;
    }

    /**
     * Returns an array of fields for the given $content, to be added to the
     * corresponding block documents.
     *
     * @param \eZ\Publish\SPI\Persistence\Content $content
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getBlockFields(Content $content)
    {
        $fields = [];

        if ($this->blockFieldMapper->accept($content)) {
            $fields = $this->blockFieldMapper->mapFields($content);
        }

        return $fields;
    }

    /**
     * Returns an array of fields for the given $content and $languageCode, to be added to the
     * corresponding block documents.
     *
     * @param \eZ\Publish\SPI\Persistence\Content $content
     * @param string $languageCode
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getBlockTranslationFields(Content $content, $languageCode)
    {
        $fields = [];

        if ($this->blockTranslationFieldMapper->accept($content, $languageCode)) {
            $fields = $this->blockTranslationFieldMapper->mapFields($content, $languageCode);
        }

        return $fields;
    }

    /**
     * Returns an array of fields for the given $content, to be added to the corresponding
     * Content document.
     *
     * @param \eZ\Publish\SPI\Persistence\Content $content
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getContentFields(Content $content)
    {
        $fields = [];

        if ($this->contentFieldMapper->accept($content)) {
            $fields = $this->contentFieldMapper->mapFields($content);
        }

        return $fields;
    }

    /**
     * Returns an array of fields for the given $content and $languageCode, to be added to the
     * corresponding Content document.
     *
     * @param \eZ\Publish\SPI\Persistence\Content $content
     * @param string $languageCode
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getContentTranslationFields(Content $content, $languageCode)
    {
        $fields = [];

        if ($this->contentTranslationFieldMapper->accept($content, $languageCode)) {
            $fields = $this->contentTranslationFieldMapper->mapFields($content, $languageCode);
        }

        return $fields;
    }

    /**
     * Returns an array of fields for the given $location, to be added to the corresponding
     * Location document.
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Location $location
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getLocationFields(Location $location)
    {
        $fields = [];

        if ($this->locationFieldMapper->accept($location)) {
            $fields = $this->locationFieldMapper->mapFields($location);
        }

        return $fields;
    }

    /**
     * Returns an array of fields for the given $location and $languageCode, to be added to
     * the corresponding Location document.
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Location $location
     * @param string $languageCode
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getLocationTranslationFields(Location $location, $languageCode)
    {
        $fields = [];

        if ($this->locationTranslationFieldMapper->accept($location, $languageCode)) {
            $fields = $this->locationTranslationFieldMapper->mapFields($location, $languageCode);
        }

        return $fields;
    }
}
