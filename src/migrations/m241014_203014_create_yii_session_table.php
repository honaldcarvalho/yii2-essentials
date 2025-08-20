<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%yii_session}}`.
 */
class m241014_203014_create_yii_session_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%yii_session}}', [
            'id' => $this->char(40)->notNull()->append('PRIMARY KEY'),
            'expire' => $this->integer(11)->notNull(),
            'data' => $this->binary(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%yii_session}}');
    }
}
