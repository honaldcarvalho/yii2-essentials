<?php

use yii\db\Migration;

class m250829_000001_seed_sys_menus extends Migration
{
    public function safeUp()
    {
        $table = '{{%sys_menus}}';

        // ATENÇÃO: se já existir conteúdo nessa tabela, verifique conflitos de PK (id)
        $this->execute('SET FOREIGN_KEY_CHECKS=0');

        // Insere PAI com ids fixos (batem com os parent_id usados abaixo)
        $this->batchInsert($table,
            ['id','parent_id','label','icon','icon_style','url','order','only_admin','status','show','controller','action','active','visible'],
            [
                // Top-level
                [13,null,'System','fas fa-cogs','fas','#',13,0,1,1,null,null,null,null],
                [14,null,'Development','fas fa-file-code','fas','#',14,1,1,1,null,null,null,null],

                // System children (alguns são pais de outros)
                [15,13,'Page Header','', 'fas','/page-header/index',0,0,1,1,'app\\controllers\\PageHeaderController','index','page-header','index'],
                [16,13,'Menus','fas fa-bars','fas','/menu/index',2,0,1,1,'croacworks\\essentials\\controllers\\MenuController','index','menu','index'],
                [17,13,'Configurations','fas fa-clipboard-check','fas','/configuration/index',2,0,1,1,'croacworks\\essentials\\controllers\\ConfigurationController','index',null,'index'],

                // Pais intermediários sob System (ids FIXOS referenciados depois)
                [25,13,'Authentication','fas fa-key','fas','#',3,0,1,1,null,null,null,null],
                [26,13,'Dinamic Pages','fas fa-copy','fas','#',3,0,1,1,null,null,null,null],
                [27,13,'Storage','fas fa-hdd','fas','#',4,0,1,1,null,null,null,null],
                [28,13,'Notifications','fas fa-comment-alt','fas','#',5,0,1,1,null,null,null,null],
                [29,13,'Translations','fas fa-globe','fas','#',6,0,1,1,null,null,null,null],

                // Demais filhos de System
                [32,13,'Email Services','fas fa-envelope','fas','/email-service/index',8,0,1,1,'croacworks\\essentials\\controllers\\EmailServiceController','index',null,'index'],
                [33,13,'Logs','fas fa-keyboard','fas','/log/index',1000,0,1,1,'croacworks\\essentials\\controllers\\LogController','index',null,'index'],

                // Development children
                [34,14,'Debug','fas fa-file-code','fas','debug/default/view',0,1,1,1,'app\\controllers\\DebugController','default',null,null],
                [35,14,'Gii','fas fa-file-code','fas','/gii',1,1,1,1,'app\\controllers\\GiiController','*',null,null],

                // Authentication children (parent_id = 25)
                [36,25,'Groups','fas fa-users','fas','/group/index',0,0,1,1,'croacworks\\essentials\\controllers\\GroupController','index',null,'index'],
                [37,25,'Users','fas fa-user','fas','/user/index',1,0,1,1,'croacworks\\essentials\\controllers\\UserController','index',null,'index'],
                [38,25,'Roles','fas fa-person-booth','fas','/role/index',2,0,1,1,'croacworks\\essentials\\controllers\\RoleController','', 'role','croacworks\\essentials\\controllers\\RuleController;index'],
                [39,25,'License Types','fas fa-certificate','fas','/license-type/index',5,0,1,1,'croacworks\\essentials\\controllers\\LicenseTypeController','index',null,'index'],
                [40,25,'Licenses','fas fa-certificate','fas','/license/index',6,0,1,1,'croacworks\\essentials\\controllers\\LicenseController','index',null,'index'],

                // Dinamic Pages children (parent_id = 26)
                [41,26,'Sections','fas fa-ellipsis-v','fas','/section/index',3,0,1,1,'croacworks\\essentials\\controllers\\SectionController','index',null,'index'],
                [42,26,'Pages','fas fa-file','fas','/page/index',5,0,1,1,'croacworks\\essentials\\controllers\\PageController','index',null,'index'],

                // Storage children (parent_id = 27)
                [43,27,'Folders','fas fa-folder','fas','/folder/index',0,0,1,1,'croacworks\\essentials\\controllers\\FolderController','index',null,'index'],
                [44,27,'Files','fas fa-file','fas','/file/index',0,0,1,1,'croacworks\\essentials\\controllers\\FileController','index',null,'index'],

                // Notifications children (parent_id = 28)
                [45,28,'Messages','fas fa-comment-dots','fas','/notification-message/index',0,0,1,1,'croacworks\\essentials\\controllers\\NotificationMessageController','index',null,'index'],
                [46,28,'Notifications','fas fa-paper-plane','fas','/notification/index',7,0,1,1,'croacworks\\essentials\\controllers\\NotificationController','index',null,'index'],

                // Translations children (parent_id = 29)
                [47,29,'Languages','fas fa-language','fas','/language/index',0,0,1,1,'croacworks\\essentials\\controllers\\LanguageController','index',null,'index'],
                [48,29,'Source Messages','fas fa-comment','fas','/source-message/index',2,0,1,1,'croacworks\\essentials\\controllers\\SourceMessageController','index',null,'index'],

                // EXTRA: "Roles" direto sob System (ajustado parent_id=13; seu SQL original usava 12)
                [49,13,'Roles','fas fa-shield-halved','fas','/role/index',3,0,1,1,'croacworks\\essentials\\controllers\\RoleController','index',null,'index'],
            ]
        );

        $this->execute('SET FOREIGN_KEY_CHECKS=1');
    }

    public function safeDown()
    {
        // Remove pelos ids inseridos acima
        $this->delete('{{%sys_menus}}', ['id' => [
            13,14,15,16,17,25,26,27,28,29,32,33,34,35,36,37,38,39,40,
            41,42,43,44,45,46,47,48,49
        ]]);
    }
}
