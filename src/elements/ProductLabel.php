<?php

namespace thepixelage\productlabels\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Structures;
use DateTime;
use DateTimeZone;
use thepixelage\productlabels\conditions\ProductLabelProductCondition;
use thepixelage\productlabels\elements\db\ProductLabelQuery;
use thepixelage\productlabels\Plugin;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\Response;

/**
 * @property-read null|int $sourceId
 * @property-read string $gqlTypeName
 */
class ProductLabel extends Element
{
    public ?DateTime $dateFrom = null;
    public ?DateTime $dateTo = null;
    private ElementConditionInterface|null $_productCondition = null;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

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

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function getProductCondition(): ElementConditionInterface
    {
        $condition = $this->_productCondition ?? new ProductLabelProductCondition();
        $condition->mainTag = 'div';
        $condition->name = 'productCondition';

        return $condition;
    }

    /**
     * @throws InvalidConfigException
     */
    public function setProductCondition(ElementConditionInterface|string|array|null $condition): void
    {
        if (is_string($condition)) {
            $condition = Json::decodeIfJson($condition);
        }

        if (!$condition instanceof ElementConditionInterface) {
            $condition['class'] = ProductLabelProductCondition::class;
            $condition = Craft::$app->getConditions()->createCondition($condition);
        }
        $condition->forProjectConfig = false;

        $this->_productCondition = $condition;
    }

    public static function find(): ElementQueryInterface
    {
        return new ProductLabelQuery(static::class);
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(self::class);
    }

    /**
     * @throws \Exception
     */
    protected static function defineSources(string $context): array
    {
        $structure = Plugin::getInstance()->productLabels->getStructure();

        return [
            [
                'key' => '*',
                'label' => Craft::t('app', 'Product labels'),
                'hasThumbs' => false,
                'data' => ['slug' => 'all'],
                'structureId' => $structure->id,
                'structureEditable' => true,
                'defaultSort' => ['structure', 'asc'],
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        if ($controller instanceof ElementIndexesController) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }

        // Now figure out what we can do with it
        $actions = [];
        $elementsService = Craft::$app->getElements();

        // Set Status
        $actions[] = SetStatus::class;

        // Edit
        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Craft::t('app', 'Edit product label'),
        ]);

        // Duplicate
        $actions[] = Duplicate::class;

        // Delete
        $actions[] = Delete::class;

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('app', 'Product labels restored.'),
            'partialSuccessMessage' => Craft::t('app', 'Some product labels restored.'),
            'failMessage' => Craft::t('app', 'Product labels not restored.'),
        ]);

        return $actions;
    }

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    protected static function defineTableAttributes(): array
    {
        return [
            'id' => Craft::t('app', 'ID'),
            'uid' => Craft::t('app', 'UID'),
            'slug' => Craft::t('app', 'Slug'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['slug', 'dateCreated'];
    }

    protected function metaFieldsHtml(bool $static): string
    {
        return implode('', [
            $this->slugFieldHtml($static),
            parent::metaFieldsHtml($static),
        ]);
    }

    protected function cpEditUrl(): ?string
    {
        $path = sprintf('productlabels/%s', $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    public function canView(User $user): bool
    {
        return $user->can('editProductLabels');
    }

    public function canSave(User $user): bool
    {
        return $user->can('editProductLabels');
    }

    public function canDelete(User $user): bool
    {
        return $user->can('deleteProductLabels');
    }

    /**
     * @throws InvalidConfigException
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('app', 'Product Labels'),
                'url' => UrlHelper::url('productlabels'),
            ],
        ];

        $response->crumbs($crumbs);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%productlabels}}', [
                    'id' => $this->id,
                    'productCondition' => Json::encode($this->getProductCondition()->getConfig()),
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%productlabels}}', [
                    'productCondition' => Json::encode($this->getProductCondition()->getConfig()),
                    'dateFrom' => $this->dateFrom ? $this->dateFrom->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null,
                    'dateTo' => $this->dateTo ? $this->dateTo->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null,
                ], ['id' => $this->id])
                ->execute();
        }

        if (!$this->duplicateOf && $isNew) {
            Craft::$app->getStructures()->appendToRoot($this->structureId, $this, Structures::MODE_INSERT);
        }

        parent::afterSave($isNew);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'ProductLabel';
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }
}
