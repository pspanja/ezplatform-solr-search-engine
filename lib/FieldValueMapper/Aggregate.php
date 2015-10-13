<?php

/**
 * File containing the Aggregate class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\SolrSearchEngine\FieldValueMapper;

use EzSystems\SolrSearchEngine\FieldValueMapper;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\API\Repository\Exceptions\NotImplementedException;

/**
 * Maps raw document field values to something Solr can index.
 */
class Aggregate extends FieldValueMapper
{
    /**
     * Array of available mappers.
     *
     * @var \EzSystems\SolrSearchEngine\FieldValueMapper[]
     */
    protected $mappers = array();

    /**
     * COnstruct from optional mapper array.
     *
     * @param \EzSystems\SolrSearchEngine\FieldValueMapper[] $mappers
     */
    public function __construct(array $mappers = array())
    {
        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }
    }

    /**
     * Adds mapper.
     *
     * @param \EzSystems\SolrSearchEngine\FieldValueMapper $mapper
     */
    public function addMapper(FieldValueMapper $mapper)
    {
        $this->mappers[] = $mapper;
    }

    /**
     * Check if field can be mapped.
     *
     * @param Field $field
     *
     * @return bool
     */
    public function canMap(Field $field)
    {
        return true;
    }

    /**
     * Map field value to a proper Solr representation.
     *
     * @param Field $field
     *
     * @return mixed
     */
    public function map(Field $field)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->canMap($field)) {
                return $mapper->map($field);
            }
        }

        throw new NotImplementedException('No mapper available for: ' . get_class($field->type));
    }
}
