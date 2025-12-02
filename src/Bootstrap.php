<?php

namespace croacworks\essentials;

use Yii;
use yii\base\BootstrapInterface;

/**
 * Bootstrap for croacworks/yii2-essentials.
 * - Registers aliases
 * - (Optionally) wires DI definitions and default components if missing
 * - Leaves i18n category to 'app' (expects DbMessageSource on host app)
 */
class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        // Alias for this package
        Yii::setAlias('@croacworks/essentials', __DIR__);
        Yii::setAlias('@essentials', __DIR__);

        // Example: register DI bindings if not already set
        $container = Yii::$container;

        // Bind StorageInterface -> LocalStorage (can be overridden in app config)
        if (!$container->has('croacworks\\essentials\\components\\files\\StorageInterface')) {
            $container->set(
                'croacworks\\essentials\\components\\files\\StorageInterface',
                'croacworks\\essentials\\components\\files\\LocalStorage'
            );
        }

        // Ensure default components exist (non-intrusive: only set if missing)
        $this->ensureDefaultComponent($app, 'authorization', [
            'class' => \croacworks\essentials\components\authorization\AuthorizationService::class,
        ]);

        $this->ensureDefaultComponent($app, 'authService', [
            'class' => \croacworks\essentials\components\auth\AuthService::class,
        ]);

        $this->ensureDefaultComponent($app, 'settings', [
            'class' => \croacworks\essentials\components\config\Settings::class,
            'cacheTtl' => 300,
        ]);

        $this->ensureDefaultComponent($app, 'storage', [
            'class' => \croacworks\essentials\components\files\LocalStorage::class,
            'basePath' => '@app/runtime/uploads',
            'baseUrl'  => '/uploads',
        ]);

        $this->ensureDefaultComponent($app, 'alertManager', [
            'class' => \croacworks\essentials\components\ui\AlertManager::class,
        ]);

        $this->ensureDefaultComponent($app, 'metaHelper', [
            'class' => \croacworks\essentials\components\meta\MetaHelper::class,
        ]);

        // Note: i18n uses category 'app' — host app should configure DbMessageSource for 'app'
        // Example (host app): 
        // 'i18n' => ['translations' => ['app*' => ['class' => \yii\i18n\DbMessageSource::class]]]
    }

    private function ensureDefaultComponent($app, string $id, array $definition): void
    {
        if (!$app->has($id)) {
            $app->set($id, $definition);
        }
    }
}
