<?php

namespace thepixelage\productlabels;

use Craft;
use craft\base\Model;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\web\UrlManager;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\models\Settings;
use thepixelage\productlabels\services\ProductLabels;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\productlabels
 *
 * @property ProductLabels $productLabels
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = false;
    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->hasCpSection = true;
        $this->hasCpSettings = false;

        $this->registerServices();
        $this->registerElementTypes();
        $this->registerFieldLayoutStandardFields();
        $this->registerCpRoutes();
        $this->registerProjectConfigChangeListeners();
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    private function registerServices()
    {
        $this->setComponents([
            'productLabels' => ProductLabels::class,
        ]);
    }

    public function registerElementTypes(): void
    {
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = ProductLabel::class;
            }
        );
    }

    private function registerFieldLayoutStandardFields()
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /* @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            if ($fieldLayout->type == ProductLabel::class) {
                $event->fields[] = TitleField::class;
            }
        });
    }

    private function registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $rules = include __DIR__ . '/config/routes.php';
            $event->rules = array_merge($event->rules, $rules);
        });
    }

    private function registerProjectConfigChangeListeners()
    {
        Craft::$app->projectConfig
            ->onAdd('productLabelTypes.{uid}', [$this->productLabels, 'handleChangedProductLabelType'])
            ->onUpdate('productLabelTypes.{uid}', [$this->productLabels, 'handleChangedProductLabelType'])
            ->onRemove('productLabelTypes.{uid}', [$this->productLabels, 'handleDeletedProductLabelType']);
    }
}
