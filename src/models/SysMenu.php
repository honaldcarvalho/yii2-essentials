<?php
namespace croacworks\essentials\models;

use croacworks\essentials\models\ModelCommon;

/**
 * @property int         $id
 * @property int|null    $parent_id
 * @property string      $label
 * @property string|null $icon
 * @property string|null $icon_style
 * @property string      $url
 * @property int         $order
 * @property int|bool    $only_admin
 * @property int|bool    $status
 * @property string|null $controller  // FQCN do controller
 * @property string|null $action      // "index" | "index;view" | "*"
 *
 * @property SysMenu     $parent
 * @property SysMenu[]   $children
 */
class SysMenu extends ModelCommon
{
    public static function tableName(): string
    {
        return '{{%sys_menus}}';
    }

    public function rules(): array
    {
        return [
            [['label', 'url'], 'required'],
            [['parent_id', 'order'], 'integer'],
            [['only_admin', 'status'], 'boolean'],
            [['label', 'url', 'controller', 'action'], 'string', 'max' => 255],
            [['icon', 'icon_style'], 'string', 'max' => 128],
            [['parent_id'], 'default', 'value' => null],
            [['url'], 'default', 'value' => '#'],
            [['order'], 'default', 'value' => 0],
            [['only_admin', 'status'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'parent_id'   => 'Parent',
            'label'       => 'Label',
            'icon'        => 'Ícone',
            'icon_style'  => 'Estilo do Ícone',
            'url'         => 'URL',
            'order'       => 'Ordem',
            'only_admin'  => 'Somente Admin',
            'status'      => 'Status',
            'controller'  => 'Controller (FQCN)',
            'action'      => 'Ações',
        ];
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])->orderBy(['order' => SORT_ASC]);
    }
}
