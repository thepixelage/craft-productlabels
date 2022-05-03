<?php

namespace thepixelage\productlabels\conditions;

use craft\elements\conditions\ElementCondition;
use craft\errors\InvalidTypeException;
use thepixelage\productlabels\conditions\products\RelatedSaleConditionRule;
use thepixelage\productlabels\conditions\products\SkuConditionRule;

class ProductLabelProductCondition extends ElementCondition
{
    /**
     * @throws InvalidTypeException
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            SkuConditionRule::class,
            RelatedSaleConditionRule::class,
        ]);
    }
}
