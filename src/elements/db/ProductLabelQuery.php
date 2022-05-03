<?php

namespace thepixelage\productlabels\elements\db;

use Craft;
use craft\controllers\GraphqlController;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use Exception;
use thepixelage\productlabels\db\Table;
use thepixelage\productlabels\models\ProductLabelType;

class ProductLabelQuery extends ElementQuery
{
    public mixed $typeId = null;

    public function init(): void
    {
        if (!isset($this->withStructure)) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function type(mixed $value): self
    {
        if ($value instanceof ProductLabelType) {
            $this->structureId = ($value->structureId ?: false);
            $this->typeId = [$value->id];
        } elseif ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(Table::PRODUCTLABELTYPES)
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    public function typeId(mixed $value): self
    {
        $this->typeId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $productLabelsTableName = 'productlabels';
        $this->joinElementTable($productLabelsTableName);
        $this->query->select([
            sprintf('%s.typeId', $productLabelsTableName),
            sprintf('%s.productCondition', $productLabelsTableName),
            sprintf('%s.dateFrom', $productLabelsTableName),
            sprintf('%s.dateTo', $productLabelsTableName),
        ]);

        if (!Craft::$app->request->isCpRequest && !Craft::$app->request->isConsoleRequest && !(Craft::$app->controller instanceof GraphqlController)) {
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

        foreach ($elements as $key => $element) {
            $element->dateFrom = $element->dateFrom ? DateTimeHelper::toDateTime($element->dateFrom) : null;
            $element->dateTo = $element->dateTo ? DateTimeHelper::toDateTime($element->dateTo) : null;
        }

        return $elements;
    }
}
