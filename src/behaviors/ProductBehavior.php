<?php

namespace thepixelage\productlabels\behaviors;

use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\Plugin;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{
    public ?array $_productLabels = null;

    public function getProductLabels(): array
    {
        if ($this->_productLabels) {
            return $this->_productLabels;
        }

        $productLabels = ProductLabel::find()->all();

        $this->_productLabels = array_filter($productLabels, function ($productLabel) {
            return Plugin::getInstance()->productLabels->matchConditions($productLabel, $this->owner);
        });

        return $this->_productLabels;
    }
}
