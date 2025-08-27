<?php

use yii\db\Migration;

/**
 * Handles adding columns `slug` and `expires_at` to table `files`.
 */
class m250825_000002_add_slug_and_expires_to_files extends Migration
{
    public function safeUp()
    {
        // adiciona a coluna slug (32 caracteres é um bom tamanho)
        $this->addColumn('{{%files}}', 'slug', $this->string(32)->null()->unique()->after('id'));
        // adiciona expires_at (timestamp UNIX)
        $this->addColumn('{{%files}}', 'expires_at', $this->integer()->null()->after('slug'));

        // popula registros existentes
        $rows = (new \yii\db\Query())->from('{{%files}}')->select(['id'])->all();
        foreach ($rows as $row) {
            $slug = $this->generateSlug(32);
            $expires = time() + 3600; // 1 hora (60m * 60s)
            $this->update('{{%files}}', [
                'slug'       => $slug,
                'expires_at' => $expires,
            ], ['id' => $row['id']]);
        }
    }

    private function generateSlug($len = 16)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $n = strlen($alphabet);
        $s = '';
        for ($i = 0; $i < $len; $i++) {
            $s .= $alphabet[random_int(0, $n - 1)];
        }
        return $s;
    }

    public function safeDown()
    {
        $this->dropColumn('{{%files}}', 'expires_at');
        $this->dropColumn('{{%files}}', 'slug');
    }
}
