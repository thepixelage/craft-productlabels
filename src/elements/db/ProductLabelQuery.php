<?php

namespace thepixelage\productlabels\elements\db;

use Craft;
use craft\controllers\GraphqlController;
use craft\elements\db\ElementQuery;
use craft\helpers\DateTimeHelper;
use Exception;
use thepixelage\productlabels\elements\ProductLabel;

class ProductLabelQuery extends ElementQuery
{
    public function init(): void
    {
        if (!isset($this->withStructure)) {
            $this->withStructure = true;
        }

        parent::init();
    }

    protected function beforePrepare(): bool
    {
        $productLabelsTableName = 'productlabels';
        $this->joinElementTable($productLabelsTableName);
        $this->query->select([
            sprintf('%s.productCondition', $productLabelsTableName),
            sprintf('%s.userCondition', $productLabelsTableName),
            sprintf('%s.dateFrom', $productLabelsTableName),
            sprintf('%s.dateTo', $productLabelsTableName),
        ]);

        if (!Craft::$app->request->isConsoleRequest && (!Craft::$app->request->isCpRequest || Craft::$app->controller instanceof GraphqlController)) {
            $now = DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s');
            $this->subQuery->andWhere([
                'and',
                [
                    'or',
                    ['is', sprintf('%s.dateFrom', $productLabelsTableName), null],
                    ['<=', sprintf('%s.dateFrom', $productLabelsTableName), $now],
                ],
                [
                    'or',
                    ['is', sprintf('%s.dateTo', $productLabelsTableName), null],
                    ['>', sprintf('%s.dateTo', $productLabelsTableName), $now],
                ],
            ]);
        }

        return parent::beforePrepare();
    }

    /**
     * @throws Exception
     */
    public function afterPopulate(array $elements): array
    {
        parent::afterPopulate($elements);

        /**
         * @var ProductLabel $element
         */
        foreach ($elements as $key => $element) {
            $element->dateFrom = $element->dateFrom ? DateTimeHelper::toDateTime($element->dateFrom) : null;
            $element->dateTo = $element->dateTo ? DateTimeHelper::toDateTime($element->dateTo) : null;
        }

        return $elements;
    }
}
