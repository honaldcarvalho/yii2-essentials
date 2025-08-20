<?php

use yii\db\Migration;

class m230419_000001_create_users extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->null(),
            'username' => $this->string(64)->notNull()->unique(),
            'email' => $this->string(190)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(32)->notNull(),
            'token_validate' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'password_reset_token' => $this->string(190)->null()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
        ]);
        $this->createIndex('idx_users_group', '{{%users}}', 'group_id');

    }
    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
