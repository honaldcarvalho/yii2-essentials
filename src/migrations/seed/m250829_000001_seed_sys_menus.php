<?php

use yii\db\Migration;

class m250829_000001_seed_sys_menus extends Migration
{
    public function safeUp()
    {
        $table = '{{%sys_menus}}';

        $this->batchInsert($table, 
            ['parent_id','label','icon','icon_style','url','order','only_admin','status','show','controller','action','active','visible'],
            [
                [null,'System','fas fa-cogs','fas','#',13,0,1,1,null,null,null,null],
                [null,'Development','fas fa-file-code','fas','#',14,1,1,1,null,null,null,null],
                [13,'Page Header','','fas','/page-header/index',0,0,1,1,'app\\controllers\\PageHeaderController','index','page-header','index'],
                [13,'Menus','fas fa-bars','fas','/menu/index',2,0,1,1,'croacworks\\essentials\\controllers\\MenuController','index','menu','index'],
                [13,'Configurations','fas fa-clipboard-check','fas','/configuration/index',2,0,1,1,'croacworks\\essentials\\controllers\\ConfigurationController','index',null,'index'],
                [13,'Authentication','fas fa-key','fas','#',3,0,1,1,null,null,null,null],
                [13,'Dinamic Pages','fas fa-copy','fas','#',3,0,1,1,null,null,null,null],
                [13,'Storage','fas fa-hdd','fas','#',4,0,1,1,null,null,null,null],
                [13,'Notifications','fas fa-comment-alt','fas','#',5,0,1,1,null,null,null,null],
                [13,'Translations','fas fa-globe','fas','#',6,0,1,1,null,null,null,null],
                [13,'Email Services','fas fa-envelope','fas','/email-service/index',8,0,1,1,'croacworks\\essentials\\controllers\\EmailServiceController','index',null,'index'],
                [13,'Logs','fas fa-keyboard','fas','/log/index',1000,0,1,1,'croacworks\\essentials\\controllers\\LogController','index',null,'index'],
                [14,'Debug','fas fa-file-code','fas','debug/default/view',0,1,1,1,'app\\controllers\\DebugController','default',null,null],
                [14,'Gii','fas fa-file-code','fas','/gii',1,1,1,1,'app\\controllers\\GiiController','*',null,null],
                [25,'Groups','fas fa-users','fas','/group/index',0,0,1,1,'croacworks\\essentials\\controllers\\GroupController','index',null,'index'],
                [25,'Users','fas fa-user','fas','/user/index',1,0,1,1,'croacworks\\essentials\\controllers\\UserController','index',null,'index'],
                [25,'Roles','fas fa-person-booth','fas','/role/index',2,0,1,1,'croacworks\\essentials\\controllers\\RoleController','', 'role','croacworks\\essentials\\controllers\\RuleController;index'],
                [25,'License Types','fas fa-certificate','fas','/license-type/index',5,0,1,1,'croacworks\\essentials\\controllers\\LicenseTypeController','index',null,'index'],
                [25,'Licenses','fas fa-certificate','fas','/license/index',6,0,1,1,'croacworks\\essentials\\controllers\\LicenseController','index',null,'index'],
                [26,'Sections','fas fa-ellipsis-v','fas','/section/index',3,0,1,1,'croacworks\\essentials\\controllers\\SectionController','index',null,'index'],
                [26,'Pages','fas fa-file','fas','/page/index',5,0,1,1,'croacworks\\essentials\\controllers\\PageController','index',null,'index'],
                [27,'Folders','fas fa-folder','fas','/folder/index',0,0,1,1,'croacworks\\essentials\\controllers\\FolderController','index',null,'index'],
                [27,'Files','fas fa-file','fas','/file/index',0,0,1,1,'croacworks\\essentials\\controllers\\FileController','index',null,'index'],
                [28,'Messages','fas fa-comment-dots','fas','/notification-message/index',0,0,1,1,'croacworks\\essentials\\controllers\\NotificationMessageController','index',null,'index'],
                [28,'Notifications','fas fa-paper-plane','fas','/notification/index',7,0,1,1,'croacworks\\essentials\\controllers\\NotificationController','index',null,'index'],
                [29,'Languages','fas fa-language','fas','/language/index',0,0,1,1,'croacworks\\essentials\\controllers\\LanguageController','index',null,'index'],
                [29,'Source Messages','fas fa-comment','fas','/source-message/index',2,0,1,1,'croacworks\\essentials\\controllers\\SourceMessageController','index',null,'index'],
                [12,'Roles','fas fa-shield-halved','fas','/role/index',3,0,1,1,'croacworks\\essentials\\controllers\\RoleController','index',null,'index'],
            ]
        );
    }

    public function safeDown()
    {
        $this->delete('{{%sys_menus}}', ['label' => [
            'System','Development','Page Header','Menus','Configurations','Authentication','Dinamic Pages','Storage','Notifications','Translations','Email Services','Logs','Debug','Gii','Groups','Users','Roles','License Types','Licenses','Sections','Pages','Folders','Files','Messages','Languages','Source Messages'
        ]]);
    }
}
