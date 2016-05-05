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

use EzSystems\EzPlatformSolrSearchEngine\Endpoint;
use EzSystems\EzPlatformSolrSearchEngine\Gateway;
use EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\SPI\Search\FieldType;
use RuntimeException;

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
     * @var \EzSystems\EzPlatformSolrSearchEngine\Gateway\UpdateSerializer
     */
    protected $updateSerializer;

    /**
     * Construct from HTTP client.
     *
     * @param HttpClient $client
     * @param \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter $contentQueryConverter
     * @param \EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter $locationQueryConverter
     * @param \EzSystems\EzPlatformSolrSearchEngine\Gateway\UpdateSerializer $updateSerializer
     */
    public function __construct(
        HttpClient $client,
        QueryConverter $contentQueryConverter,
        QueryConverter $locationQueryConverter,
        UpdateSerializer $updateSerializer
    ) {
        $this->client = $client;
        $this->contentQueryConverter = $contentQueryConverter;
        $this->locationQueryConverter = $locationQueryConverter;
        $this->updateSerializer = $updateSerializer;
    }

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $entryEndpoint
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    public function findContent(Query $query, Endpoint $entryEndpoint, array $targetEndpoints)
    {
        $parameters = $this->contentQueryConverter->convert($query, $targetEndpoints);

        return $this->internalFind($parameters, $entryEndpoint);
    }

    /**
     * Returns search hits for the given query.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $entryEndpoint
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint[] $targetEndpoints
     *
     * @return mixed
     */
    public function findLocations(Query $query, Endpoint $entryEndpoint, array $targetEndpoints)
    {
        $parameters = $this->locationQueryConverter->convert($query, $targetEndpoints);

        return $this->internalFind($parameters, $entryEndpoint);
    }

    /**
     * Returns search hits for the given array of Solr query parameters.
     *
     * @param array $parameters
     * @param \EzSystems\EzPlatformSolrSearchEngine\Endpoint $entryEndpoint
     *
     * @return mixed
     */
    protected function internalFind(array $parameters, Endpoint $entryEndpoint)
    {
        $queryString = $this->generateQueryString($parameters);

        $response = $this->client->request('GET', $entryEndpoint, "/select?{$queryString}" );

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
        $updates = $this->updateSerializer->serialize($documents);
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

    public function deleteByQuery($query, array $endpoints)
    {
        foreach ($endpoints as $endpoint) {
            $this->client->request(
                'POST',
                $endpoint,
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

    public function purgeIndex(array $endpoints)
    {
        foreach ($endpoints as $endpoint) {
            $this->purgeEndpoint($endpoint);
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

    public function commit(array $endpoints, $flush = false)
    {
        $payload = $flush ? '<commit/>' : '<commit softCommit="true"/>';

        foreach ($endpoints as $endpoint) {
            $result = $this->client->request(
                'POST',
                $endpoint,
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
}
