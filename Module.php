<?php
namespace croacworks\essentials;

use Yii;
use yii\base\Module as BaseModule;

/**
 * Essentials module.
 * Optional module if you want to expose routes under /essentials/*
 * (e.g., file download endpoint, alert feed, health checks).
 */
class Module extends BaseModule
{
    /** @var string Controller namespace for web context */
    public $controllerNamespace = 'croacworks\\essentials\\controllers';

    /** @var string Default route inside the module */
    public $defaultRoute = 'health/index';

    /** @var bool Enable simple health endpoint (optional controllers may be provided later) */
    public $enableHealth = true;

    public function init()
    {
        parent::init();

        // Example of module-level params (can be overridden in app config)
        if (!isset($this->params['uploadsBasePath'])) {
            $this->params['uploadsBasePath'] = '@app/runtime/uploads';
        }
        if (!isset($this->params['uploadsBaseUrl'])) {
            $this->params['uploadsBaseUrl'] = '/uploads';
        }
    }

    /**
     * Basic translation helper (keeps category 'app').
     */
    public static function t(string $message, array $params = []): string
    {
        return Yii::t('app', $message, $params);
    }
}
