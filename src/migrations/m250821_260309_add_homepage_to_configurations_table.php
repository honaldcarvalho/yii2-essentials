<?php

use yii\db\Migration;

class m250821_260309_add_homepage_to_configurations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('configurations', 'homepage', $this->string(255)->defaultValue('https://croacworks.com.br')->after('host'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('configurations', 'homepage');
    }
}
