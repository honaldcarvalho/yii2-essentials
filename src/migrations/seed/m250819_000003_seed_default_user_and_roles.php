<?php

use croacworks\essentials\models\User;
use yii\db\Migration;

/**
 * Class m250819_000003_insert_default_user_and_roles
 *
 * Seeds the database with:
 *  - a default admin user (username: admin / password: admin123)
 *  - default roles for group_id = 1 with full access (controller='*', action='*')
 */
class m250819_000003_seed_default_user_and_roles extends Migration
{
    public function safeUp()
    {
        $time = time();

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

        $this->insert('configurations', [
            'slogan' => 'CroacWorks',
            'title' => 'System Basic',
            'description' => 'Basic Configuration',
            'host' => 'https://localhost',
            'title' => 'System Essential',
            'bussiness_name' => 'CroacWorks',
            'email' => 'suporte@croacworks.com.br',
            'email_service_id'=>1
        ]);

        $this->insert('groups', [
            'id'=>1,
            'level' => 'free',
            'name' => '*',
            'status'=>true
        ]);

        $this->insert('groups', [
            'id'=>2,
            'level' => 'master',
            'name' => 'Adminstrators',
            'status'=>true
        ]);

        // Insert default admin user
        $this->insert('{{%users}}', [
            'group_id' => 2,
            'username' => 'admin',
            'email' => 'suporte@croacworks.com.br',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'access_token' => Yii::$app->security->generateRandomString(),
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->insert('{{%user_profiles}}', [
            'user_id' => 1,
            'language_id' => 1,//en-US
            'theme' => 'dark',
            'file_id' => null,
            'fullname' => "System Administrator",
            'cpf_cnpj' => null,
            'phone' => null
        ]);

        $this->insert('users_groups', [
            'user_id' => 1,
            'group_id' => 2,
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
