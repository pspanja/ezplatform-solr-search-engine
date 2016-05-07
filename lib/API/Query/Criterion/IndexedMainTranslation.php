<?php

namespace EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;
use InvalidArgumentException;

/**
 * A criterion that matches a document based on whether it's indexed content translation is the
 * main translation of the content.
 */
class IndexedMainTranslation extends Criterion implements CriterionInterface
{
    /**
     * Main content translation constant: main.
     */
    const MAIN = 0;

    /**
     * Main content translation constant: not main.
     */
    const NOT_MAIN = 1;

    /**
     * Creates a new IndexedMainTranslation criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param int $value Main content translation: self::MAIN, self::NOT_MAIN
     */
    public function __construct($value)
    {
        if ($value !== self::MAIN && $value !== self::NOT_MAIN) {
            throw new InvalidArgumentException(
                "Invalid main translation value '{$value}'"
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
