<?php

namespace thepixelage\productlabels\conditions\products;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\commerce\Plugin as Commerce;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

class SaleConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('app', 'Sale');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['sale', 'saleId'];
    }

    /**
     * @throws InvalidConfigException
     */
    protected function options(): array
    {
        $sales = Commerce::getInstance()->getSales()->getAllSales();

        return ArrayHelper::map($sales, 'id', 'name');
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {

    }

    public function matchElement(ElementInterface $element): bool
    {
        return true;
    }
}
