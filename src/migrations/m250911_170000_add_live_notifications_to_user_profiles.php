<?php

use yii\db\Migration;

class m250911_170000_add_live_notifications_to_user_profiles extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user_profiles}}', 'live_notifications', $this->boolean()->defautValue(false)->notNull()->after('id'));   
        $this->addColumn('{{%user_profiles}}', 'notifications_interval', $this->integer()->defaultValue(60000)->notNull()->after('id'));   
    }
    public function safeDown()
    {
        $this->dropColumn('{{%user_profiles}}', 'live_notifications');
        $this->dropColumn('{{%user_profiles}}', 'notifications_interval');
    }
}
