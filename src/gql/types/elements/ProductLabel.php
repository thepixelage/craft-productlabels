<?php

namespace thepixelage\productlabels\gql\types\elements;

use craft\gql\types\elements\Element;
use thepixelage\productlabels\gql\interfaces\elements\ProductLabel as ProductLabelInterface;

class ProductLabel extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ProductLabelInterface::getType(),
        ];

        parent::__construct($config);
    }
}
