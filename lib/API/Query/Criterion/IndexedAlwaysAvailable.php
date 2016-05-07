<?php

namespace EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;
use InvalidArgumentException;

/**
 * A criterion that matches a document based on whether it's indexed content translation
 * is the main translation of the content, marked as always available.
 */
class IndexedAlwaysAvailable extends Criterion implements CriterionInterface
{
    /**
     * Always available constant: always available in it's main translation.
     */
    const AVAILABLE = 0;

    /**
     * Always available constant: not always available in it's main translation.
     */
    const NOT_AVAILABLE = 1;

    /**
     * @internal
     *
     * Creates a new IndexedAlwaysAvailable criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param int $value Availability: self::ALWAYS_AVAILABLE, self::NOT_ALWAYS_AVAILABLE
     */
    public function __construct($value)
    {
        if ($value !== self::AVAILABLE && $value !== self::NOT_AVAILABLE) {
            throw new InvalidArgumentException(
                "Invalid always available value '{$value}'"
            );
        }

        parent::__construct(null, null, $value);
    }

    public function getSpecifications()
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER
            ),
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($value);
    }
}
