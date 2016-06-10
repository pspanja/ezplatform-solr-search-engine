<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion\IndexedMainTranslationCore as IndexedMainTranslationCoreCriterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class IndexedMainTranslationCore extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof IndexedMainTranslationCoreCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        $value = ($criterion->value[0] === IndexedMainTranslationCoreCriterion::MAIN_CORE ? 'true' : 'false');

        return 'meta_indexed_main_translation_b:' . $value;
    }
}
