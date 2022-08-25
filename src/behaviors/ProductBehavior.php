<?php

namespace thepixelage\productlabels\behaviors;

use craft\commerce\elements\Product;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\Plugin;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{
    public function getProductLabels(): array
    {
        /** @var Product $product */
        $product = $this->owner;
        $productLabels = Plugin::getInstance()->productLabels->getAllProductLabels();

        return array_filter($productLabels, function (ProductLabel $productLabel) use ($product) {
            return in_array($product->id, $productLabel->getMatchedProductIds()) &&
                $productLabel->getMatchCurrentUser();
        });
    }
}
