<?php

namespace thepixelage\productlabels\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;
use thepixelage\productlabels\db\Table;
use thepixelage\productlabels\elements\ProductLabel;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property-read ActiveQueryInterface $fieldLayout
 * @property int $id ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property-read ActiveQueryInterface $productLabels
 * @property string $handle Handle
 */
class ProductLabelType extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::PRODUCTLABELTYPES;
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    public function getProductLabels(): ActiveQueryInterface
    {
        return $this->hasMany(ProductLabel::class, ['productLabelTypeId' => 'id']);
    }
}
