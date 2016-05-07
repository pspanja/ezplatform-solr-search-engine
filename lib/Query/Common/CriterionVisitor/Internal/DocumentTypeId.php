<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\DocumentTypeId as DocumentTypeIdCriterion;
use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class DocumentTypeId extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof DocumentTypeIdCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        return 'document_type_id:"' . $criterion->value[0] . '"';
    }
}
