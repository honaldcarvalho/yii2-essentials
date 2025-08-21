<?php

namespace croacworks\essentials\helpers;

use Yii;

class ModelHelper
{

    public static $paths = [
        '@app/models',
        '@vendor/croacworks/yii2-essentials/src/models'
    ];

    public static function getAllModelClasses(): array
    {
        $models = [];

        foreach (static::$paths as $pathAlias) {
            $path = Yii::getAlias($pathAlias);
            if (!is_dir($path)) continue;

            $files = scandir($path);

            foreach ($files as $file) {
                // Ignora arquivos não .php ou abstratos
                if (!preg_match('/^[A-Z]\w+\.php$/', $file)) continue;

                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (!is_file($fullPath)) continue;

                $content = file_get_contents($fullPath);

                // Extrai namespace
                if (!preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) continue;
                // Extrai nome da classe
                if (!preg_match('/class\s+([A-Z]\w*)/', $content, $classMatch)) continue;

                $namespace = trim($nsMatch[1]);
                $className = trim($classMatch[1]);
                $fqcn = $namespace . '\\' . $className;

                // Verifica se é um ActiveRecord válido
                if (!class_exists($fqcn)) continue;
                if (!is_subclass_of($fqcn, \yii\db\ActiveRecord::class)) continue;

                $models[$fqcn] = $className;
            }
        }

        return $models;
    }

    public static function getFields($class)
    {
        try {
            if (!class_exists($class)) {
                return [];
            }

            $instance = new $class();
            $attributeNames = array_keys($instance->attributes);

            foreach ($attributeNames as $attr) {
                $fields[] = [
                    'id' => $attr,
                    'text' => $instance->getAttributeLabel($attr),
                ];
            }
            return $fields;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
