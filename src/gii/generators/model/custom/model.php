<?php

use croacworks\essentials\models\ModelCommon;
use Yii;

/** @var $enum array */
/** @var $generator yii\gii\generators\model\Generator */
/** @var string $tableName */
/** @var string $className */
/** @var string $queryClassName */
/** @var yii\db\TableSchema $tableSchema */
/** @var array $properties */
/** @var array $labels */
/** @var array $rules */
/** @var array $relations */
/** @var array $relationsClassHints */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends ModelCommon
{
    public $verGroup = false;

    public static function tableName()
    {
        return '<?= $tableName ?>';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    public function rules()
    {
        return [
<?php foreach ($rules as $rule): ?>
            <?= $rule ?>,
<?php endforeach; ?>
        ];
    }

    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            '<?= $name ?>' => Yii::t('app', '<?= addslashes($label) ?>'),
<?php endforeach; ?>
        ];
    }

<?php foreach ($relations as $name => $relation): ?>
    /**
     * Gets query for [[<?= $name ?>]].
     * @return <?= $relationsClassHints[$name] . "\n" ?>
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }

<?php endforeach; ?>

<?php if ($queryClassName): ?>
    /**
     * {@inheritdoc}
     * @return <?= ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName ?>(get_called_class());
    }
<?php endif; ?>

<?php if (!empty($enum)): ?>
<?php foreach ($enum as $columnName => $columnData): ?>

    /**
     * column <?= $columnName ?> ENUM value labels
     * @return string[]
     */
    public static function <?= $columnData['funcOptsName'] ?>()
    {
        return [
<?php foreach ($columnData['values'] as $k => $value): ?>
<?php if ($generator->enableI18N): ?>
            self::<?= $value['constName'] ?> => Yii::t('<?= $generator->messageCategory ?>', '<?= $value['value'] ?>'),
<?php else: ?>
            self::<?= $value['constName'] ?> => '<?= $value['value'] ?>',
<?php endif; ?>
<?php endforeach; ?>
        ];
    }

    public function <?= $columnData['displayFunctionPrefix'] ?>()
    {
        return self::<?= $columnData['funcOptsName'] ?>()[\$this-><?= $columnName ?>] ?? null;
    }

<?php foreach ($columnData['values'] as $value): ?>

    public function <?= $columnData['isFunctionPrefix'] . $value['functionSuffix'] ?>()
    {
        return \$this-><?= $columnName ?> === self::<?= $value['constName'] ?>;
    }

    public function <?= $columnData['setFunctionPrefix'] . $value['functionSuffix'] ?>()
    {
        \$this-><?= $columnName ?> = self::<?= $value['constName'] ?>;
    }

<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
}
