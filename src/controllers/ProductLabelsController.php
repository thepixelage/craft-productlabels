<?php

namespace thepixelage\productlabels\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Exception;
use thepixelage\productlabels\elements\ProductLabel;
use thepixelage\productlabels\models\ProductLabelType;
use thepixelage\productlabels\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
    public function actionDeleteProductLabelType()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $typeId = $this->request->getRequiredBodyParam('id');
        Plugin::getInstance()->productLabels->deleteTypeById($typeId);
        return $this->asSuccess();
    }

//    public function actionEditProductLabel(?int $productLabelId = null, ?ProductLabel $productLabel = null): Response
//    {
//        if (!$productLabel) {
//            if ($productLabelId) {
//                $productLabel = ProductLabel::find()
//                    ->id($productLabelId)
//                    ->one();
//            } else {
//                $productLabel = new ProductLabel();
//            }
//        }
//
//        return $this->renderTemplate('productlabels/productlabels/_edit', [
//            'element' => $productLabel,
//            'isNew' => $productLabel->id == null,
//        ]);
//    }
}
