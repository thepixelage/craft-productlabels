<?php

namespace thepixelage\productlabels\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use thepixelage\productlabels\elements\ProductLabel as ProductLabelElement;
use thepixelage\productlabels\gql\interfaces\elements\ProductLabel as ProductLabelInterface;
use thepixelage\productlabels\gql\types\elements\ProductLabel;

class ProductLabelType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        $type = static::generateType($context);

        return [$type->name => $type];
    }

    public static function generateType(mixed $context): mixed
    {
        $context = $context ?: Craft::$app->getFields()->getLayoutByType(ProductLabelElement::class);

        $typeName = ProductLabelElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);
        $productLabelFields = Craft::$app->getGql()->prepareFieldDefinitions(
            array_merge(
                ProductLabelInterface::getFieldDefinitions(),
                $contentFieldGqlTypes
            ),
            $typeName
        );

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ProductLabel([
                'name' => $typeName,
                'fields' => function() use ($productLabelFields) {
                    return $productLabelFields;
                },
            ])
        );
    }
}
