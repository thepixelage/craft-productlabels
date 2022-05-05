<?php

namespace thepixelage\productlabels\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Product;
use craft\errors\BusyResourceException;
use craft\errors\StaleResourceException;
use craft\errors\StructureNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\Structure;
use thepixelage\productlabels\elements\ProductLabel;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

class ProductLabels extends Component
{
    /**
     * @throws \Exception
     */
    public function createStructure(string $uid): bool
    {
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
    public function saveFieldLayout(FieldLayout $fieldLayout)
    {
        $configData = [
            'fieldLayouts' => [
                $fieldLayout->uid => $fieldLayout->getConfig()
            ]
        ];

        Craft::$app->getProjectConfig()->set('productLabels', $configData, "Save product label config");
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
     */
    public function handleChangedProductLabelStructure(ConfigEvent $event)
    {
        $data = $event->newValue;
        $structure = Craft::$app->getStructures()->getStructureByUid($data['uid'], true) ?? new Structure(['uid' => $data['uid']]);
        $structure->maxLevels = 1;
        Craft::$app->getStructures()->saveStructure($structure);
    }

    /**
     * @throws Exception
     */
    public function handleChangedProductLabel(ConfigEvent $event)
    {
        $data = $event->newValue;
        if (isset($data['fieldLayouts'])) {
            $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
            $layout->type = ProductLabel::class;
            $layout->uid = key($data['fieldLayouts']);
            Craft::$app->getFields()->saveLayout($layout);
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
}
