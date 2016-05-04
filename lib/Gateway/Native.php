<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\Gateway;

use EzSystems\EzPlatformSolrSearchEngine\Gateway;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\Search\Common\FieldNameGenerator;
use EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter;
use EzSystems\EzPlatformSolrSearchEngine\FieldValueMapper;
use RuntimeException;
use XMLWriter;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\Document;
use eZ\Publish\SPI\Search\FieldType;

/**
 * The Content Search Gateway provides the implementation for one database to
 * retrieve the desired content objects.
 */
class Native extends Gateway
{
    /**
     * HTTP client to communicate with Solr server.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointResolver
     */
    protected $endpointResolver;

    /**
     * Endpoint registry service.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointRegistry
     */
    protected $endpointRegistry;

    /**
     * Content Query converter.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter
     */
    protected $contentQueryConverter;

    /**
     * Location Query converter.
     *
     * @var \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter
     */
    protected $locationQueryConverter;

    /**
     * Field value mapper.
     *
     * @var FieldValueMapper
     */
    protected $fieldValueMapper;

    /**
     * Field name generator.
     *
     * @var FieldNameGenerator
     */
    protected $nameGenerator;

    /**
     * Construct from HTTP client.
     *
     * @param HttpClient $client
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointResolver $endpointResolver
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointRegistry $endpointRegistry
     * @param \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter $contentQueryConverter
     * @param \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter $locationQueryConverter
     * @param FieldValueMapper $fieldValueMapper
     * @param FieldNameGenerator $nameGenerator
     */
    public function __construct(
        HttpClient $client,
        EndpointResolver $endpointResolver,
        EndpointRegistry $endpointRegistry,
        QueryConverter $contentQueryConverter,
        QueryConverter $locationQueryConverter,
        FieldValueMapper $fieldValueMapper,
        FieldNameGenerator $nameGenerator
    ) {
        $this->client = $client;
        $this->endpointResolver = $endpointResolver;
        $this->endpointRegistry = $endpointRegistry;
        $this->contentQueryConverter = $contentQueryConverter;
        $this->locationQueryConverter = $locationQueryConverter;
        $this->fieldValueMapper = $fieldValueMapper;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    public function findContent(Query $query, array $targetEndpoints)
    {
        $parameters = $this->contentQueryConverter->convert($query, $targetEndpoints);

        return $this->internalFind($parameters);
    }

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    public function findLocations(Query $query, array $targetEndpoints)
    {
        $parameters = $this->locationQueryConverter->convert($query, $targetEndpoints);

        return $this->internalFind($parameters);
    }

    /**
     * Returns search hits for the given array of Solr query parameters.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    protected function internalFind(array $parameters)
    {
        $queryString = $this->generateQueryString($parameters);

        $response = $this->client->request(
            'GET',
            $this->endpointRegistry->getEndpoint(
                $this->endpointResolver->getEntryEndpoint()
            ),
            "/select?{$queryString}"
        );

        // @todo: Error handling?
        $result = json_decode($response->body);

        if (!isset($result->response)) {
            throw new RuntimeException(
                '->response not set: ' . var_export(array($result, $parameters), true)
            );
        }

        return $result;
    }

    /**
     * Generate URL-encoded query string.
     *
     * Array markers, possibly added for the facet parameters,
     * will be removed from the result.
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function generateQueryString(array $parameters)
    {
        return preg_replace(
            '/%5B[0-9]+%5D=/',
            '=',
            http_build_query($parameters)
        );
    }

    public function bulkIndexDocuments(array $documents, Endpoint $endpoint)
    {
        $updates = $this->createUpdates($documents);
        $result = $this->client->request(
            'POST',
            $endpoint,
            '/update?wt=json',
            new Message(
                array(
                    'Content-Type' => 'text/xml',
                ),
                $updates
            )
        );

        if ($result->headers['status'] !== 200) {
            throw new RuntimeException(
                'Wrong HTTP status received from Solr: ' . $result->headers['status'] . var_export(array($result, $updates), true)
            );
        }
    }

    /**
     * Deletes documents by the given $query.
     *
     * @param string $query
     */
    public function deleteByQuery($query)
    {
        $endpoints = $this->endpointResolver->getEndpoints();

        foreach ($endpoints as $endpointName) {
            $this->client->request(
                'POST',
                $this->endpointRegistry->getEndpoint($endpointName),
                '/update?wt=json',
                new Message(
                    array(
                        'Content-Type' => 'text/xml',
                    ),
                    "<delete><query>{$query}</query></delete>"
                )
            );
        }
    }

    /**
     * @todo implement purging for document type
     *
     * Purges all contents from the index
     */
    public function purgeIndex()
    {
        $endpoints = $this->endpointResolver->getEndpoints();

        foreach ($endpoints as $endpointName) {
            $this->purgeEndpoint(
                $this->endpointRegistry->getEndpoint($endpointName)
            );
        }
    }

    /**
     * @todo error handling
     *
     * @param $endpoint
     */
    protected function purgeEndpoint($endpoint)
    {
        $this->client->request(
            'POST',
            $endpoint,
            '/update?wt=json',
            new Message(
                array(
                    'Content-Type' => 'text/xml',
                ),
                '<delete><query>*:*</query></delete>'
            )
        );
    }

    /**
     * Commits the data to the Solr index, making it available for search.
     *
     * This will perform Solr 'soft commit', which means there is no guarantee that data
     * is actually written to the stable storage, it is only made available for search.
     * Passing true will also write the data to the safe storage, ensuring durability.
     *
     * @param bool $flush
     */
    public function commit($flush = false)
    {
        $payload = $flush ?
            '<commit/>' :
            '<commit softCommit="true"/>';

        foreach ($this->endpointResolver->getEndpoints() as $endpointName) {
            $result = $this->client->request(
                'POST',
                $this->endpointRegistry->getEndpoint($endpointName),
                '/update',
                new Message(
                    array(
                        'Content-Type' => 'text/xml',
                    ),
                    $payload
                )
            );

            if ($result->headers['status'] !== 200) {
                throw new RuntimeException(
                    'Wrong HTTP status received from Solr: ' .
                    $result->headers['status'] . var_export($result, true)
                );
            }
        }
    }

    /**
     * Create document(s) update XML.
     *
     * @param \eZ\Publish\SPI\Search\Document[] $documents
     *
     * @return string
     */
    protected function createUpdates(array $documents)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startElement('add');

        foreach ($documents as $document) {
            // Index dummy nested document when there are no other nested documents.
            // This is done in order to avoid the situation when previous standalone document is
            // being re-indexed as a block-joined set of documents, or vice-versa.
            // Enforcing document block in all cases ensures correct overwriting (updating) and
            // avoiding multiple documents with the same ID.
            if (empty($document->documents)) {
                $document->documents[] = $this->getDummyDocument($document->id);
            }
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

    /**
     * Returns a 'dummy' document.
     *
     * This is intended to be indexed as nested document of Content, in order to enforce
     * document block when Content does not have other nested documents (Locations).
     * Not intended to be returned as a search result.
     *
     * For more info see:
     * @link http://grokbase.com/t/lucene/solr-user/14chqr73nv/converting-to-parent-child-block-indexing
     * @link https://issues.apache.org/jira/browse/SOLR-5211
     *
     * @param string $id
     * @return \eZ\Publish\SPI\Search\Document
     */
    protected function getDummyDocument($id)
    {
        return new Document(
            array(
                'id' => $id . '_nested_dummy',
                'fields' => array(
                    new Field(
                        'document_type',
                        'nested_dummy',
                        new FieldType\IdentifierField()
                    ),
                ),
            )
        );
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
