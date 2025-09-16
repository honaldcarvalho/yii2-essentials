<?php
use yii\db\Migration;

/**
 * Migration to seed i18n messages from a CSV file (category,message,translation_pt_BR).
 * Place the CSV file named 'i18n_messages_pt_BR.csv' in the SAME DIRECTORY as this migration.
 * - Trims whitespace in message.
 * - Upserts into source_message and message (language 'pt-BR').
 * - Detects either default Yii i18n tables or 'dbtranslate' schema.
 */
class m20250916_030000_i18n_seed_messages_from_csv extends Migration
{
    private string $tblSource;
    private string $tblMessage;

    public function init()
    {
        parent::init();
        [$this->tblSource, $this->tblMessage] = $this->resolveI18nTables();
    }

    public function safeUp()
    {
        $csvPath = __DIR__ . DIRECTORY_SEPARATOR . 'i18n_messages_pt_BR.csv';
        if (!file_exists($csvPath)) {
            throw new \RuntimeException("CSV file not found: {$csvPath}");
        }

        $fh = fopen($csvPath, 'r');
        if ($fh === false) {
            throw new \RuntimeException("Unable to open CSV: {$csvPath}");
        }

        // Read header
        $header = fgetcsv($fh);
        if (!$header || count($header) < 3) {
            fclose($fh);
            throw new \RuntimeException("CSV header must be: category,message,translation_pt_BR");
        }

        $this->upsertIndexes();

        $count = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) continue;
            [$category, $message, $translation] = $row;
            $category   = trim((string)$category);
            $message    = trim((string)$message);
            $translation= (string)$translation;

            if ($category === '' || $message === '') continue;

            // 1) Ensure source_message
            $sourceId = (new \yii\db\Query())
                ->from($this->tblSource)
                ->select('id')
                ->where(['category' => $category, 'message' => $message])
                ->scalar($this->db);
            if (!$sourceId) {
                $this->insert($this->tblSource, ['category' => $category, 'message' => $message]);
                $sourceId = $this->db->getLastInsertID();
            }

            // 2) Ensure pt-BR translation
            $exists = (new \yii\db\Query())
                ->from($this->tblMessage)
                ->where(['id' => $sourceId, 'language' => 'pt-BR'])
                ->exists($this->db);

            if ($exists) {
                $this->update($this->tblMessage, ['translation' => $translation], ['id' => $sourceId, 'language' => 'pt-BR']);
            } else {
                $this->insert($this->tblMessage, ['id' => $sourceId, 'language' => 'pt-BR', 'translation' => $translation]);
            }

            $count++;
        }
        fclose($fh);

        echo "\nSeeded/updated {$count} translations from CSV.\n";
    }

    public function safeDown()
    {
        // We won't delete source messages, only remove pt-BR translations we inserted/updated.
        $csvPath = __DIR__ . DIRECTORY_SEPARATOR . 'i18n_messages_pt_BR.csv';
        if (!file_exists($csvPath)) {
            echo "\nCSV not found; nothing to rollback.\n";
            return;
        }

        $fh = fopen($csvPath, 'r');
        if ($fh === false) {
            echo "\nUnable to open CSV; aborting rollback.\n";
            return;
        }

        // skip header
        fgetcsv($fh);
        $removed = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) continue;
            [$category, $message] = [$row[0], $row[1]];
            $category = trim((string)$category);
            $message  = trim((string)$message);
            if ($category === '' || $message === '') continue;

            $sourceId = (new \yii\db\Query())
                ->from($this->tblSource)
                ->select('id')
                ->where(['category' => $category, 'message' => $message])
                ->scalar($this->db);
            if ($sourceId) {
                $this->delete($this->tblMessage, ['id' => $sourceId, 'language' => 'pt-BR']);
                $removed++;
            }
        }
        fclose($fh);

        echo "\nRemoved {$removed} pt-BR translations (source entries preserved).\n";
    }

    private function resolveI18nTables(): array
    {
        // Try * first
        $schema = $this->db->schema;
        if ($this->tableExists('source_message') && $this->tableExists('message')) {
            return ['source_message', 'message'];
        }
        // Fallback to standard Yii i18n tables
        return ['{{%source_message}}', '{{%message}}'];
    }

    private function tableExists(string $name): bool
    {
        try {
            return (bool)$this->db->getTableSchema($name, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function upsertIndexes(): void
    {
        // Create helpful indexes if missing
        try { $this->createIndex('idx_src_msg_category', $this->tblSource, 'category'); } catch (\Throwable $e) {}
        try { $this->createIndex('ux_src_category_message', $this->tblSource, ['category','message'], true); } catch (\Throwable $e) {}
        try { $this->createIndex('ux_msg_id_language', $this->tblMessage, ['id','language'], true); } catch (\Throwable $e) {}
    }
}