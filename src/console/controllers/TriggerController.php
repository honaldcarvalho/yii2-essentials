<?php
namespace console\controllers;

use croacworks\essentials\models\Trigger;
use Yii;
use yii\console\Controller;

/**
 * TriggerController (Console)
 * ---------------------------
 * Runs all active triggers periodically (cron).
 * Example: php yii trigger/run
 * Cron: * * * * * php /app/yii trigger/run >> /var/log/trigger.log 2>&1
 */
class TriggerController extends Controller
{
    /**
     * Execute all enabled triggers across models.
     */
    public function actionRun(): int
    {
        $triggers = Trigger::find()->where(['enabled' => true])->all();
        $totalFired = 0;

        echo "=== Running " . count($triggers) . " triggers ===\n";

        foreach ($triggers as $trigger) {
            if (!class_exists($trigger->model_class)) {
                echo "⚠️  Model not found: {$trigger->model_class}\n";
                continue;
            }

            $query = $trigger->model_class::find();
            $count = $query->count();

            if ($count == 0) {
                echo "ℹ️  No records for {$trigger->model_class}\n";
                continue;
            }

            echo "→ Checking {$count} records of {$trigger->model_class} for '{$trigger->name}'...\n";

            foreach ($query->batch(50) as $models) {
                foreach ($models as $model) {
                    if ($trigger->apply($model)) {
                        $totalFired++;
                        echo "✅ Trigger '{$trigger->name}' fired for ID #{$model->id}\n";
                    }
                }
            }
        }

        echo "\n=== Done! {$totalFired} triggers executed ===\n";
        return Controller::EXIT_CODE_NORMAL;
    }
}
