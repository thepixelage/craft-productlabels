<?php

namespace thepixelage\productlabels\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\BusyResourceException;
use craft\errors\StaleResourceException;
use craft\events\ConfigEvent;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\services\ProjectConfig;
use thepixelage\productlabels\db\Table;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\models\ProductLabelType;
use thepixelage\productlabels\records\ProductLabelType as ProductLabelTypeRecord;
use Throwable;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\ServerErrorHttpException;

class ProductLabels extends Component
{
    public function getTypeById($typeId): ProductLabelType
    {
        $record = ProductLabelTypeRecord::find()->where(['id' => $typeId])->one();

        return $this->createProductLabelTypeFromRecord($record);
    }

    public function getAllTypes(): array
    {
        $types = [];

        $records = ProductLabelTypeRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($records as $record) {
            $types[] = $this->createProductLabelTypeFromRecord($record);
        }

        return $types;
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws StaleResourceException
     * @throws ErrorException
     * @throws BusyResourceException
     * @throws Exception
     * @throws \Exception
     */
    public function saveType(ProductLabelType $type, bool $runValidation = true): bool
    {
        $isNewType = !$type->id;

        if ($runValidation && !$type->validate()) {
            Craft::info('Product label type not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewType) {
            $type->uid = StringHelper::UUID();
        }

        $configPath = "productLabelTypes.$type->uid";
        $configData = $type->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save product label type “{$type->handle}”");

        return true;
    }

    public function deleteTypeById(int $typeId)
    {
        if (!$typeId) {
            return false;
        }

        $type = $this->getTypeById($typeId);

        if (!$type) {
            return false;
        }

        return $this->deleteType($type);
    }

    public function deleteType(ProductLabelType $type): bool
    {
        Craft::$app->getProjectConfig()->remove('productLabelTypes.' . $type->uid, "Delete product label type “{$type->handle}”");

        return true;
    }

    public function handleChangedProductLabelType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $record = $this->getProductLabelTypeRecord($uid);
            $record->name = $data['name'];
            $record->handle = $data['handle'];
            $record->uid = $uid;

            if (!empty($data['fieldLayouts'])) {
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $record->fieldLayoutId;
                $layout->type = ProductLabel::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $record->fieldLayoutId = $layout->id;
            } else if ($record->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($record->fieldLayoutId);
                $record->fieldLayoutId = null;
            }

            $record->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleDeletedProductLabelType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $type = $this->getProductLabelTypeByUid($uid);
        if (!$type) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(Table::PRODUCTLABELTYPES, ['id' => $type->id])
            ->execute();
    }

    private function getProductLabelTypeRecord(string $uid): ProductLabelTypeRecord
    {
        $query = ProductLabelTypeRecord::find()->andWhere(['uid' => $uid]);

        return $query->one() ?? new ProductLabelTypeRecord();
    }

    public function getProductLabelTypeByUid($uid): ?ProductLabelType
    {
        $result = $this->createProductLabelTypesQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new ProductLabelType($result) : null;
    }

    private function createProductLabelTypesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid',
                'fieldLayoutId',
            ])
            ->from([Table::PRODUCTLABELTYPES])
            ->orderBy('name asc');
    }

    private function createProductLabelTypeFromRecord(ProductLabelTypeRecord $record): ProductLabelType
    {
        return new ProductLabelType($record->toArray([
            'id',
            'structureId',
            'fieldLayoutId',
            'name',
            'handle',
            'uid',
        ]));
    }
}
