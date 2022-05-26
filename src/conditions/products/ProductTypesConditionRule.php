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

class ProductTypesConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('app', 'Product Types');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['productTypeIds'];
    }

    protected function options(): array
    {
        $allTypes = Commerce::getInstance()->productTypes->getAllProductTypes();
        usort($allTypes, function ($a, $b) {
            return $a->name > $b->name;
        });

        return ArrayHelper::map($allTypes, 'id', 'name');
    }

    /**
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->where([($this->operator == self::OPERATOR_NOT_IN ? 'not in' : 'in'), '{{%commerce_products}}.typeId', $this->getValues()]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Product $element */
        return $this->matchValue($element->typeId);
    }
}
