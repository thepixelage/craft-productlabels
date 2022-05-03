<?php

namespace thepixelage\productlabels\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;
use thepixelage\productlabels\elements\ProductLabel as ProductLabelElement;
use thepixelage\productlabels\gql\interfaces\elements\ProductLabel as ProductLabelInterface;
use thepixelage\productlabels\gql\types\elements\ProductLabel;
use thepixelage\productlabels\Plugin;

class ProductLabelType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        $productLabelTypes = Plugin::getInstance()->productLabels->getAllTypes();
        $gqlTypes = [];

        foreach ($productLabelTypes as $productLabelType) {
            $requiredContexts = ProductLabelElement::gqlScopesByContext($productLabelType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $type = static::generateType($productLabelType);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): mixed
    {
        /** @var \thepixelage\productlabels\models\ProductLabelType $context */
        $typeName = ProductLabelElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);

        $productLabelFields = Craft::$app->getGql()->prepareFieldDefinitions(
            array_merge(
                ProductLabelInterface::getFieldDefinitions(),
                $contentFieldGqlTypes
            ),
            $typeName
        );

        return GqlEntityRegistry::getEntity($typeName) ?:
            GqlEntityRegistry::createEntity(
                $typeName,
                new ProductLabel([
                    'name' => $typeName,
                    'fields' => function() use ($productLabelFields) {
                        return $productLabelFields;
                    },
                ])
            );
    }
}
