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
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\ElementSources;
use thepixelage\productlabels\elements\db\ProductLabelQuery;
use thepixelage\productlabels\models\ProductLabelType;
use thepixelage\productlabels\Plugin;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\Response;

class ProductLabel extends Element
{
    public ?int $typeId = null;

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

    public static function find(): ElementQueryInterface
    {
        return new ProductLabelQuery(static::class);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return parent::getFieldLayout() ?? $this->getType()->getFieldLayout();
    }

    protected static function defineSources(string $context): array
    {
        $sources = [];

        if ($context === ElementSources::CONTEXT_INDEX) {
            $types = Plugin::getInstance()->productLabels->getEditableTypes();
        } else {
            $types = Plugin::getInstance()->productLabels->getAllTypes();
        }

        foreach ($types as $type) {
            $sources[] = [
                'key' => 'type:' . $type->uid,
                'label' => Craft::t('site', $type->name),
                'data' => ['handle' => $type->handle],
                'criteria' => ['typeId' => $type->id],
                'structureId' => $type->structureId,
                'structureEditable' => Craft::$app->getRequest()->getIsConsoleRequest() || Craft::$app->getUser()->checkPermission("viewProductLabels:$type->uid"),
            ];
        }

        return $sources;
    }

    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^type:(.+)$/', $source, $matches) &&
            ($type = Plugin::getInstance()->productLabels->getTypeByUid($matches[1]))
        ) {
            $fieldLayouts[] = $type->getFieldLayout();
        }

        return $fieldLayouts;
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

        // Get the type we need to check permissions on
        if (preg_match('/^type:(\d+)$/', $source, $matches)) {
            $type = Plugin::getInstance()->productLabels->getTypeById($matches[1]);
        } elseif (preg_match('/^type:(.+)$/', $source, $matches)) {
            $type = Plugin::getInstance()->productLabels->getTypeByUid($matches[1]);
        }

        // Now figure out what we can do with it
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if (!empty($type)) {
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
        }

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('app', 'Product labels restored.'),
            'partialSuccessMessage' => Craft::t('app', 'Some product labels restored.'),
            'failMessage' => Craft::t('app', 'Product labels not restored.'),
        ]);

        return $actions;
    }

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
        return [];
    }

    protected function metaFieldsHtml(bool $static): string
    {
        return implode('', [
            $this->slugFieldHtml($static),
            parent::metaFieldsHtml($static),
        ]);
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        return true;
    }

    /**
     * @throws InvalidConfigException
     */
    protected function cpEditUrl(): ?string
    {
        $type = $this->getType();

        $path = sprintf('productlabels/%s/%s', $type->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getPostEditUrl(): ?string
    {
        $type = $this->getType();

        return UrlHelper::cpUrl("productlabels/$type->handle");
    }

    /**
     * @throws InvalidConfigException
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $type = $this->getType();

        $crumbs = [
            [
                'label' => Craft::t('app', 'Product Labels'),
                'url' => UrlHelper::url('productlabels'),
            ],
            [
                'label' => Craft::t('site', $type->name),
                'url' => UrlHelper::url('productlabels/' . $type->handle),
            ],
        ];

        $response->crumbs($crumbs);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getType(): ProductLabelType
    {
        if (!isset($this->typeId)) {
            throw new InvalidConfigException('Product label is missing its type ID');
        }

        $type = Plugin::getInstance()->productLabels->getTypeById($this->typeId);

        if (!$type) {
            throw new InvalidConfigException('Invalid product label type ID: ' . $this->typeId);
        }

        return $type;
    }

    /**
     * @throws InvalidConfigException
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->structureId = $this->getType()->structureId;

        return parent::beforeSave($isNew);
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
                    'typeId' => $this->typeId,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%productlabels}}', [
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}
