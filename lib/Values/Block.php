<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values;

/**
 * Block represents a document containing nested documents.
 */
class Block extends Document
{
    /**
     * Translation language code of the Content that document represents.
     *
     * @var string
     */
    public $languageCode;

    /**
     * Indicates that document's translation is always available main translation
     * of the Content.
     *
     * @var bool
     */
    public $alwaysAvailable;

    /**
     * Indicates that document's translation is a main translation of the Content.
     *
     * @var bool
     */
    public $isMainTranslation;

    /**
     * An array of nested documents.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\Values\Document[]
     */
    public $documents = [];

    public function __clone()
    {
        foreach ($this->documents as &$document) {
            $document = clone $document;
        }
    }
}
