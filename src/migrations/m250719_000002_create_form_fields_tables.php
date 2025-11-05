<?php

use yii\db\Migration;

class m250719_000002_create_form_fields_tables extends Migration
{
    public function safeUp()
    {

        $this->createTable('form_fields', [
            'id' => $this->bigPrimaryKey(),
            'group_id' => $this->integer()->null(),
            'dynamic_form_id' => $this->bigInteger()->notNull(), // FK para dynamic_forms
            'label' => $this->string()->notNull(),
            'name' => $this->string()->notNull(),
            'type' => $this->integer()->notNull(), 
            'format' => $this->string()->notNull()->defaultValue('text'),
            'default' => $this->string(),
            'options' => $this->string(),
            'items' => $this->text(),
            'model_class' => $this->string(),
            'model_field' => $this->string(),
            'model_criteria' => $this->string(),
            'sql' => $this->text(),
            'script' => $this->text(),
            'order' => $this->integer()->notNull(),
            'show' => $this->boolean()->defaultValue(true),
            'status' => $this->boolean()->defaultValue(true)
        ]);

        $this->addForeignKey('fk-form_fields-template', 'form_fields', 'dynamic_form_id', 'dynamic_forms', 'id', 'CASCADE');
        $this->addForeignKey(
            'fk-form_fields-group_id',
            'form_fields',
            'group_id',
            'groups',
            'id',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-form_fields-template', 'form_fields');
        $this->dropTable('form_fields');
    }
}
