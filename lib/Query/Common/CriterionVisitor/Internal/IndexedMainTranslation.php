<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedMainTranslation as IndexedMainTranslationCriterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class IndexedMainTranslation extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof IndexedMainTranslationCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        $value = ($criterion->value[0] === IndexedMainTranslationCriterion::MAIN ? 'true' : 'false');

        return 'meta_indexed_is_main_translation_b:' . $value;
    }
}
