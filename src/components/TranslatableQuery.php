<?php

namespace croacworks\essentials\components;

use yii\db\ActiveQuery;
use yii\db\Query;
use yii\db\Expression;

class TranslatableQuery extends ActiveQuery
{
    private bool $applyLanguageAutomatically = false;
    private bool $autoLanguageApplied = false;
    private $autoLanguage = null; // string|int|null

    /** Chame via Post::find()->enableAutoLanguage('pt-BR') se quiser fixar outro idioma 
     */
    public function enableAutoLanguage($language = null): self
    {
        $this->applyLanguageAutomatically = true;
        if ($language !== null) {
            $this->autoLanguage = $language;
        }
        return $this;
    }

    /** Opt-out para casos específicos (admin/relatórios) */
    public function disableAutoLanguage(): self
    {
        $this->applyLanguageAutomatically = false;
        return $this;
    }

    /** Hook central: injeta o fallback antes de montar o SQL */
    public function prepare($builder)
    {
        if ($this->applyLanguageAutomatically && !$this->autoLanguageApplied) {
            $lang = $this->autoLanguage ?? \Yii::$app->language; // ex.: 'pt-BR'
            $this->withLanguageFallback($lang, 'model_group_id', 'language_id');
            $this->autoLanguageApplied = true; // garante que só roda 1x
        }
        return parent::prepare($builder);
    }

    // ——— A partir daqui é a sua versão já corrigida ———

    public function withLanguageFallback(
        $language,
        string $groupColumn = 'model_group_id',
        string $languageColumn = 'language_id',
        string $languageTable = 'languages',
        string $languageCodeColumn = 'code',
        string $languageIdColumn = 'id'
    ): self {
        [$table, $alias] = $this->tableAndAlias();

        // Se não tiver grupo ou language no schema, não aplica o filtro
        if (!$this->hasColumn($table, $groupColumn) || !$this->hasColumn($table, $languageColumn)) {
            // ainda assim, garanta um alias consistente
            if ($alias === null) {
                $alias = 't';
                $this->from([$alias => $table]);
            }
            return $this; // sai sem mexer na query
        }

        $languageId = $this->resolveLanguageId($language, $languageTable, $languageCodeColumn, $languageIdColumn);

        // Garante alias
        if ($alias === null) {
            $alias = 't';
            $this->from([$alias => $table]);
        }

        // WHERE base opcional (status/list só se existirem)
        $baseWhere = ['and'];
        if ($this->hasColumn($table, 'status')) {
            $baseWhere[] = ['p.status' => true];
        }
        if ($this->hasColumn($table, 'list')) {
            $baseWhere[] = ['p.list' => true];
        }

        // priority por grupo
        $priorityWhere = ['and'];
        if ($this->hasColumn($table, 'status')) {
            $priorityWhere[] = ['p2.status' => true];
        }
        if ($this->hasColumn($table, 'list')) {
            $priorityWhere[] = ['p2.list' => true];
        }
        $priorityWhere[] = ['not', ["p2.$groupColumn" => null]];

        $priority = (new \yii\db\Query())
            ->select([
                $groupColumn,
                'min_priority' => new \yii\db\Expression("MIN(CASE WHEN p2.$languageColumn = :lang THEN 1 ELSE 2 END)")
            ])
            ->from(['p2' => $table])
            ->where($priorityWhere)
            ->groupBy($groupColumn)
            ->params([':lang' => $languageId]);

        // preferidos (melhor idioma por grupo)
        $preferWhere = $baseWhere;
        $preferWhere[] = ['not', ["p.$groupColumn" => null]];
        $preferWhere[] = new \yii\db\Expression("CASE WHEN p.$languageColumn = :lang THEN 1 ELSE 2 END = priority.min_priority");

        $prefer = (new \yii\db\Query())
            ->select(['id' => 'p.id'])
            ->from(['p' => $table])
            ->innerJoin(['priority' => $priority], "p.$groupColumn = priority.$groupColumn")
            ->where($preferWhere)
            ->params([':lang' => $languageId]);

        // sem grupo
        $noGroupWhere = $baseWhere;
        $noGroupWhere[] = ["p.$groupColumn" => null];

        $noGroup = (new \yii\db\Query())
            ->select(['id' => 'p.id'])
            ->from(['p' => $table])
            ->where($noGroupWhere);

        // UNION e aplica IN
        $sub = $prefer->union($noGroup, true);
        $this->andWhere(['in', "$alias.id", $sub]);

        return $this;
    }

    /** Verifica se a coluna existe na tabela (respeita prefixo {{%}}) */
    private function hasColumn(string $tableName, string $column): bool
    {
        $schema = \Yii::$app->db->getTableSchema($tableName, true);
        if ($schema === null) {
            // tenta com o nome "cru" da tabela
            $raw = \Yii::$app->db->schema->getRawTableName($tableName);
            $schema = \Yii::$app->db->getTableSchema($raw, true);
            if ($schema === null) {
                return false;
            }
        }
        return array_key_exists($column, $schema->columns);
    }

    private function resolveLanguageId($language, string $table, string $codeCol, string $idCol): int
    {
        if (is_int($language) || ctype_digit((string)$language)) {
            return (int)$language;
        }

        $code = trim((string)$language);

        $id = (new Query())
            ->from($table)
            ->select($idCol)
            ->where([$codeCol => $code])
            ->scalar();

        if ($id !== false && $id !== null) {
            return (int)$id;
        }

        $prefix = substr($code, 0, 2);
        $id = (new Query())
            ->from($table)
            ->select($idCol)
            ->where([$codeCol => $prefix])
            ->scalar();

        return $id ? (int)$id : 0;
    }

    protected function tableAndAlias(): array
    {
        $from = $this->from;
        if (empty($from)) {
            return [$this->modelClass::tableName(), null];
        }
        if (is_string($from)) {
            $s = trim($from);
            if (preg_match('/\s+AS\s+/i', $s)) {
                [$table, $alias] = preg_split('/\s+AS\s+/i', $s, 2);
                return [trim($table), trim($alias)];
            }
            $parts = preg_split('/\s+/', $s);
            if (count($parts) >= 2) {
                return [trim($parts[0]), trim($parts[1])];
            }
            return [$s, null];
        }
        $key = array_key_first($from);
        $val = $from[$key];
        if (is_string($key)) {
            return [trim($val), trim($key)];
        }
        if (is_string($val)) {
            $s = trim($val);
            if (preg_match('/\s+AS\s+/i', $s)) {
                [$table, $alias] = preg_split('/\s+AS\s+/i', $s, 2);
                return [trim($table), trim($alias)];
            }
            $parts = preg_split('/\s+/', $s);
            if (count($parts) >= 2) {
                return [trim($parts[0]), trim($parts[1])];
            }
            return [$s, null];
        }
        return [$this->modelClass::tableName(), null];
    }
}
