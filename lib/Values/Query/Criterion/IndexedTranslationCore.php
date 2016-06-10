<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;
use InvalidArgumentException;

/**
 * A criterion that matches a document based on whether it's indexed translation is
 * indexed as a regular translation of the Content.
 */
class IndexedTranslationCore extends Criterion implements CriterionInterface
{
    /**
     * Main content translation in main core placement constant: in main core.
     */
    const TRANSLATION_CORE = 0;

    /**
     * Main content translation in main core placement constant: not in main core.
     */
    const NOT_TRANSLATION_CORE = 1;

    /**
     * @internal
     *
     * Creates a new IndexedMainTranslationCore criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param int $value Main translation in main core: self::TRANSLATION_CORE, self::NOT_TRANSLATION_CORE
     */
    public function __construct($value)
    {
        if ($value !== self::TRANSLATION_CORE && $value !== self::NOT_TRANSLATION_CORE) {
            throw new InvalidArgumentException(
                "Invalid main translation core value '{$value}'"
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
