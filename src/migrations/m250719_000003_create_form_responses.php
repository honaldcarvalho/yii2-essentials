<?php

use yii\db\Migration;

class m250719_000003_create_form_responses extends Migration
{
    public function safeUp()
    {

        $this->createTable('form_responses', [
            'id' => $this->bigPrimaryKey(),
            'group_id' => $this->integer()->null(),
            'dynamic_form_id' => $this->bigInteger()->notNull(),
            'response_data' => $this->json()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey(
            'fk-form_responses-group_id',
            'form_responses',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );
        $this->addForeignKey('fk_form_response_form', 'form_responses', 'dynamic_form_id', 'dynamic_forms', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-form_responses-field', 'form_responses');
        $this->dropTable('form_responses');
    }
}
