<?php

use yii\db\Migration;
use yii\helpers\Inflector;

class m250911_170000_add_slug_to_page_sections extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%page_sections}}', true);

        if (!isset($table->columns['slug'])) {
            $this->addColumn('{{%page_sections}}', 'slug', $this->string(64)->notNull()->after('id'));
        }

        // índice único pro slug
        // (tente um nome curto de índice)
        $this->createIndex('ux_page_sections_slug', '{{%page_sections}}', ['slug'], true);

        // Popular slug para linhas existentes
        $rows = (new \yii\db\Query())
            ->from('{{%page_sections}}')
            ->all();

        foreach ($rows as $r) {
            // Tenta inferir um 'nome' — ajuste se seu schema usar outro campo (ex.: 'title', 'label', 'code')
            $base = $r['name'] ?? $r['code'] ?? $r['title'] ?? ('section-'.$r['id']);
            $slug = Inflector::slug((string)$base);

            // Evita slug vazio
            if ($slug === '' || $slug === '-') {
                $slug = 'section-'.$r['id'];
            }

            // Garante unicidade simples (se houver conflito raro)
            $try = $slug; $i = 2;
            while ((new \yii\db\Query())
                ->from('{{%page_sections}}')
                ->where(['slug' => $try])
                ->andWhere(['<>', 'id', $r['id']])
                ->exists()
            ) {
                $try = $slug.'-'.$i++;
            }

            $this->update('{{%page_sections}}', ['slug' => $try], ['id' => $r['id']]);
        }
    }

    public function safeDown()
    {
        // Se quiser manter a coluna, comente as duas linhas abaixo.
        $this->dropIndex('ux_page_sections_slug', '{{%page_sections}}');
        $this->dropColumn('{{%page_sections}}', 'slug');
    }
}
