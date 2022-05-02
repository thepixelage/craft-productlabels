<?php

namespace thepixelage\productlabels\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use thepixelage\productlabels\db\Table;
use thepixelage\productlabels\models\ProductLabelType;

class ProductLabelQuery extends ElementQuery
{
    public mixed $typeId = null;

    public function init(): void
    {
        if (!isset($this->withStructure)) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function type(mixed $value): self
    {
        if ($value instanceof ProductLabelType) {
            $this->structureId = ($value->structureId ?: false);
            $this->typeId = [$value->id];
        } elseif ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(Table::PRODUCTLABELTYPES)
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    public function typeId(mixed $value): self
    {
        $this->typeId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $productLabelsTableName = 'productlabels';
        $this->joinElementTable($productLabelsTableName);
        $this->query->select([
            sprintf('%s.typeId', $productLabelsTableName),
        ]);

        return parent::beforePrepare();
    }
}
