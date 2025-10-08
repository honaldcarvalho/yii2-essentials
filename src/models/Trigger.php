<?php
namespace croacworks\essentials\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Trigger Model
 * -------------
 * Defines a condition and an action to execute automatically.
 */
class Trigger extends ActiveRecord
{
    public static function tableName() { return '{{%triggers}}'; }

    public function rules()
    {
        return [
            [['name', 'model_class', 'expression', 'action_type'], 'required'],
            [['expression'], 'string'],
            [['group_id', 'cooldown_seconds'], 'integer'],
            [['enabled'], 'boolean'],
            [['last_triggered_at', 'created_at'], 'safe'],
            [['name', 'model_class', 'action_type', 'action_target'], 'string', 'max' => 255],
        ];
    }

    public function getLogs()
    {
        return $this->hasMany(TriggerLog::class, ['trigger_id' => 'id'])
            ->orderBy(['executed_at' => SORT_DESC]);
    }

    public function canExecute(): bool
    {
        if (!$this->enabled) return false;
        if (!$this->last_triggered_at) return true;

        if ($this->cooldown_seconds > 0) {
            $last = strtotime($this->last_triggered_at);
            return (time() - $last) >= $this->cooldown_seconds;
        }
        return true;
    }

    public function apply($model): bool
    {
        try {
            if (!$this->canExecute()) return false;

            $varName = strtolower((new \ReflectionClass($model))->getShortName());
            extract([$varName => $model]);
            $result = eval("return ({$this->expression});");

            if ($result) {
                $this->executeAction($model);
                $this->last_triggered_at = date('Y-m-d H:i:s');
                $this->save(false);
                TriggerLog::add($this, $model, "Triggered successfully");
                return true;
            }
        } catch (\Throwable $e) {
            TriggerLog::add($this, $model, $e->getMessage(), false);
            Yii::error("Trigger '{$this->name}' failed: " . $e->getMessage(), __METHOD__);
        }

        return false;
    }

    protected function executeAction($model)
    {
        switch ($this->action_type) {
            case 'notify':
                Yii::$app->notify->send(
                    $model->group_id ?? null,
                    'system',
                    Yii::t('app', $this->action_target ?? 'System Trigger'),
                    [
                        'model' => get_class($model),
                        'id' => $model->id ?? null,
                    ]
                );
                break;

            case 'call':
                if (method_exists($model, $this->action_target))
                    $model->{$this->action_target}();
                break;

            case 'webhook':
                @file_get_contents($this->action_target);
                break;

            case 'command':
                exec($this->action_target . ' > /dev/null 2>&1 &');
                break;
        }
    }
}
