<?php
/**
 * This is the template for generating the ActiveQuery class.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\model\Generator $generator */
/** @var string $tableName full table name */
/** @var string $className class name */
/** @var yii\db\TableSchema $tableSchema */
/** @var string[] $labels list of attribute labels (name => label) */
/** @var string[] $rules list of validation rules */
/** @var array $relations list of relations (name => relation declaration) */
/** @var string $className class name */
/** @var string $modelClassName related model class name */

$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}

echo "<?php\n";
?>

namespace <?= $generator->queryNs ?>;

use Yii;
use yii\data\ActiveDataProvider;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Group;

/**
 * This is the ActiveQuery class for [[<?= $modelFullClassName ?>]].
 *
 * @see <?= $modelFullClassName . "\n" ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->queryBaseClass, '\\') . "\n" ?>
{
    /**
     * Performs an advanced search with filtering, ordering, joins and group restrictions.
     *
     * @param array \$params
     * @param array \$options
     * @return ActiveDataProvider
     */
    public function search(\$params, \$options = ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC], 'order' => false])
    {
        \$this->scenario = self::SCENARIO_SEARCH;

        \$className = self::getClass();
        \$table     = static::tableName();
        \$pageSize  = 10;

        \$query = static::find();

        if (isset(\$options['select'])) {
            \$query->select(\$options['select']);
        }

        \$sort = ['defaultOrder' => ['id' => SORT_DESC]];
        if (isset(\$options['orderBy'])) {
            \$sort['defaultOrder'] = \$options['orderBy'];
        }
        if (isset(\$options['pageSize'])) {
            \$pageSize = (int)\$options['pageSize'];
        }

        // --- Order / Page expansion ---
        if (isset(\$options['order']) && \$options['order'] && !empty(\$options['order']) && count(\$params) > 0) {
            \$query->orderBy([\$options['order']['field'] => SORT_ASC]);

            if (
                isset(\$options['order']['flag']) &&
                \$options['order']['flag'] !== false &&
                isset(\$params[\$className][\$options['order']['flag']]) &&
                !empty(\$params[\$className][\$options['order']['flag']])
            ) {
                foreach (\$params[\$className] as \$field => \$search) {
                    if (\$search !== '' && \$search !== null && (!is_array(\$search) || array_filter(\$search, fn(\$v) => \$v !== '' && \$v !== null))) {
                        \$pageSize = 10000;
                        break;
                    }
                }
            }
        }

        // --- Optional JOINS ---
        if (!empty(\$options['join']) && is_array(\$options['join'])) {
            foreach (\$options['join'] as \$model) {
                [\$method, \$jt, \$criteria] = \$model;
                \$query->join(\$method, \$jt, \$criteria);
            }
        }

        // --- groupModel (external group join) ---
        if (isset(\$options['groupModel'])) {
            \$query->leftJoin(\$options['groupModel']['table'], \"{\$table}.{\$options['groupModel']['field']} = {\$options['groupModel']['table']}.id\");
        }

        \$dataProvider = new ActiveDataProvider([
            'query'      => \$query,
            'pagination' => ['pageSize' => \$pageSize],
            'sort'       => \$sort,
        ]);

        \$this->load(\$params);

        // --- Group visibility restriction ---
        \$user = AuthorizationController::User();
        if (\$this->verGroup && \$user) {
            if (!AuthorizationController::isMaster()) {
                \$group_ids = Group::familyIdsFromUser(\$user);
                \$group_ids[] = 1;
                \$group_ids = array_values(array_unique(array_map('intval', \$group_ids)));

                \$groupPath = method_exists(\$this, 'groupRelationPath') ? static::groupRelationPath() : null;

                if (\$groupPath) {
                    \$relationPath = '';
                    foreach (\$groupPath as \$i => \$relation) {
                        \$relationPath .= (\$i > 0 ? '.' : '') . \$relation;
                        \$query->joinWith([\$relationPath]);
                    }
                    \$tableAlias = Yii::createObject(static::class)
                        ->getRelation(end(\$groupPath))
                        ->modelClass::tableName();

                    \$query->andFilterWhere(['in', \"{\$tableAlias}.group_id\", \$group_ids]);
                } elseif (isset(\$options['groupModel'])) {
                    \$query->andFilterWhere(['in', \"{\$options['groupModel']['table']}.group_id\", \$group_ids]);
                } elseif (\$this->hasAttribute('group_id')) {
                    \$query->andFilterWhere([\"{\$table}.group_id\" => \$group_ids]);
                }
            }
        }

        if (!\$this->validate()) {
            return \$dataProvider;
        }

        // === Normalize alias / field names ===
        \$normalizeField = static function (string \$f): string {
            return strpos(\$f, '`') !== false ? \$f : str_replace('__', '.', \$f);
        };

        \$resolveColumn = static function (string \$baseTable, string \$field) use (\$normalizeField): string {
            \$field = \$normalizeField(\$field);
            if (strpos(\$field, '.') !== false || strpos(\$field, '`') !== false) {
                return \$field;
            }
            return \"{\$baseTable}.{\$field}\";
        };

        // === Apply filters ===
        foreach (\$params as \$field => \$search) {
            if (\$field === 'page' || !isset(\$params[\$className])) {
                continue;
            }

            foreach (\$params[\$className] as \$rawField => \$value) {
                if (\$value === '' || \$value === null) {
                    continue;
                }

                \$fieldType = gettype(\$value);
                if (is_numeric(\$value) && (int)\$value == \$value) {
                    \$fieldType = 'number';
                }

                \$parts = explode(':', \$rawField);
                \$fieldName = \$parts[0];
                if (count(\$parts) > 1) {
                    \$fieldType = \$parts[1];
                }

                // --- Date range support ---
                if (str_contains(\$fieldName, 'FDT')) {
                    [\$baseField, \$pos] = explode('FDT', \$fieldName);
                    \$column = \$resolveColumn(\$table, \$baseField);
                    if (\$pos === 'sod') {
                        \$query->andFilterWhere(['>=', \$column, \$value]);
                    } elseif (\$pos === 'eod') {
                        \$query->andFilterWhere(['<=', \$column, \$value]);
                    }
                    continue;
                }

                \$column = \$resolveColumn(\$table, \$fieldName);

                // --- Type detection ---
                if (\$fieldType === 'custom' && is_array(\$value) && count(\$value) >= 2) {
                    \$query->andFilterWhere([\$value[0], \$column, \$value[1]]);
                } elseif (\$fieldType === 'between' && is_array(\$value) && count(\$value) >= 2) {
                    \$query->andFilterWhere(['between', \$column, \$value[0], \$value[1]]);
                } elseif (\$fieldType === 'string') {
                    \$query->andFilterWhere(['like', \$column, \$value]);
                } elseif (is_array(\$value)) {
                    \$flat = array_values(array_filter(\$value, fn(\$v) => \$v !== '' && \$v !== null));
                    if (!empty(\$flat)) {
                        \$query->andFilterWhere([\$column => \$flat]);
                    }
                } else {
                    \$query->andFilterWhere([\$column => \$value]);
                }
            }
        }

        return \$dataProvider;
    }

    /**
     * {@inheritdoc}
     * @return <?= $modelFullClassName ?>[]|array
     */
    public function all(\$db = null)
    {
        return parent::all(\$db);
    }

    /**
     * {@inheritdoc}
     * @return <?= $modelFullClassName ?>|array|null
     */
    public function one(\$db = null)
    {
        return parent::one(\$db);
    }
}
