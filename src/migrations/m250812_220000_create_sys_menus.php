<?php

use yii\db\Migration;


class m250812_220000_create_sys_menus extends Migration
{
    private string $new = '{{%sys_menus}}';

    public function safeUp()
    {
        // 1) Tabela nova
        $this->createTable($this->new, [
            'id'          => $this->primaryKey(),
            'parent_id'   => $this->integer()->null(),
            'label'       => $this->string(255)->notNull(),
            'icon'        => $this->string(128)->null(),
            'icon_style'  => $this->string(128)->null(),
            'url'         => $this->string(255)->notNull()->defaultValue('#'),
            'order'       => $this->integer()->notNull()->defaultValue(0),
            'only_admin'  => $this->boolean()->notNull()->defaultValue(false),
            'status'      => $this->boolean()->notNull()->defaultValue(true),
            'show'        => $this->boolean()->notNull()->defaultValue(true),  // hard toggle
            'controller'  => $this->string(255)->null(),   // FQCN
            'action'      => $this->string(255)->null(),   // usado p/ "active"
            'active'      => $this->string(60),
            'visible'     => $this->string(255)->null(),   // CSV de actions p/ exibição
        ]);

        $this->createIndex('idx-sys_menus-parent_id',  $this->new, 'parent_id');
        $this->createIndex('idx-sys_menus-status',     $this->new, 'status');
        $this->createIndex('idx-sys_menus-show',       $this->new, 'show');
        $this->createIndex('idx-sys_menus-controller', $this->new, 'controller');

        $this->addForeignKey(
            'fk-sys_menus-parent',
            $this->new,
            'parent_id',
            $this->new,
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->new, true) !== null) {
            $this->dropForeignKey('fk-sys_menus-parent', $this->new);
        }
        $this->dropTable($this->new);
    }
}
