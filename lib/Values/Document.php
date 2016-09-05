<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values;

use eZ\Publish\SPI\Search\Document as SPIDocument;

/**
 * Base class for documents.
 */
class Document extends SPIDocument
{
    /**
     * Identifier of the document's type (content or location).
     *
     * @var string
     */
    public $documentTypeIdentifier;
}
