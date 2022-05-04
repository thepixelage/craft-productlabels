<?php

return [
    'productlabels/settings' => 'productlabels/settings/settings',
    'productlabels' => 'productlabels/product-labels/index',
    'productlabels/new' => 'productlabels/product-labels/edit',
    'productlabels/<productLabelId:\d+><slug:(?:-[^\/]*)?>' => 'productlabels/product-labels/edit',
];
