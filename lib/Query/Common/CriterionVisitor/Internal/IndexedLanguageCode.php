<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedLanguageCode as IndexedLanguageCodeCriterion;
use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class IndexedLanguageCode extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof IndexedLanguageCodeCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        return 'meta_indexed_language_code_s:"' . $criterion->value[0] . '"';
    }
}
