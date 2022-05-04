<?php

namespace thepixelage\productlabels\controllers;

use Craft;
use craft\errors\BusyResourceException;
use craft\errors\StaleResourceException;
use craft\web\Controller;
use thepixelage\productlabels\elements\ProductLabel;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class SettingsController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function actionSettings(): ?Response
    {
        $this->requireAdmin();

        return $this->renderTemplate('productlabels/settings');
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     * @throws StaleResourceException
     * @throws Exception
     * @throws ErrorException
     * @throws BusyResourceException
     */
    public function actionSave(): Response
    {
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ProductLabel::class;

        $configData = [
            'fieldLayouts' => [
                $fieldLayout->uid => $fieldLayout->getConfig()
            ]
        ];

        Craft::$app->getProjectConfig()->set('productLabels', $configData, "Save product label config");

        return $this->renderTemplate('productlabels/settings');
    }
}
