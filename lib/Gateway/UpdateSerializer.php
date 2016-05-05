<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Gateway;

use EzSystems\EzPlatformSolrSearchEngine\FieldValueMapper;
use eZ\Publish\Core\Search\Common\FieldNameGenerator;
use eZ\Publish\SPI\Search\Document;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;
use XMLWriter;

/**
 * Update serializer converts an array of documents to XML string that
 * can be posted to Solr backend for indexing.
 */
class UpdateSerializer
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\FieldValueMapper
     */
    protected $fieldValueMapper;

    /**
     * @var \eZ\Publish\Core\Search\Common\FieldNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param \EzSystems\EzPlatformSolrSearchEngine\FieldValueMapper $fieldValueMapper
     * @param \eZ\Publish\Core\Search\Common\FieldNameGenerator $nameGenerator
     */
    public function __construct(
        FieldValueMapper $fieldValueMapper,
        FieldNameGenerator $nameGenerator
    ) {
        $this->fieldValueMapper = $fieldValueMapper;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Create update XML for the given array of $documents.
     *
     * @param \eZ\Publish\SPI\Search\Document[] $documents
     *
     * @return string
     */
    public function serialize(array $documents)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startElement('add');

        foreach ($documents as $document) {
            $this->writeDocument($xmlWriter, $document);
        }

        $xmlWriter->endElement();

        return $xmlWriter->outputMemory(true);
    }

    protected function writeDocument(XMLWriter $xmlWriter, Document $document)
    {
        $xmlWriter->startElement('doc');

        $this->writeField(
            $xmlWriter,
            new Field(
                'id',
                $document->id,
                new FieldType\IdentifierField()
            )
        );

        foreach ($document->fields as $field) {
            $this->writeField($xmlWriter, $field);
        }

        foreach ($document->documents as $subDocument) {
            $this->writeDocument($xmlWriter, $subDocument);
        }

        $xmlWriter->endElement();
    }

    protected function writeField(XMLWriter $xmlWriter, Field $field)
    {
        foreach ((array)$this->fieldValueMapper->map($field) as $value) {
            $xmlWriter->startElement('field');
            $xmlWriter->writeAttribute(
                'name',
                $this->nameGenerator->getTypedName($field->name, $field->type)
            );
            $xmlWriter->text($value);
            $xmlWriter->endElement();
        }
    }
}
