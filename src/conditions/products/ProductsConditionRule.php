<?php

namespace thepixelage\productlabels\conditions\products;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

class ProductsConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('app', 'Products');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['productIds'];
    }

    protected function options(): array
    {
        $allProducts = Product::find()->orderBy('dateUpdated desc, title asc, defaultSku asc')->all();
        $products = array_map(function ($product) {
            return [
                'id' => $product->id,
                'label' => "$product->defaultSku – $product->title",
            ];
        }, $allProducts);

        return ArrayHelper::map($products, 'id', 'label');
    }

    /**
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->where([($this->operator == self::OPERATOR_NOT_IN ? 'not in' : 'in'), '{{%commerce_products}}.id', $this->getValues()]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Product $element */
        return $this->matchValue($element->id);
    }
}
