<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion\TranslationCorePlacement as PlacementCriterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use RuntimeException;

class TranslationCorePlacement extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof PlacementCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        switch ($criterion->value[0]) {
            case PlacementCriterion::IN_MAIN_TRANSLATION_CORE:
                return 'meta_indexed_main_translation_b:true';
                break;
            case PlacementCriterion::NOT_IN_MAIN_TRANSLATION_CORE:
                return 'meta_indexed_main_translation_b:false';
                break;
            case PlacementCriterion::IN_REGULAR_TRANSLATION_CORE:
                return 'meta_indexed_translation_b:true';
                break;
            case PlacementCriterion::NOT_IN_REGULAR_TRANSLATION_CORE:
                return 'meta_indexed_translation_b:false';
                break;
        }

        throw new RuntimeException(
            "Invalid translation core placement value '{$criterion->value[0]}'"
        );
    }
}
