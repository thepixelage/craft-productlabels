<?php

namespace thepixelage\productlabels\elements\db;

use craft\elements\db\ElementQuery;

class ProductLabelQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('productlabels');

        return parent::beforePrepare();
    }
}
