<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;

/**
 * A criterion that matches a document based on its indexed content translation language code.
 *
 * Supported operators:
 * - EQ: matches against one language code
 */
class IndexedLanguageCode extends Criterion implements CriterionInterface
{
    /**
     * @internal
     *
     * Creates a new IndexedLanguageCode criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param string|string[] $value One language code that must be matched.
     */
    public function __construct($value)
    {
        parent::__construct(null, null, $value);
    }

    public function getSpecifications()
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            ),
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($value);
    }
}
