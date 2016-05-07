<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation;

use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\ContentTranslation;
use eZ\Publish\SPI\Persistence\Content;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;

/**
 * Maps meta fields to block documents (Content and Location).
 */
class BlockDocumentsMetaFields extends ContentTranslation
{
    public function accept(Content $content, $languageCode)
    {
        return true;
    }

    public function mapFields(Content $content, $languageCode)
    {
        $isMainTranslation = $languageCode === $content->versionInfo->contentInfo->mainLanguageCode;

        return [
            new Field(
                'meta_indexed_language_code',
                $languageCode,
                new FieldType\StringField()
            ),
            new Field(
                'meta_indexed_is_main_translation',
                $isMainTranslation,
                new FieldType\BooleanField()
            ),
            new Field(
                'meta_indexed_is_main_translation_and_always_available',
                $isMainTranslation && $content->versionInfo->contentInfo->alwaysAvailable,
                new FieldType\BooleanField()
            ),
        ];
    }
}
