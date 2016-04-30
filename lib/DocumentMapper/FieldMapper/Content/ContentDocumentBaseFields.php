<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content;

use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper\FieldMapper\Content as ContentMapper;
use EzSystems\EzPlatformSolrSearchEngine\DocumentMapper;
use eZ\Publish\SPI\Persistence\Content;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;

/**
 * Maps base Content related fields to a Content document.
 */
class ContentDocumentBaseFields extends ContentMapper
{
    public function accept(Content $content)
    {
        return true;
    }

    public function mapFields(Content $content)
    {
        return [
            new Field(
                'document_type',
                DocumentMapper::DOCUMENT_TYPE_IDENTIFIER_CONTENT,
                new FieldType\IdentifierField()
            ),
        ];
    }
}
