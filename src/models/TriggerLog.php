<?php
namespace croacworks\essentials\models;

use Yii;
use yii\db\ActiveRecord;

class TriggerLog extends ActiveRecord
{
    public static function tableName() { return '{{%trigger_logs}}'; }

    public function rules()
    {
        return [
            [['trigger_id', 'model_class'], 'required'],
            [['trigger_id', 'model_id'], 'integer'],
            [['success'], 'boolean'],
            [['executed_at'], 'safe'],
            [['model_class', 'message'], 'string', 'max' => 512],
        ];
    }

    public static function add(Trigger $trigger, $model, string $message, bool $success = true)
    {
        $log = new self([
            'trigger_id' => $trigger->id,
            'model_class' => get_class($model),
            'model_id' => $model->id ?? null,
            'message' => $message,
            'success' => $success,
        ]);
        $log->save(false);
    }
}
