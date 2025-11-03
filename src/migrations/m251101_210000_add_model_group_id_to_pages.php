<?php

use yii\db\Migration;

class m251101_210000_add_model_group_id_to_pages extends Migration
{
    private const TABLE = '{{%pages}}';
    private const IDX_MODEL_GROUP = 'idx-pages-model_group_id';
    private const IDX_LIST = 'idx-pages-list';

    public function safeUp()
    {
        // Add columns if missing
        $this->addColumnIfNotExists(self::TABLE, 'model_group_id', $this->integer()->null()->after('id'));
        $this->addColumnIfNotExists(self::TABLE, 'list', $this->integer()->null()->after('id'));

        // Indexes if missing
        $this->createIndexIfNotExists(self::IDX_MODEL_GROUP, self::TABLE, 'model_group_id');
        $this->createIndexIfNotExists(self::IDX_LIST, self::TABLE, 'list');

        // Backfill
        if ($this->columnExists(self::TABLE, 'model_group_id')) {
            $this->execute('UPDATE ' . self::TABLE . ' SET model_group_id = id WHERE model_group_id IS NULL');
        }
    }

    public function safeDown()
    {
        // Drop indexes if exist
        $this->dropIndexIfExists(self::IDX_MODEL_GROUP, self::TABLE);
        $this->dropIndexIfExists(self::IDX_LIST, self::TABLE);

        // Drop columns if exist
        $this->dropColumnIfExists(self::TABLE, 'model_group_id');
        $this->dropColumnIfExists(self::TABLE, 'list');
    }

    // --- Helpers (minimal) ---

    private function columnExists(string $table, string $column): bool
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        return $schema && isset($schema->columns[$column]);
    }

    private function indexExists(string $name, string $table): bool
    {
        if ($this->db->driverName === 'mysql') {
            $raw = $this->db->schema->getRawTableName($table);
            $full = $this->db->tablePrefix . $raw;
            $q = $this->db->quoteTableName($full);
            $row = $this->db->createCommand("SHOW INDEX FROM {$q} WHERE Key_name = :n", [':n' => $name])->queryOne();
            return (bool)$row;
        }
        return false; // let createIndex handle duplicates on other drivers
    }

    private function addColumnIfNotExists(string $table, string $column, $type): void
    {
        if (!$this->columnExists($table, $column)) {
            $this->addColumn($table, $column, $type);
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->columnExists($table, $column)) {
            $this->dropColumn($table, $column);
        }
    }

    private function createIndexIfNotExists(string $name, string $table, $columns, bool $unique = false): void
    {
        if (!$this->indexExists($name, $table)) {
            $this->createIndex($name, $table, $columns, $unique);
        }
    }

    private function dropIndexIfExists(string $name, string $table): void
    {
        if ($this->indexExists($name, $table)) {
            $this->dropIndex($name, $table);
        }
    }
}