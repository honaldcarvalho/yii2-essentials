<?php
namespace croacworks\essentials\themes\coreui\assets;

use yii\web\AssetBundle;

class PluginAsset extends AssetBundle
{
    public $sourcePath = '@vendor/croacworks/yii2-essentials/src/themes/coreui/web/plugins';
    public $depends = [
        'croacworks\essentials\themes\coreui\assets\BaseAsset'
    ];

    public static $pluginMap = [
        'color-mode' => [
            'color-modes/color-modes.js',
            'color-modes/colors.js',
        ],
        'axios' => [
            'js' => 'axios/axios.min.js',
        ],
        'ckeditor' => [
            'js' => 'ckeditor/ckeditor5.umd.js',
            'css' => 'ckeditor/ckeditor5.css',
        ],
        'ace' => [
            'js' => 'ace/ace.js',
            'css' => 'ace/css/ace.css',
        ],
        'tinymce' => [
            'js' => 'tinymce/tinymce.min.js',
        ],
        'inputmask' => [
            'css' => 'inputmask/colormask.css',
            'js' => 'inputmask/inputmask.min.js',
            'js' => 'inputmask/jquery.inputmask.min.js',
        ],
        'jquery' => [
            'js' => 'jquery/jquery-3.7.1.min.js',
        ],
        'jquery-ui' => [
            'css' => 'jquery-ui/jquery-ui.min.css',
            'js' => 'jquery-ui/jquery-ui.min.js',
        ],
        'icheck-bootstrap' => [
            'css' => ['icheck-bootstrap/icheck-bootstrap.css']
        ],
        'jquery-cropper' => [
            'css' => 'jquery-cropper/cropper.min.css',
            'js' => 'jquery-cropper/cropper.min.js',
        ],
        'cropperjs' => [
            'css' => 'cropperjs/css/cropper.css',
            'js' => 'cropperjs/js/cropper.js',
        ],
        'datatables' => [
            'css' => 'datatables/datatables.min.css',
            'js' => 'datatables/jquery.dataTables.min.js',
        ],
        'cropper' => [
            'css' => 'cropper/cropper.min.css',
            'js' => 'cropper/cropper.min.js',
        ],
        'chart.js' => [
            'css' => 'chart.js/Chart.min.css',
            'js' => 'chart.js/Chart.min.js',
            'js' => 'chart.js/Chart.bundle.min.js',
        ],
        'toastr' => [
            'js' => 'toastr/toastr.min.js',
            'css' => 'toastr/toastr.min.css'
        ],
        'multiselect' => [
            'js'=>'multiselect/multiselect.min.js'
        ],
        'select2' => [
            'css' => 'select2/css/select2.min.css',
            'js' => 'select2/js/select2.min.js',
        ],
        'fancybox' => [
            'css' => 'fancybox5/fancybox.css',
            'js' => 'fancybox5/fancybox.umd.js',
        ],
        'fancybox' => [
            'css' => 'fancybox5/fancybox.css',
            'js' => 'fancybox5/fancybox.umd.js',
        ],
        'fontawesome' => [
            'css' => 'fontawesome-free/css/brands.min.css',
            'css' => 'fontawesome-free/css/regular.min.css',
            'css' => 'fontawesome-free/css/solid.min.css',
        ],
        'sweetalert2' => [
            'css' => 'sweetalert2-theme-bootstrap-4/bootstrap-4.min.css',
            'js' => 'sweetalert2/sweetalert2.min.js'
        ],
    ];

    /**
     * add a plugin dynamically
     * @param $pluginName
     * @return $this
     */
    public function add($pluginName)
    {
        $pluginName = (array) $pluginName;

        foreach ($pluginName as $name) {
            $plugin = $this->getPluginConfig($name);
            if (isset($plugin['css'])) {
                foreach ((array) $plugin['css'] as $v) {
                    $this->css[] = $v;
                }
            }
            if (isset($plugin['js'])) {
                foreach ((array) $plugin['js'] as $v) {
                    $this->js[] = $v;
                }
            }
        }

        return $this;
    }

    /**
     * @param $name plugin name
     * @return array|null
     */
    private function getPluginConfig($name)
    {
        return \Yii::$app->params['weebz/yii2-basic']['pluginMap'][$name] ?? self::$pluginMap[$name] ?? null;
    }
}