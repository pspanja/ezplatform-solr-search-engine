<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\LanguageCode;
use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class LanguageCodeIn extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return
            $criterion instanceof LanguageCode &&
            (
                ($criterion->operator ?: Operator::IN) === Operator::IN ||
                $criterion->operator === Operator::EQ
            );
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        return '(' .
            implode(
                ' OR ',
                array_map(
                    function ($value) {
                        return 'content_language_codes_ms:"' . $value . '"';
                    },
                    $criterion->value
                )
            ) .
            ')';
    }
}
