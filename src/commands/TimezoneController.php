<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;

/**
 * TimezoneController
 * ------------------
 * Sets the timezone to "America/Fortaleza" inside a Docker (Debian-based) container
 * and updates the MariaDB timezone using Yii::$app->db connection.
 *
 * Usage:
 * ```
 * php yii timezone/set-fortaleza
 * ```
 *
 * Works even if systemd/timedatectl are not available.
 *
 * @author Honald
 */
class TimezoneController extends Controller
{
    public function actionSetFortaleza(): int
    {
        $timezone = 'America/Fortaleza';
        echo "=== Setting container timezone to {$timezone} ===\n";

        // 1️⃣ Atualiza arquivos de timezone no container
        try {
            $zoneFile = "/usr/share/zoneinfo/{$timezone}";
            if (!file_exists($zoneFile)) {
                echo "[ERROR] Timezone file {$zoneFile} not found.\n";
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Atualiza /etc/localtime e /etc/timezone
            @unlink('/etc/localtime');
            symlink($zoneFile, '/etc/localtime');
            file_put_contents('/etc/timezone', "{$timezone}\n");

            echo "[OK] System timezone updated to {$timezone}\n";

            // Mostra hora atual para conferência
            echo "Current system time: " . date('Y-m-d H:i:s T') . "\n";
        } catch (\Throwable $e) {
            echo "[ERROR] Failed to update container timezone: {$e->getMessage()}\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n=== Updating MariaDB timezone using Yii DB config ===\n";

        /** @var Connection $db */
        $db = Yii::$app->db;

        try {
            $db->createCommand("SET GLOBAL time_zone = '{$timezone}'")->execute();
            $db->createCommand("SET time_zone = '{$timezone}'")->execute();

            $tzValues = $db->createCommand("
                SELECT @@global.time_zone AS global_tz, @@session.time_zone AS session_tz
            ")->queryOne();

            echo "[OK] MariaDB timezone updated successfully.\n";
            echo "Global: {$tzValues['global_tz']} | Session: {$tzValues['session_tz']}\n";
        } catch (\Throwable $e) {
            echo "[ERROR] Failed to update MariaDB timezone: {$e->getMessage()}\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n=== Verifying MariaDB timezone variables ===\n";

        try {
            $rows = $db->createCommand("
                SHOW VARIABLES LIKE 'time_zone';
                SHOW VARIABLES LIKE 'system_time_zone';
            ")->queryAll();

            foreach ($rows as $r) {
                echo "{$r['Variable_name']}: {$r['Value']}\n";
            }
        } catch (\Throwable $e) {
            echo "[WARN] Could not verify MariaDB timezone variables: {$e->getMessage()}\n";
        }

        echo "\n✅ Configuration completed successfully inside Docker container!\n";
        return ExitCode::OK;
    }
}