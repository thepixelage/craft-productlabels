<?php

namespace thepixelage\productlabels\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Product;
use craft\events\ConfigEvent;
use craft\models\FieldLayout;
use thepixelage\productlabels\elements\ProductLabel;
use yii\base\Exception;

class ProductLabels extends Component
{
    /**
     * @throws Exception
     */
    public function handleChangedProductLabelConfig(ConfigEvent $event)
    {
        $data = $event->newValue;
        $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
        $layout->type = ProductLabel::class;
        $layout->uid = key($data['fieldLayouts']);
        Craft::$app->getFields()->saveLayout($layout);
    }

    public function matchConditions(ProductLabel $productLabel, Product|\yii\base\Component $product): bool
    {
        $productCondition = $productLabel->getProductCondition();
        if (count($productCondition->getConditionRules()) > 0) {
            foreach ($productCondition->getConditionRules() as $rule) {
                if (!$rule->matchElement($product)) {
                    return false;
                }
            }
        }

        return true;
    }
}
