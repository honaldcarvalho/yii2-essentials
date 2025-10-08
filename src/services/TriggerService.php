<?php
namespace croacworks\essentials\services;

use croacworks\essentials\models\Trigger;
use Yii;

/**
 * TriggerService
 * --------------
 * Evaluates triggers for a given model instance or class.
 *
 * Usage:
 *   TriggerService::runForModel($license);
 *   TriggerService::runForClass(common\models\License::class);
 */
class TriggerService
{
    /**
     * Run all triggers related to the given model instance.
     */
    public static function runForModel($model): void
    {
        $class = get_class($model);

        $triggers = Trigger::find()
            ->where(['model_class' => $class, 'enabled' => true])
            ->all();

        foreach ($triggers as $trigger) {
            $trigger->apply($model);
        }
    }

    /**
     * Run all triggers for a given model class (for batch runs).
     */
    public static function runForClass(string $class): void
    {
        if (!class_exists($class)) return;

        $triggers = Trigger::find()
            ->where(['model_class' => $class, 'enabled' => true])
            ->all();

        foreach ($triggers as $trigger) {
            foreach ($class::find()->batch(50) as $models) {
                foreach ($models as $model) {
                    $trigger->apply($model);
                }
            }
        }
    }
}
