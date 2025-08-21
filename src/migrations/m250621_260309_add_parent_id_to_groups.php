<?php

use yii\db\Migration;

class m250621_260309_add_parent_id_to_groups extends Migration
{
    public function safeUp()
    {
        // Adiciona a coluna parent_id
        $this->addColumn('groups', 'parent_id', $this->integer()->null()->after('id'));

        // Cria a foreign key para self-reference
        $this->addForeignKey(
            'fk-groups-parent_id',
            'groups',
            'parent_id',
            'groups',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Remove a foreign key e a coluna
        $this->dropForeignKey('fk-groups-parent_id', 'groups');
        $this->dropColumn('groups', 'parent_id');
    }
}