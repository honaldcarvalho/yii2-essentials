<?php

class m250819_000001_create_users extends \yii\db\Migration
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
            'password_reset_token' => $this->string(190)->null()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
        ]);
        $this->createIndex('idx_users_group', '{{%users}}', 'group_id');

        // seed admin (senha: admin123)
        $security = new \yii\base\DynamicModel([]);
        $hash = Yii::$app->security->generatePasswordHash('admin123');
        $auth = Yii::$app->security->generateRandomString();
        $time = time();
        $this->insert('{{%users}}', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => $hash,
            'auth_key' => $auth,
            'status' => 1,
            'group_id' => null,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }
    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
