<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion\IndexedAlwaysAvailable as IndexedAlwaysAvailableCriterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class IndexedAlwaysAvailable extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof IndexedAlwaysAvailableCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        $value = ($criterion->value[0] === IndexedAlwaysAvailableCriterion::ALWAYS_AVAILABLE ? 'true' : 'false');

        return 'meta_indexed_is_main_translation_and_always_available_b:' . $value;
    }
}
