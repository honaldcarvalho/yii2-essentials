<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_group}}`.
 */
class m230425_124535_create_users_groups_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%users_groups}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'group_id' => $this->integer(),
        ]);

        // add foreign keys for table `user_group`
        $this->addForeignKey(
            'fk-users_groups-user_id',
            'users_groups',
            'user_id',
            'users',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-users_groups-group_id',
            'users_groups',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );

    }
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%users_group}}');
    }
}
