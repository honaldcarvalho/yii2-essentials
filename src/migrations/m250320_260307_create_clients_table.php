<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%clients}}`.
 */
class m250320_260307_create_clients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%clients}}', [
            //autentication fields
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->notNull(),
            'group_id' => $this->integer()->null(),
            'email' => $this->string(190)->notNull()->unique(),
            'username' => $this->string(64)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(32)->notNull(),
            'token_validate' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'password_reset_token' => $this->string(190)->null()->unique(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            //profile fields
            'file_id' => $this->bigInteger()->unsigned()->defaultValue(null),
            'fullname' => $this->string()->notNull(),
            'phone' => $this->string()->notNull(),
            'identity_number' => $this->string(18),
            'cpf_cnpj' => $this->string(18)->notNull(),
            'city_id' => $this->integer(),
            'state_id' => $this->integer(),
            'street' => $this->string(),
            'district' => $this->string(),
            'number' => $this->integer(),
            'postal_code' => $this->string(),
            'address_complement' => $this->string(),
            'notes' => 'MEDIUMTEXT',
        ],$tableOptions);

        $this->addForeignKey(
            'fk-clients-group_id',
            'clients',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-clients-state_id',
            'clients',
            'state_id',
            'states',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-clients-city_id',
            'clients',
            'city_id',
            'cities',
            'id',
            'RESTRICT'
        );


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        
        $this->dropTable('{{%clients}}');
        
        $this->delete  ('rules', [
            'group_id' => 2,
            'controller' => 'client',
        ]);

        $this->delete('menus', [
            'visible' => 'client;index',
            'url'     => '/client/index',
        ]);
    }
}
