<?php

namespace thepixelage\productlabels\conditions;

use craft\elements\conditions\ElementCondition;
use craft\errors\InvalidTypeException;
use thepixelage\productlabels\conditions\products\ProductSalesConditionRule;
use thepixelage\productlabels\conditions\products\ProductsConditionRule;
use thepixelage\productlabels\conditions\products\ProductTypesConditionRule;

class ProductLabelProductCondition extends ElementCondition
{
    /**
     * @throws InvalidTypeException
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            ProductsConditionRule::class,
            ProductSalesConditionRule::class,
            ProductTypesConditionRule::class,
        ]);
    }
}
