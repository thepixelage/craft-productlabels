<?php

namespace thepixelage\productlabels\conditions;

use craft\elements\conditions\ElementCondition;
use craft\errors\InvalidTypeException;
use thepixelage\productlabels\conditions\products\ProductSalesConditionRule;
use thepixelage\productlabels\conditions\products\ProductSkusConditionRule;

class ProductLabelProductCondition extends ElementCondition
{
    /**
     * @throws InvalidTypeException
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            ProductSkusConditionRule::class,
            ProductSalesConditionRule::class,
        ]);
    }
}
