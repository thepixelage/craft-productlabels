<?php

namespace thepixelage\productlabels\helpers;

class Gql extends \craft\helpers\Gql
{
    public static function canQueryProductLabels(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['productlabeltypes']);
    }
}
