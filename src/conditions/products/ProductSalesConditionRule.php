<?php

namespace thepixelage\productlabels\conditions\products;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as Commerce;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

class ProductSalesConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('app', 'Product Sales');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['saleIds'];
    }

    /**
     * @throws InvalidConfigException
     */
    protected function options(): array
    {
        $sales = Commerce::getInstance()->getSales()->getAllSales();

        return ArrayHelper::map($sales, 'id', 'name');
    }

    /**
     * @throws NotSupportedException
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        throw new NotSupportedException('Product Sales condition rule does not support element queries.');
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Product $element */
        $purchasable = $element->defaultVariant;
        foreach ($purchasable->sales as $sale) {
            if ($this->matchValue($sale->id)) {
                return true;
            }
        }

        return false;
    }
}
