<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%logs}}`.
 */
class m230425_130812_create_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%logs}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'controller' => $this->string(50),
            'action' => $this->string(50),
            'ip' => $this->string(50),
            'device' => $this->string(150),
            'data' => 'LONGTEXT',
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()'))
        ]);

        $this->addForeignKey(
            'fk-logs-user_id',
            'logs',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );

        $this->insert('menus', [
            'id'=> 15,
            'menu_id' => 1,
            'label'   => 'Logs',
            'icon_style'=> 'fas',
            'icon'    => 'fas fa-keyboard',
            'visible' => 'log;index',
            'url'     => '/log/index',
            'path'  => 'weebz/controllers',
            'active'  => 'log',
            'order'   => 1000,
            'status'  => true
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%logs}}');
    }
}
