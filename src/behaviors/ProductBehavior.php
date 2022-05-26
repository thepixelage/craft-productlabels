<?php

namespace thepixelage\productlabels\behaviors;

use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\Plugin;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{
    public function getProductLabels(): array
    {
        $productLabels = Plugin::getInstance()->productLabels->getAllProductLabels();

        return array_filter($productLabels, function (ProductLabel $productLabel) {
            return in_array($this->owner->id, $productLabel->getMatchedProductIds());
        });
    }
}
