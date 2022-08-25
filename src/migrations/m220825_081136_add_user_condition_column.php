<?php

namespace thepixelage\productlabels\migrations;

use Craft;
use craft\db\Migration;
use thepixelage\productlabels\db\Table;

/**
 * m220825_081136_add_user_condition_column migration.
 */
class m220825_081136_add_user_condition_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::PRODUCTLABELS,
            'userCondition',
            $this->text()->after('productCondition'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::PRODUCTLABELS, 'userCondition');

        return false;
    }
}
