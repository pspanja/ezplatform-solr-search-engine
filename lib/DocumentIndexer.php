<?php

namespace EzSystems\EzPlatformSolrSearchEngine;

use EzSystems\EzPlatformSolrSearchEngine\Values\Block;
use EzSystems\EzPlatformSolrSearchEngine\Values\Document;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;
use SplObjectStorage;

/**
 * Document indexer maps Content items to documents, prepares and indexes
 * mapped documents to the Solr backend.
 */
class DocumentIndexer
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\Gateway
     */
    protected $gateway;

    /**
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
     * Indexes the given array of Content items.
     *
     * @param \eZ\Publish\SPI\Persistence\Content[] $contentItems
     */
    public function bulkIndexContent(array $contentItems)
    {
        $contentBlockGroups = [];
        foreach ($contentItems as $content) {
            $contentBlockGroups[] = $this->mapper->mapContentBlock($content);
        }

        $endpointBlockMap = $this->mapEndpointBlocks($contentBlockGroups);

        $this->prepare($endpointBlockMap);

        foreach ($endpointBlockMap as $endpoint) {
            $this->gateway->bulkIndexDocuments($endpointBlockMap[$endpoint], $endpoint);
        }
    }

    /**
     * Maps blocks in the given $contentBlockGroups by the endpoint target that the
     * block is to be indexed in.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[][] $contentBlockGroups
     *
     * @return \SplObjectStorage
     */
    private function mapEndpointBlocks(array $contentBlockGroups)
    {
        $endpointBlockMap = new SplObjectStorage();
        $translationBlockMap = $this->mapTranslationBlocks($contentBlockGroups);

        foreach ($translationBlockMap as $languageCode => $blocks) {
            $endpoint = $this->endpointResolver->getIndexingTarget($languageCode);

            if ($endpointBlockMap->contains($endpoint)) {
                $endpointBlocks = $endpointBlockMap[$endpoint];
                $blocks = array_merge($blocks, $endpointBlocks);
            }

            $endpointBlockMap->attach($endpoint, $blocks);
        }

        return $endpointBlockMap;
    }

    /**
     * Maps blocks in the given $contentBlockGroups by the block translation language code.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[][] $contentBlockGroups
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Values\Block[][]
     */
    private function mapTranslationBlocks(array $contentBlockGroups)
    {
        $blockMap = [];

        foreach ($contentBlockGroups as $contentBlocks) {
            foreach ($contentBlocks as $block) {
                $blockMap[$block->languageCode][] = $block;
            }
        }

        return $blockMap;
    }

    /**
     * Prepare endpoint to block map, which includes:
     *
     *  - if needed: adding documents to be indexed for dedicated main translations matching
     *  - always: adding fields that indicate document's translation matching placement (regular,
     *    main translations or shared for both)
     *
     * @param \SplObjectStorage $endpointBlockMap
     */
    private function prepare(SplObjectStorage $endpointBlockMap)
    {
        $mainTranslationBlockGroups = [[]];
        $mainTranslationEndpoint = null;
        if ($this->endpointResolver->hasMainLanguagesEndpoint()) {
            $mainTranslationEndpoint = $this->endpointResolver->getMainLanguagesEndpoint();
        }

        foreach ($endpointBlockMap as $endpoint) {
            /** @var \EzSystems\EzPlatformSolrSearchEngine\Values\Block[] $blocks */
            $blocks = $endpointBlockMap[$endpoint];

            $this->addInternalIndexedFields($blocks);

            $sharedPlacement = ($endpoint === $mainTranslationEndpoint);

            $mainTranslationBlockGroups[] = $this->getMainTranslationDedicatedBlocks(
                $blocks,
                $sharedPlacement
            );

            $this->addTranslationCorePlacementFields($blocks, $sharedPlacement);
        }

        $mainTranslationBlocks = array_merge(...$mainTranslationBlockGroups);

        $this->addMainTranslationDedicatedCorePlacementFields($mainTranslationBlocks);

        if (!empty($mainTranslationBlocks)) {
            if ($endpointBlockMap->contains($mainTranslationEndpoint)) {
                $endpointBlocks = $endpointBlockMap[$mainTranslationEndpoint];
                $mainTranslationBlocks = array_merge($mainTranslationBlocks, $endpointBlocks);
            }

            $endpointBlockMap->attach($mainTranslationEndpoint, $mainTranslationBlocks);
        }
    }

    /**
     * Adds internal fields to the given array of $blocks.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[] $blocks
     */
    private function addInternalIndexedFields(array $blocks)
    {
        foreach ($blocks as $block) {
            $blockInternalFields = $this->getBlockInternalFields($block);
            $documentInternalFields = $this->getDocumentInternalFields($block);

            $block->fields = array_merge(
                $block->fields,
                $blockInternalFields,
                $documentInternalFields
            );

            foreach ($block->documents as $document) {
                $documentInternalFields = $this->getDocumentInternalFields($document);

                $document->fields = array_merge(
                    $document->fields,
                    $blockInternalFields,
                    $documentInternalFields
                );
            }
        }
    }

    /**
     * Returns internal fields for the given $document.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Document $document
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getDocumentInternalFields(Document $document)
    {
        return [
            new Field(
                'document_type',
                $document->documentTypeIdentifier,
                new FieldType\IdentifierField()
            ),
        ];
    }

    /**
     * Returns internal fields for the given $block.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block $block
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getBlockInternalFields(Block $block)
    {
        return [
            new Field(
                'meta_indexed_language_code',
                $block->languageCode,
                new FieldType\StringField()
            ),
            new Field(
                'meta_indexed_is_main_translation',
                $block->isMainTranslation,
                new FieldType\BooleanField()
            ),
            new Field(
                'meta_indexed_is_main_translation_and_always_available',
                $block->alwaysAvailable,
                new FieldType\BooleanField()
            ),
        ];
    }

    /**
     * For the given array of $blocks, return an array of blocks to index as dedicated
     * main translations blocks.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[] $blocks
     * @param bool $sharedPlacement
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Values\Block[]
     */
    private function getMainTranslationDedicatedBlocks(array $blocks, $sharedPlacement)
    {
        $mainTranslationBlocks = [];

        if (!$this->endpointResolver->hasMainLanguagesEndpoint()) {
            return $mainTranslationBlocks;
        }

        foreach ($blocks as $block) {
            if ($block->isMainTranslation && !$sharedPlacement) {
                $mainTranslationBlocks[] = $this->getMainTranslationBlock($block);
            }
        }

        return $mainTranslationBlocks;
    }

    /**
     * Add translation matching fields to the given array of $blocks.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[] $blocks
     * @param bool $sharedPlacement
     */
    private function addTranslationCorePlacementFields(array $blocks, $sharedPlacement)
    {
        foreach ($blocks as $block) {
            $internalFields = $this->getTranslationCorePlacementFields(
                true,
                $block->isMainTranslation && $sharedPlacement
            );
            $block->fields = array_merge($block->fields, $internalFields);

            foreach ($block->documents as $document) {
                $document->fields = array_merge($document->fields, $internalFields);
            }
        }
    }

    /**
     * Add dedicated main translations matching fields to the given array of $blocks.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block[] $blocks
     */
    private function addMainTranslationDedicatedCorePlacementFields(array $blocks)
    {
        $internalFields = $this->getTranslationCorePlacementFields(false, true);

        foreach ($blocks as $block) {
            $block->fields = array_merge($block->fields, $internalFields);

            foreach ($block->documents as $document) {
                $document->fields = array_merge($document->fields, $internalFields);
            }
        }
    }

    /**
     * Return document fields used for translation matching through core placement.
     *
     * Two fields are returned:
     *
     *  - first field indicates if the document is matched for regular translations
     *  - second field indicates if the document is matched for main translations
     *
     * Together, three combinations are valid:
     *
     *  1. matching only for regular translations (true, false)
     *  2. matching only for main translations (false, true)
     *  3. shared - matching for both regular and main translations (true, true)
     *
     * Fourth combination (false, false) never happens.
     *
     * @param bool $regularTranslation
     * @param bool $mainTranslation
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    private function getTranslationCorePlacementFields($regularTranslation, $mainTranslation)
    {
        return [
            new Field(
                'meta_indexed_translation',
                $regularTranslation,
                new FieldType\BooleanField()
            ),
            new Field(
                'meta_indexed_main_translation',
                $mainTranslation,
                new FieldType\BooleanField()
            )
        ];
    }

    /**
     * For the given $block return a block to index as a dedicated main translation block.
     *
     * This will just clone the given block and change document ids to resolve conflicts.
     *
     * @param \EzSystems\EzPlatformSolrSearchEngine\Values\Block $block
     *
     * @return \EzSystems\EzPlatformSolrSearchEngine\Values\Block
     */
    private function getMainTranslationBlock(Block $block)
    {
        $block = clone $block;

        $block->id .= 'mt';

        foreach ($block->documents as $document) {
            $document->id .= 'mt';
        }

        return $block;
    }
}
