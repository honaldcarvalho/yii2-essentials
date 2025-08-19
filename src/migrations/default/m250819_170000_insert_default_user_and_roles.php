<?php

use yii\db\Migration;

/**
 * Class m250819_170000_insert_default_user_and_roles
 *
 * This migration creates default roles and a default admin user
 * so the system can be accessed right after installation.
 */
class m250819_170000_insert_default_user_and_roles extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // --- Insert default roles ---
        $this->batchInsert('{{%roles}}', ['id', 'name', 'description', 'status'], [
            [1, 'admin', 'System administrator', 1],
            [2, 'operator', 'System operator', 1],
            [3, 'patient', 'System patient', 1],
        ]);

        // --- Insert default admin user ---
        $passwordHash = Yii::$app->security->generatePasswordHash('admin123'); // default password

        $this->insert('{{%user}}', [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => $passwordHash,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'status' => 10, // active
            'created_at' => time(),
            'updated_at' => time(),
            'role_id' => 1, // link to "admin" role
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove default user
        $this->delete('{{%user}}', ['id' => 1]);

        // Remove default roles
        $this->delete('{{%roles}}', ['id' => [1, 2, 3]]);
    }
}
