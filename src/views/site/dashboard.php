<?php

/** @var yii\web\View $this */

use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\SystemInfo;

$sysInfo = new SystemInfo();
$diskInfo = $sysInfo->diskInfo('/');
$memoryInfo = $sysInfo->memoryInfo();
$cpuInfo = $sysInfo->cpuInfo();
$osInfo = $sysInfo->getOSInformation();

$params = Configuration::get();

if (!empty($params->file_id) && $params->file !== null) {
    $url = Yii::getAlias('@web') . $params->file->urlThumb;
    $logo_image = "<img alt='{$params->title}' width='150px' class='brand-image img-circle elevation-3' src='{$url}' style='opacity: .8' />";
} else {
    $logo_image = "<img src='/preview_square.jpg' width='150px' alt='{$params->title}' class='brand-image img-circle elevation-3' style='opacity: .8'>";
}
$this->title = '';

?>

<div class="row">
    <div class="example">
        <ul class="nav nav-underline-border" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-coreui-toggle="tab" href="#preview-1002" role="tab">
                    <i class="icon me-2" fa-brands fa-<?= $osInfo['type'] ?>"></i>
                    <?= Yii::t('app', 'Server Heath'); ?>
                </a>
            </li>
        </ul>
        <div class="tab-content rounded-bottom">
            <div class="tab-pane p-3 active preview" role="tabpanel" id="preview-1002">
                <div class="row g-4">

                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Operation System') ?></div>
                                <div><?= $osInfo['pretty_name'] ?></div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="width:0%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-white text-opacity-75">
                                    <?= $osInfo['name'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->

                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white <?= $diskInfo['percent_used'] > 50 ? ($diskInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Disk Space') ?></div>
                                <div><?= $diskInfo['total_bytes_pretty'] ?></div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="<?= $diskInfo['percent_used'] ?>%" aria-valuenow="<?= $diskInfo['percent_used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-white text-opacity-75">
                                    <?= Yii::t('app','Total Space Used:').$diskInfo['used_bytes_pretty'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->

                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white <?= $memoryInfo['percent_used'] > 50 ? ($memoryInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Memory') ?></div>
                                <div><?= $memoryInfo['total_pretty'] ?></div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="<?= $memoryInfo['percent_used'] ?>%" aria-valuenow="<?= $memoryInfo['percent_used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-white text-opacity-75">
                                    <?= Yii::t('app','Total Memory Used:').$memoryInfo['used_bytes_pretty'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->

                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white <?= $cpuInfo['used'] > 50 ? ($cpuInfo['used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold"><?= Yii::t('app', 'CPU Usage') ?></div>
                                <div><?= $cpuInfo['used'] ?></div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="<?= $cpuInfo['used'] ?>%" aria-valuenow="<?= $cpuInfo['used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-white text-opacity-75">
                                    <?= Yii::t('app','Total Cpu Free:').$cpuInfo['free'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->

                </div>
                <!-- /.row.g-4-->
            </div>
        </div>
    </div>

</div>