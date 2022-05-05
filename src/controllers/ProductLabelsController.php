<?php

namespace thepixelage\productlabels\controllers;

use Craft;
use craft\base\Element;
use craft\commerce\Plugin as Commerce;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\models\Site;
use craft\web\Controller;
use Exception;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\Plugin;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ProductLabelsController extends Controller
{
    public function actionIndex(?string $typeHandle = null): Response
    {
        $this->view->registerTranslations('app', [
            'New product label',
        ]);

        return $this->renderTemplate('productlabels/productlabels/_index');
    }

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function actionEdit(?int $productLabelId = null, ?string $site = null, ?ProductLabel $productLabel = null): Response
    {
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
                    ->site($site)
                    ->status(null)
                    ->one();

                if (!$productLabel) {
                    throw new BadRequestHttpException("Invalid product label ID: $productLabelId");
                }
            } else {
                $productLabel = new ProductLabel();
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
            'site' => $site,
            'siteIds' => $siteIds,
            'canUpdateSource' => true,
            'isNew' => ($productLabel->id == null),
            'sourceId' => $productLabel->getCanonicalId(),
            'sidebarHtml' => $productLabel->getSidebarHtml(false),
        ]);
    }

    /**
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $productLabelId = $this->request->getBodyParam('sourceId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }

        if ($productLabelId) {
            $productLabel = ProductLabel::find()
                ->id($productLabelId)
                ->site($site)
                ->status(null)
                ->one();
        } else {
            $productLabel = new ProductLabel();
        }

        $productLabel->siteId = $site->id;
        $productLabel->title = $this->request->getBodyParam('title', $productLabel->title);
        $productLabel->slug = $this->request->getBodyParam('slug', $productLabel->slug);

        $structure = Plugin::getInstance()->productLabels->getStructure();
        $productLabel->structureId = $structure->id;

        $productLabel->enabled = (bool)$this->request->getBodyParam('enabled', $productLabel->enabled);
        $productLabel->setFieldValuesFromRequest($this->request->getParam('fieldsLocation', 'fields'));

        $productLabel->setProductCondition($this->request->getBodyParam('productCondition'));

        $dateFromParams = $this->request->getBodyParam('dateFrom');
        $dateToParams = $this->request->getBodyParam('dateTo');
        $productLabel->dateFrom = $dateFromParams['date'] ? DateTimeHelper::toDateTime($dateFromParams) : null;
        $productLabel->dateTo = $dateToParams['date'] ? DateTimeHelper::toDateTime($dateToParams) : null;

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
     */
    protected function enforceEditProductLabelPermissions(ProductLabel $productLabel, bool $duplicate = false)
    {
        // Make sure the user is allowed to edit product labels
        $this->requirePermission('editProductLabels');

        // Is it a new entry?
        if (!$productLabel->id || $duplicate) {
            // Make sure they have permission to create new product labels
            $this->requirePermission('createProductLabels');
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
