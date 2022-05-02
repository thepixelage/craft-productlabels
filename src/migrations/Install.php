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
        if (!$this->db->tableExists(Table::PRODUCTLABELTYPES)) {
            $this->createTable(Table::PRODUCTLABELTYPES, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'fieldLayoutId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateDeleted' => $this->dateTime(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(Table::PRODUCTLABELS)) {
            $this->createTable(Table::PRODUCTLABELS, [
                'id' => $this->integer()->notNull(),
                'typeId' => $this->integer()->notNull(),
                'conditions' => $this->text(),
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

        if ($this->db->tableExists(Table::PRODUCTLABELTYPES)) {
            $this->dropTable(Table::PRODUCTLABELTYPES);
        }

        return true;
    }
}
