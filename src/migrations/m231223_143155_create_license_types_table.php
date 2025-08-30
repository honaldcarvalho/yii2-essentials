<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%license_types}}`.
 */
class m231223_143155_create_license_types_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%license_types}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'description' => $this->text()->notNull(),
            'value' => $this->string('15')->notNull(),
            'contract' => $this->text()->notNull(),
            'max_devices' => $this->integer()->notNull()->defaultValue(1),
            'status' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->insert('license_types', [
            'name' => 'Single',
            'description' =>'Single Access',
            'value' => 100,
            'contract' => 'Basic',
            'max_devices' => 1,
            'status' => 1,
        ]);

        $this->insert('license_types', [
            'name' => '5 Users',
            'description' =>'5 Users Access',
            'value' => 100,
            'contract' => 'Basic',
            'max_devices' => 5,
            'status' => 1,
        ]);

        $this->insert('license_types', [
            'name' => '10 Users',
            'description' =>'10 Users Access',
            'value' => 100,
            'contract' => 'Basic',
            'max_devices' => 10,
            'status' => 1,
        ]);

        $this->insert('license_types', [
            'name' => 'Unlimite',
            'description' =>'Unlimite Users Access',
            'value' => 100,
            'contract' => 'Basic',
            'max_devices' => 0,
            'status' => 1,
        ]);

        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%license_types}}');
    }
}
