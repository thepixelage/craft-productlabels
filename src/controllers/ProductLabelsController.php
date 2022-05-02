<?php

namespace thepixelage\productlabels\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Category;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use Exception;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\models\ProductLabelType;
use thepixelage\productlabels\Plugin;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use craft\commerce\Plugin as Commerce;

class ProductLabelsController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function actionTypeIndex(): Response
    {
        $this->requireAdmin();

        $types = Plugin::getInstance()->productLabels->getAllTypes();

        return $this->renderTemplate('productlabels/settings/types/index', [
            'productLabelTypes' => $types,
        ]);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionEditProductLabelType(?int $typeId = null, ?ProductLabelType $productLabelType = null): Response
    {
        $this->requireAdmin();

        $variables = [];

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Product Labels'),
                'url' => UrlHelper::url('settings/productlabels'),
            ],
        ];

        $variables['isNewType'] = false;

        if ($typeId !== null) {
            if ($productLabelType === null) {
                $productLabelType = Plugin::getInstance()->productLabels->getTypeById($typeId);

                if (!$productLabelType) {
                    throw new NotFoundHttpException('Product label type not found');
                }
            }

            $variables['title'] = trim($productLabelType->name) ?: Craft::t('app', 'Edit Product Label Type');
        } else {
            if ($productLabelType === null) {
                $productLabelType = new ProductLabelType();
                $variables['isNewType'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new product label type');
        }

        $variables['typeId'] = $typeId;
        $variables['productLabelType'] = $productLabelType;

        return $this->renderTemplate('productlabels/settings/types/_edit', $variables);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionSaveProductLabelType(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $typeId = $this->request->getBodyParam('typeId');

        if ($typeId) {
            $type = Plugin::getInstance()->productLabels->getTypeById($typeId);

            if (!$type) {
                throw new NotFoundHttpException('Product label type not found');
            }
        } else {
            $type = new ProductLabelType();
        }

        $type->name = $this->request->getBodyParam('name');
        $type->handle = $this->request->getBodyParam('handle');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ProductLabel::class;
        $type->setFieldLayout($fieldLayout);

        if (!Plugin::getInstance()->productLabels->saveType($type)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the product label type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'productLabelType' => $type,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Product label type saved.'));
        return $this->redirectToPostedUrl($type);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionDeleteProductLabelType(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $typeId = $this->request->getRequiredBodyParam('id');
        Plugin::getInstance()->productLabels->deleteTypeById($typeId);

        return $this->asSuccess();
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionProductLabelIndex(?string $typeHandle = null): Response
    {
        $types = Plugin::getInstance()->productLabels->getEditableTypes();

        if (empty($types)) {
            throw new ForbiddenHttpException('User not permitted to edit product labels');
        }

        $this->view->registerTranslations('app', [
            'New product label',
        ]);

        $indexJsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@thepixelage/productlabels/resources/js/ProductLabelIndex.js',
            true
        );

        $productLabelTypesJson = Json::encode($types, JSON_UNESCAPED_UNICODE);

        return $this->renderTemplate('productlabels/productlabels/_index', [
            'typeHandle' => $typeHandle,
            'types' => $types,
            'indexJsUrl' => $indexJsUrl,
            'productLabelTypesJson' => $productLabelTypesJson,
        ]);
    }

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function actionEdit(string $typeHandle, ?int $productLabelId = null, ?string $site = null, ?ProductLabel $productLabel = null): Response
    {
        $productLabelType = Plugin::getInstance()->productLabels->getTypeByHandle($typeHandle);

        if ($site !== null) {
            $siteHandle = $site;
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }

        if (!$productLabel) {
            if ($productLabelId) {
                $productLabel = ProductLabel::find()
                    ->id($productLabelId)
                    ->structureId($productLabelType->structureId)
                    ->site($site)
                    ->status(null)
                    ->one();

                if (!$productLabel) {
                    throw new BadRequestHttpException("Invalid product label ID: $productLabelId");
                }
            } else {
                $productLabel = new ProductLabel();
                $productLabel->typeId = $productLabelType->id;
                $productLabel->allPurchasables = true;
                $productLabel->allCategories = true;
            }
        }

        $this->enforceSitePermission($site);
        $this->enforceEditProductLabelPermissions($productLabel);

        if (Craft::$app->getIsMultiSite()) {
            $siteIds = array_map(function ($siteId) {
                return $siteId;
            }, $productLabel->getSupportedSites());

            if ($productLabel->enabled && $productLabel->id) {
                $siteStatusesQuery = $productLabel::find()
                    ->select(['elements_sites.siteId', 'elements_sites.enabled'])
                    ->id($productLabel->id)
                    ->siteId($siteIds)
                    ->status(null)
                    ->asArray();
                $siteStatuses = array_map(fn($enabled) => (bool)$enabled, $siteStatusesQuery->pairs());
            } else {
                // If the element isn't saved yet, assume other sites will share its current status
                $defaultStatus = !$productLabel->id && $productLabel->enabled && $productLabel->getEnabledForSite();
                $siteStatuses = array_combine($siteIds, array_map(fn() => $defaultStatus, $siteIds));
            }
        } else {
            /* @noinspection PhpUnhandledExceptionInspection */
            $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
            $siteStatuses = [];
        }

        $settingsJs = Json::encode([
            'canEditMultipleSites' => true,
            'canSaveCanonical' => true,
            'canonicalId' => $productLabel->getCanonicalId(),
            'elementType' => get_class($productLabel),
            'enablePreview' => false,
            'enabledForSite' => $productLabel->enabled && $productLabel->getEnabledForSite(),
            'siteId' => $productLabel->siteId,
            'siteStatuses' => $siteStatuses,
        ]);
        $js = <<<JS
new Craft.ElementEditor($('#main-form'), $settingsJs)
JS;
        $this->view->registerJs($js);

        $purchasableTypes = Commerce::getInstance()->getPurchasables()->getAllPurchasableElementTypes();

        return $this->renderTemplate('productlabels/productlabels/_edit', [
            'productLabel' => $productLabel,
            'productLabelType' => $productLabelType,
            'site' => $site,
            'siteIds' => $siteIds,
            'canUpdateSource' => true,
            'isNew' => ($productLabel->id == null),
            'sourceId' => $productLabel->getCanonicalId(),
            'sidebarHtml' => $productLabel->getSidebarHtml(false),
            'purchasableTypes' => array_map(function ($purchasableType) {
                return [
                    'name' => $purchasableType::displayName(),
                    'elementType' => $purchasableType,
                ];
            }, $purchasableTypes),
            'categories' => [],
            'categoryElementType' => Category::class,
            'categoryRelationshipType' => [
                'sourceElement' => Craft::t('commerce', 'Source - The category relationship field is on the purchasable'),
                'targetElement' => Craft::t('commerce', 'Target - The purchasable relationship field is on the category'),
                'element' => Craft::t('commerce', 'Either (Default) - The relationship field is on the purchasable or the category'),
            ],
        ]);
    }

    /**
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $productLabelId = $this->request->getBodyParam('sourceId');
        $productLabelTypeId = $this->request->getBodyParam('productLabelTypeId');
        $siteId = $this->request->getBodyParam('siteId');

        $type = Plugin::getInstance()->productLabels->getTypeById($productLabelTypeId);
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }

        if ($productLabelId) {
            $productLabel = ProductLabel::find()
                ->id($productLabelId)
                ->structureId($type->structureId)
                ->site($site)
                ->status(null)
                ->one();
        } else {
            $productLabel = new ProductLabel();
        }

        $productLabel->typeId = $type->id;
        $productLabel->siteId = $site->id;
        $productLabel->title = $this->request->getBodyParam('title', $productLabel->title);
        $productLabel->slug = $this->request->getBodyParam('slug', $productLabel->slug);
        $productLabel->enabled = (bool)$this->request->getBodyParam('enabled', $productLabel->enabled);
        $productLabel->setFieldValuesFromRequest($this->request->getParam('fieldsLocation', 'fields'));

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $productLabel->enabled = in_array(true, $enabledForSite, false) || $productLabel->enabled;
        } else {
            $productLabel->enabled = (bool)$this->request->getBodyParam('enabled', $productLabel->enabled);
        }
        $productLabel->setEnabledForSite($enabledForSite ?? $productLabel->getEnabledForSite());

        $this->enforceSitePermission($productLabel->getSite());
        $this->enforceEditProductLabelPermissions($productLabel);

        if ($productLabel->getEnabledForSite()) {
            $productLabel->setScenario(Element::SCENARIO_LIVE);
        }

        try {
            if (!Craft::$app->elements->saveElement($productLabel)) {
                if ($this->request->getAcceptsJson()) {
                    return $this->asJson(['errors' => $productLabel->getErrors()]);
                }

                $this->setFailFlash(Craft::t('productlabels', "Couldn’t save product label."));

                Craft::$app->urlManager->setRouteParams([
                    'productLabel' => $productLabel,
                ]);

                return null;
            }
        } catch (ElementNotFoundException|Throwable|\yii\base\Exception $e) {
        }

        $this->setSuccessFlash(Craft::t('productlabels', "Product label saved."));
        $this->redirectToPostedUrl($productLabel);

        return null;
    }

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws Throwable
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        $productLabelId = $this->request->getRequiredBodyParam('sourceId');
        /** @var ProductLabel $productLabel */
        $productLabel = Craft::$app->getElements()->getElementById($productLabelId, ProductLabel::class);

        if (!$productLabel) {
            throw new NotFoundHttpException("Product label not found");
        }

        $this->enforceDeleteFragmentPermissions($productLabel);

        if (!Craft::$app->getElements()->deleteElement($productLabel)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', "Couldn’t delete product label."));

            Craft::$app->getUrlManager()->setRouteParams([
                'productLabel' => $productLabel,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', "Product label deleted."));

        return $this->redirectToPostedUrl($productLabel);
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function enforceSitePermission(Site $site)
    {
        if (Craft::$app->getIsMultiSite()) {
            $this->requirePermission('editSite:' . $site->uid);
        }
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    protected function enforceEditProductLabelPermissions(ProductLabel $productLabel, bool $duplicate = false)
    {
        $permissionSuffix = ':' . $productLabel->getType()->uid;

        // Make sure the user is allowed to edit entries in this section
        $this->requirePermission('editProductLabels' . $permissionSuffix);

        // Is it a new entry?
        if (!$productLabel->id || $duplicate) {
            // Make sure they have permission to create new fragments in this zone
            $this->requirePermission('createProductLabels' . $permissionSuffix);
        }
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function enforceDeleteFragmentPermissions(ProductLabel $productLabel)
    {
        $userSession = Craft::$app->getUser();
        $user = Craft::$app->users->getUserById($userSession->id);

        if (!$productLabel->canDelete($user)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function enabledForSiteValue()
    {
        $enabledForSite = $this->request->getBodyParam('enabledForSite');
        if (is_array($enabledForSite)) {
            // Make sure they are allowed to edit all the posted site IDs
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
                throw new ForbiddenHttpException('User not permitted to edit the statuses for all the submitted site IDs');
            }
        }

        return $enabledForSite;
    }
}
