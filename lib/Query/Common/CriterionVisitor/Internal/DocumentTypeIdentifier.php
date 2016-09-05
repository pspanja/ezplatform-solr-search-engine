<?php

namespace EzSystems\EzPlatformSolrSearchEngine\Query\Common\CriterionVisitor\Internal;

use EzSystems\EzPlatformSolrSearchEngine\Values\Query\Criterion\DocumentTypeIdentifier as DocumentTypeIdentifierCriterion;
use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

class DocumentTypeIdentifier extends CriterionVisitor
{
    public function canVisit(Criterion $criterion)
    {
        return $criterion instanceof DocumentTypeIdentifierCriterion && $criterion->operator === Operator::EQ;
    }

    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        return 'document_type_id:"' . $criterion->value[0] . '"';
    }
}
