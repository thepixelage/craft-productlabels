<?php

namespace thepixelage\productlabels\migrations;

use craft\db\Migration;
use thepixelage\productlabels\db\Table;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::PRODUCTLABELS)) {
            $this->createTable(Table::PRODUCTLABELS, [
                'id' => $this->integer()->notNull(),
                'productCondition' => $this->text(),
                'dateFrom' => $this->dateTime()->defaultValue(null),
                'dateTo' => $this->dateTime()->defaultValue(null),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                Table::PRODUCTLABELS, 'id', '{{%elements}}', 'id', 'CASCADE');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->tableExists(Table::PRODUCTLABELS)) {
            $this->dropTable(Table::PRODUCTLABELS);
        }

        return true;
    }
}
