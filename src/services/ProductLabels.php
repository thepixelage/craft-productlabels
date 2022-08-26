<?php

namespace thepixelage\productlabels\services;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\commerce\elements\Product;
use craft\elements\User;
use craft\errors\BusyResourceException;
use craft\errors\StaleResourceException;
use craft\errors\StructureNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\Structure;
use craft\records\StructureElement;
use thepixelage\productlabels\elements\ProductLabel;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

class ProductLabels extends Component
{
    public ?MemoizableArray $_productLabels = null;

    public function getAllProductLabels(): array
    {
        return $this->_productLabels()->all();
    }

    /**
     * @throws \Exception
     */
    public function createStructure(?string $uid = null): bool
    {
        if (empty($uid)) {
            $uid = StringHelper::UUID();
        }

        Craft::$app->getProjectConfig()->set('productLabels.structure', [
            'uid' => $uid,
        ]);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function getStructure(): Structure
    {
        $structureUid = Craft::$app->getProjectConfig()->get('productLabels.structure.uid') ?? StringHelper::UUID();
        $structure = Craft::$app->getStructures()->getStructureByUid($structureUid);

        if (!$structure) {
            $structureUid = StringHelper::UUID();
            $this->createStructure($structureUid);
            $structure = Craft::$app->getStructures()->getStructureByUid($structureUid);
        }

        return $structure;
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws StaleResourceException
     * @throws Exception
     * @throws BusyResourceException
     * @throws ErrorException
     */
    public function saveFieldLayout(?FieldLayout $fieldLayout = null)
    {
        if (!$fieldLayout) {
            $fieldLayout = Craft::$app->getFields()->getLayoutByType(ProductLabel::class);
        }

        $configData = [
            $fieldLayout->uid => $fieldLayout->getConfig()
        ];

        Craft::$app->getProjectConfig()->set('productLabels.fieldLayouts', $configData, "Save product label config");
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws StaleResourceException
     * @throws BusyResourceException
     * @throws ErrorException
     * @throws Exception
     */
    public function getFieldLayout(): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ProductLabel::class);
        if (empty($fieldLayout->id)) {
            $this->saveFieldLayout($fieldLayout);
        }

        return $fieldLayout;
    }

    /**
     * @throws StructureNotFoundException
     * @throws \yii\db\Exception
     */
    public function handleChangedProductLabelStructure(ConfigEvent $event)
    {
        $data = $event->newValue;
        $structure = Craft::$app->getStructures()->getStructureByUid($data['uid'], true) ?? new Structure(['uid' => $data['uid']]);
        $structure->maxLevels = 1;
        $isNewStructure = empty($structure->id);
        Craft::$app->getStructures()->saveStructure($structure);

        if ($isNewStructure) {
            $elementIds = ProductLabel::find()->status(null)->ids();
            $rootIds = StructureElement::find()->select(['root'])->where(['in', 'elementId', $elementIds])->column();
            Craft::$app->db->createCommand()
                ->update('{{%structureelements}}', ['structureId' => $structure->id], ['in', 'root', $rootIds])
                ->execute();
        }
    }

    public function handleDeletedProductLabelStructure(ConfigEvent $event)
    {
        $structureUid = Craft::$app->getProjectConfig()->get('productLabels.structure.uid');
        if (!$structureUid) {
            return;
        }

        $structure = Craft::$app->getStructures()->getStructureByUid($structureUid, true);
        if (!$structure) {
            return;
        }

        Craft::$app->getStructures()->deleteStructureById($structure->id);
    }

    /**
     * @throws Exception
     */
    public function handleChangedProductLabelFieldLayout(ConfigEvent $event)
    {
        $data = $event->newValue;
        if (isset($data)) {
            $layout = FieldLayout::createFromConfig(reset($data));
            $layout->type = ProductLabel::class;
            $layout->uid = key($data);
            Craft::$app->getFields()->saveLayout($layout);
        }
    }

    public function handleDeletedProductLabelFieldLayout(ConfigEvent $event)
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ProductLabel::class);
        if (!empty($fieldLayout->id)) {
            Craft::$app->getFields()->deleteLayout($fieldLayout);
        }
    }

    public function matchConditions(ProductLabel $productLabel, Product|\yii\base\Component $product): bool
    {
        $productCondition = $productLabel->getProductCondition();
        if (count($productCondition->getConditionRules()) > 0) {
            foreach ($productCondition->getConditionRules() as $rule) {
                if (!$rule->matchElement($product)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function _productLabels(): MemoizableArray
    {
        if (!isset($this->_productLabels)) {
            $productLabels = ProductLabel::find()->all();
            $currentUser = Craft::$app->user->id ? User::find()->id(Craft::$app->user->id)->one() : null;

            /** @var ProductLabel $productLabel */
            foreach ($productLabels as $productLabel) {
                $productCondition = $productLabel->getProductCondition();
                if (count($productCondition->getConditionRules()) > 0) {
                    $query = Product::find();
                    $productCondition->modifyQuery($query);
                    $productLabel->setMatchedProductIds($query->ids());
                } else {
                    $productLabel->setMatchAllProducts(true);
                }

                $userCondition = $productLabel->getUserCondition();
                $productLabel->setMatchCurrentUser(
                    count($userCondition->conditionRules) == 0 ||
                    ($currentUser && $userCondition->matchElement($currentUser))
                );
            }

            $this->_productLabels = new MemoizableArray(array_values($productLabels));
        }

        return $this->_productLabels;
    }
}
