<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;

/**
 * A criterion that matches a document based on its type identifier.
 *
 * Supported operators:
 * - EQ: matches against one document type ID
 */
class DocumentTypeIdentifier extends Criterion implements CriterionInterface
{
    /**
     * @internal
     *
     * Creates a new DocumentTypeIdentifier criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param int|string $value One document identifier that must be matched.
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
                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
            ),
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($value);
    }
}
