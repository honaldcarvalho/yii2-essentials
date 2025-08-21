<?php

use yii\db\Migration;

class m250821_002031_create_user_profiles_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user_profiles}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'language_id' => $this->integer()->defaultValue(1),//en-US
            'theme' => $this->string(10)->defaultValue('light'),
            'file_id' => $this->integer(),
            'fullname' => $this->string(),
            'cpf_cnpj' => $this->string(18),
            'phone' => $this->string()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull()->defaultValue(strtotime("now")),
            'updated_at' => $this->integer()->notNull()->defaultValue(strtotime("now")),
        ], $tableOptions);

        $this->addForeignKey(
            'fk-user_profiles-user_id',
            'user_profiles',
            'user_id',
            'users',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-user_profiles-file_id',
            'user_profiles',
            'file_id',
            'files',
            'id',
            'RESTRICT'
        );
        $this->addForeignKey(
            'fk-user_profiles-language_id',
            'user_profiles',
            'language_id',
            'languages',
            'id',
            'RESTRICT'
        );

    }
    

    public function down()
    {
        $this->dropTable('{{%user_profiles}}');
    }
}
