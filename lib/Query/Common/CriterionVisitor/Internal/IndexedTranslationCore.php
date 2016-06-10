<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion\IndexedTranslationCore as IndexedTranslationCoreCriterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class IndexedTranslationCore extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof IndexedTranslationCoreCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        $value = ($criterion->value[0] === IndexedTranslationCoreCriterion::TRANSLATION_CORE ? 'true' : 'false');

        return 'meta_indexed_translation_b:' . $value;
    }
}
