<?php

use yii\db\Migration;

/**
 * Class m250819_000003_insert_default_user_and_roles
 *
 * Seeds the database with:
 *  - a default admin user (username: admin / password: admin123)
 *  - default roles for group_id = 1 with full access (controller='*', action='*')
 */
class m250819_000003_insert_default_user_and_roles extends Migration
{
    public function safeUp()
    {
        $time = time();

        $this->insert('configurations', [
            'slogan' => 'CroacWorks',
            'title' => 'System Basic',
            'description' => 'Basic Configuration',
            'host' => 'http://localhost',
            'title' => 'System Essential',
            'bussiness_name' => 'CroacWorks',
            'email' => 'suporte@croacworks.com.br',
            'email_service_id'=>1
        ]);

        $this->insert('groups', [
            'id'=>1,
            'name' => '*',
            'status'=>true
        ]);

        $this->insert('groups', [
            'id'=>2,
            'name' => 'Adminstrators',
            'status'=>true
        ]);

        // Insert default admin user
        $this->insert('{{%users}}', [
            'group_id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        // Insert default admin role (wildcard access for group 1)
        $this->insert('{{%roles}}', [
            'name' => 'Administrator',
            'controller' => '*',
            'action' => '*',
            'group_id' => 1,
            'status' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->insert('languages', [
            'code' => 'en-US',
            'name' => 'English (EUA)',
            'status'=> 1
        ]);

        $this->insert('languages', [
            'code' => 'pt-BR',
            'name' => 'Portugues (BR)',
            'status'=> 1
        ]);

        $this->insert('languages', [
            'code' => 'es',
            'name' => 'Español (ES)',
            'status'=> 0
        ]);

        $this->insert('folders', [
            'id' => 1,
            'name' => 'common',
            'description' => 'Common',
            'status'=>true
        ]);

        $this->insert('folders', [
            'id' => 2,
            'name' => 'images',
            'description' => 'Images',
            'status'=>true
        ]);

        $this->insert('folders', [
            'id' => 3,
            'name' => 'videos',
            'description' => 'Videos',
            'status'=>true
        ]);

        $this->insert('folders', [
            'id' => 4,
            'name' => 'documents',
            'description' => 'Documents',
            'status'=>true
        ]);
    }

    public function safeDown()
    {
        // Delete default admin user
        $this->delete('{{%users}}', ['username' => 'admin', 'email' => 'admin@example.com']);

        // Delete default admin role
        $this->delete('{{%roles}}', ['name' => 'Administrator', 'controller' => '*', 'action' => '*', 'group_id' => 1]);
    }
}
