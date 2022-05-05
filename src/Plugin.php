<?php

namespace thepixelage\productlabels;

use Craft;
use craft\base\Model;
use craft\commerce\elements\Product;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\gql\TypeManager;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Gql;
use craft\services\Plugins;
use craft\web\UrlManager;
use Exception;
use GraphQL\Type\Definition\Type;
use thepixelage\productlabels\behaviors\ProductBehavior;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\gql\interfaces\elements\ProductLabel as ProductLabelInterface;
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

    /**
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->hasCpSection = true;
        $this->hasCpSettings = true;

        $this->registerPluginEventListeners();
        $this->registerBehaviors();
        $this->registerServices();
        $this->registerElementTypes();
        $this->registerFieldLayoutStandardFields();
        $this->registerCpRoutes();
        $this->registerProjectConfigChangeListeners();
        $this->registerGql();
    }

    public function getSettingsResponse(): mixed
    {
        $url = UrlHelper::cpUrl('productlabels/settings');

        return Craft::$app->controller->redirect($url);
    }

    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->allowAdminChanges && $currentUser->admin) {
            $subNavs['fragments'] = [
                'label' => Craft::t('fragments', "Product Labels"),
                'url' => 'productlabels',
            ];

            $subNavs['settings'] = [
                'label' => Craft::t('fragments', "Settings"),
                'url' => 'productlabels/settings',
            ];
        }

        return array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
    }

    private function registerPluginEventListeners()
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                $this->productLabels->saveFieldLayout();
                $this->productLabels->createStructure();
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                Craft::$app->getProjectConfig()->remove('productLabels.structure');
                Craft::$app->getProjectConfig()->remove('productLabels.fieldLayouts');
            }
        );
    }

    private function registerBehaviors()
    {
        Event::on(
            Product::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                $event->behaviors[] = ProductBehavior::class;
            }
        );
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
            ->onAdd('productLabels.fieldLayouts', [$this->productLabels, 'handleChangedProductLabelFieldLayout'])
            ->onUpdate('productLabels.fieldLayouts', [$this->productLabels, 'handleChangedProductLabelFieldLayout'])
            ->onRemove('productLabels.fieldLayouts', [$this->productLabels, 'handleDeletedProductLabelFieldLayout']);

        Craft::$app->projectConfig
            ->onAdd('productLabels.structure', [$this->productLabels, 'handleChangedProductLabelStructure'])
            ->onUpdate('productLabels.structure', [$this->productLabels, 'handleChangedProductLabelStructure'])
            ->onRemove('productLabels.structure', [$this->productLabels, 'handleDeletedProductLabelStructure']);
    }

    private function registerGql()
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                $event->types[] = ProductLabelInterface::class;
            }
        );

        Event::on(
            TypeManager::class,
            TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS,
            function(DefineGqlTypeFieldsEvent $event) {
                if ($event->typeName == 'ProductInterface') {
                    $event->fields['productLabels'] = [
                        'name' => 'productLabels',
                        'type' => Type::listOf(ProductLabelInterface::getType()),
                        'resolve' => function($source, $arguments, $context, $resolveInfo) {
                            return $source->productLabels;
                        }
                    ];
                }
            }
        );
    }
}
