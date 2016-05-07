<?php

namespace EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;

/**
 * A criterion that matches content based on its language code.
 *
 * Supported operators:
 * - IN: matches against a list of language codes
 * - EQ: matches against one language code
 */
class LanguageCode extends Criterion implements CriterionInterface
{
    /**
     * Creates a new LanguageCode criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param string|string[] $value One or more language codes that must be matched.
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
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_STRING
            ),
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($value);
    }
}
