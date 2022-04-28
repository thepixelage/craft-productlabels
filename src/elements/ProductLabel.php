<?php

namespace thepixelage\productlabels\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use thepixelage\productlabels\elements\db\ProductLabelQuery;
use yii\db\Exception;

class ProductLabel extends Element
{
    public ?string $conditions = null;

    public static function displayName(): string
    {
        return 'Product Label';
    }

    public static function pluralDisplayName(): string
    {
        return 'Product Labels';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new ProductLabelQuery(static::class);
    }

    /**
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%productlabels}}', [
                    'id' => $this->id,
                    'conditions' => $this->conditions,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%productlabels}}', [
                    'conditions' => $this->conditions,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}
