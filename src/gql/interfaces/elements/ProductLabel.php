<?php

namespace thepixelage\productlabels\gql\interfaces\elements;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use thepixelage\productlabels\gql\types\generators\ProductLabelType as ProductLabelTypeGenerator;

class ProductLabel extends Element
{
    public static function getName(): string
    {
        return 'ProductLabelInterface';
    }

    public static function getTypeGenerator(): string
    {
        return ProductLabelTypeGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all product labels.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        ProductLabelTypeGenerator::generateTypes();

        return $type;
    }
}
