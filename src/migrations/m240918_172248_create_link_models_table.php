<?php

use croacworks\essentials\models\Menu;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%page_headers}}`.
 */
class m240918_172248_create_link_models_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%link_models}}', [
            'id' => $this->primaryKey(),
            'model_id' => $this->integer(),
            'model' => $this->string()->notNull(),
            'parent_id' => $this->integer(),
            'parent_model' => $this->string()->notNull(),
            'status' => $this->boolean()->defaultValue(true),
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $this->dropTable('{{%link_models}}');
    }
}
