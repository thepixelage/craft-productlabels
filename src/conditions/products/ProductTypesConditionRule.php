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
     * @throws NotSupportedException
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        throw new NotSupportedException('Product Types condition rule does not support element queries.');
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
