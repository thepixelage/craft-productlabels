<?php

return [
    'productlabels' => 'productlabels/product-labels/product-label-index',
    'productlabels/<typeHandle:{handle}>' => 'productlabels/product-labels/product-label-index',
    'productlabels/<typeHandle:{handle}>/new' => 'productlabels/product-labels/edit',
    'productlabels/<typeHandle:{handle}>/<productLabelId:\d+><slug:(?:-[^\/]*)?>' => 'productlabels/product-labels/edit',
    'settings/productlabels' => 'productlabels/product-labels/type-index',
    'settings/productlabels/new' => 'productlabels/product-labels/edit-product-label-type',
    'settings/productlabels/<typeId:\d+>' => 'productlabels/product-labels/edit-product-label-type',
    'settings/productlabels/save-type' => 'productlabels/product-labels/save-product-label-type',
];
