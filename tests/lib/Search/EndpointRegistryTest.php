<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Tests\Search;

use EzSystems\EzPlatformSolrSearchEngine\EndpointRegistry;
use EzSystems\EzPlatformSolrSearchEngine\Values\Endpoint;

/**
 * Test case for the endpoint registry.
 */
class EndpointRegistryTest extends TestCase
{
    public function testConstructWithoutEndpoints()
    {
        $endpointRegistry = new EndpointRegistry();
    }

    /**
     * @depends testConstructWithoutEndpoints
     * @expectedException \OutOfBoundsException
     */
    public function testGetEndpointThrowsOutOfBoundsException()
    {
        $endpointRegistry = new EndpointRegistry();

        $endpointRegistry->getEndpoint('red');
    }

    public function testConstructWithEndpoints()
    {
        $endpoints = [
            new Endpoint(),
        ];

        $endpointRegistry = new EndpointRegistry($endpoints);
    }

    /**
     * @depends testConstructWithEndpoints
     */
    public function testConstructWithEndpointsGetEndpoint()
    {
        $endpoint = new Endpoint();
        $endpointName = 'green';
        $endpoints = [
            $endpointName => $endpoint,
        ];

        $endpointRegistry = new EndpointRegistry($endpoints);

        $this->assertSame(
            $endpoint,
            $endpointRegistry->getEndpoint($endpointName)
        );
    }

    /**
     * @depends testConstructWithoutEndpoints
     */
    public function testRegisterEndpointGetEndpoint()
    {
        $endpoint = new Endpoint();
        $endpointName = 'blue';
        $endpointRegistry = new EndpointRegistry();

        $endpointRegistry->registerEndpoint($endpointName, $endpoint);

        $this->assertSame(
            $endpoint,
            $endpointRegistry->getEndpoint($endpointName)
        );
    }
}
