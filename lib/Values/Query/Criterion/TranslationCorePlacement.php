<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;
use InvalidArgumentException;

/**
 * A criterion that matches a document based on whether its translation is the
 * main translation of Content and how it is indexed, as a main or a regular translation.
 * Combination of these two criteria is known as translation core placement.
 *
 * Note: depending on configured mapping, one document can be indexed in a single core as:
 * - main translation (in a dedicated main translations core)
 * - regular translation (other core might be configured for main translations or not)
 * - both main and regular translation (shared - the same core is used for both main and
 *   regular translations)
 */
class TranslationCorePlacement extends Criterion implements CriterionInterface
{
    /**
     * Main translation placed in main translation core.
     */
    const IN_MAIN_TRANSLATION_CORE = 0;

    /**
     * Not main translation placed in main translation core.
     */
    const NOT_IN_MAIN_TRANSLATION_CORE = 1;

    /**
     * A translation placed in regular translation core.
     */
    const IN_REGULAR_TRANSLATION_CORE = 2;

    /**
     * A translation not placed in regular translation core.
     */
    const NOT_IN_REGULAR_TRANSLATION_CORE = 3;

    /**
     * @internal
     *
     * Creates a new TranslationCorePlacement criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param mixed $value Translation core placement constant, one of:
     *                     self::IN_MAIN_TRANSLATION_CORE
     *                     self::NOT_IN_MAIN_TRANSLATION_CORE
     *                     self::IN_REGULAR_TRANSLATION_CORE
     *                     self::NOT_IN_REGULAR_TRANSLATION_CORE
     */
    public function __construct($value)
    {
        if (
            $value !== self::IN_MAIN_TRANSLATION_CORE &&
            $value !== self::NOT_IN_MAIN_TRANSLATION_CORE &&
            $value !== self::IN_REGULAR_TRANSLATION_CORE &&
            $value !== self::NOT_IN_REGULAR_TRANSLATION_CORE
        ) {
            throw new InvalidArgumentException(
                "Invalid translation core placement value '{$value}'"
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
