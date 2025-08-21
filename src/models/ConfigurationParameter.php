<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "configuration_parameters".
 *
 * @property int $id
 * @property int $configuration_id
 * @property int $parameter_id
 *
 * @property Configuration $configuration
 * @property Parameter $parameter
 */
class ConfigurationParameter extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'configuration_parameters';
    }

    public static function groupRelationPath()
    {
        return ['configuration'];
    }
}
