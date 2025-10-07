<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;

/**
 * TimezoneController
 * ------------------
 * Sets the timezone to "America/Fortaleza" for both
 * the Linux system and the MariaDB/MySQL server.
 *
 * Usage:
 * ```
 * php yii timezone/set-fortaleza
 * ```
 *
 * Automatically uses Yii::$app->db credentials.
 *
 * @author Honald Carvalho
 */
class TimezoneController extends Controller
{
    /**
     * Sets timezone for system and MariaDB using Yii DB config.
     */
    public function actionSetFortaleza(): int
    {
        $timezone = 'America/Fortaleza';
        echo "=== Setting system timezone to {$timezone} ===\n";

        // 1️⃣ Ajuste do timezone do sistema
        $output = null;
        $result = null;
        exec("sudo timedatectl set-timezone {$timezone} 2>&1", $output, $result);

        if ($result === 0) {
            echo "[OK] System timezone set successfully:\n";
            system("timedatectl | grep 'Time zone'");
        } else {
            echo "[ERROR] Failed to set system timezone!\n";
            echo implode("\n", $output) . "\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n=== Updating MariaDB timezone using Yii DB config ===\n";

        /** @var Connection $db */
        $db = Yii::$app->db;
        $dsn = $db->dsn;
        $username = $db->username;
        $password = $db->password;

        // Extrai host (ex: mysql:host=localhost;dbname=test)
        preg_match('/host=([^;]+)/', $dsn, $matches);
        $host = $matches[1] ?? 'localhost';

        // 2️⃣ Atualiza timezone global e de sessão
        try {
            $db->createCommand("SET GLOBAL time_zone = '{$timezone}'")->execute();
            $db->createCommand("SET time_zone = '{$timezone}'")->execute();
            $tzValues = $db->createCommand("SELECT @@global.time_zone AS global_tz, @@session.time_zone AS session_tz")->queryOne();

            echo "[OK] MariaDB timezone updated successfully.\n";
            echo "Global: {$tzValues['global_tz']} | Session: {$tzValues['session_tz']}\n";
        } catch (\Throwable $e) {
            echo "[ERROR] Failed to update MariaDB timezone: " . $e->getMessage() . "\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n=== Updating MariaDB configuration file ===\n";

        $confPaths = ['/etc/mysql/my.cnf', '/etc/my.cnf'];
        $confFile = null;
        foreach ($confPaths as $path) {
            if (file_exists($path)) {
                $confFile = $path;
                break;
            }
        }

        if (!$confFile) {
            echo "[WARN] MariaDB configuration file not found.\n";
            goto restart;
        }

        $conf = file_get_contents($confFile);
        if (preg_match('/^default_time_zone/m', $conf)) {
            $conf = preg_replace(
                '/^default_time_zone.*/m',
                "default_time_zone='{$timezone}'",
                $conf
            );
        } else {
            $conf = preg_replace(
                '/^\[mysqld\]/m',
                "[mysqld]\ndefault_time_zone='{$timezone}'",
                $conf
            );
        }

        file_put_contents($confFile, $conf);
        echo "[OK] Directive default_time_zone applied in {$confFile}.\n";

        // 3️⃣ Reinicia o serviço do MariaDB
        restart:
        echo "Restarting MariaDB service...\n";
        exec('sudo systemctl restart mariadb 2>/dev/null', $output, $result);

        if ($result === 0) {
            echo "[OK] MariaDB restarted successfully.\n";
        } else {
            echo "[WARN] Could not restart MariaDB automatically. Please restart manually.\n";
        }

        // 4️⃣ Teste final direto via Yii
        try {
            $finalVars = $db->createCommand("
                SHOW VARIABLES LIKE 'time_zone';
                SHOW VARIABLES LIKE 'system_time_zone';
            ")->queryAll();

            echo "\n=== Final Test ===\n";
            foreach ($finalVars as $row) {
                echo "{$row['Variable_name']}: {$row['Value']}\n";
            }
        } catch (\Throwable $e) {
            echo "[WARN] Could not verify final timezone: " . $e->getMessage() . "\n";
        }

        echo "\n✅ Configuration completed successfully!\n";
        return ExitCode::OK;
    }
}
