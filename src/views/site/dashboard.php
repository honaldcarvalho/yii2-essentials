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
                    <svg class="icon me-2">
                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-media-play"></use>
                    </svg>Preview</a></li>
        </ul>
        <div class="tab-content rounded-bottom">
            <div class="tab-pane p-3 active preview" role="tabpanel" id="preview-1002">
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold">89.9%</div>
                                <div>Widget title</div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div><small class="text-white text-opacity-75">Widget helper text</small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->
                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold">12.124</div>
                                <div>Widget title</div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div><small class="text-white text-opacity-75">Widget helper text</small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->
                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold">$98.111,00</div>
                                <div>Widget title</div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div><small class="text-white text-opacity-75">Widget helper text</small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->
                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="fs-4 fw-semibold">2 TB</div>
                                <div>Widget title</div>
                                <div class="progress progress-white progress-thin my-2">
                                    <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div><small class="text-white text-opacity-75">Widget helper text</small>
                            </div>
                        </div>
                    </div>
                    <!-- /.col-->
                </div>
                <!-- /.row.g-4-->
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 col-12">

        <div class="info-box bg-gray-dark">
            <span class="info-box-icon"><i class="fa-brands fa-<?= $osInfo['type'] ?>"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= Yii::t('app', 'Operation System') ?></span>
                <span class="info-box-number"><?= $osInfo['pretty_name'] ?></span>
                <div class="progress">

                </div>
                <span class="progress-description">
                    <?= $osInfo['name'] ?>
                </span>
            </div>
        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-12">

        <div class="info-box <?= $diskInfo['percent_used'] > 50 ? ($diskInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
            <span class="info-box-icon"><i class="fas fa-hdd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= Yii::t('app', 'Disk Space') ?></span>
                <span class="info-box-number"><?= $diskInfo['total_bytes_pretty'] ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?= $diskInfo['percent_used'] ?>%"></div>
                </div>
                <span class="progress-description">
                    Total Space Used: <?= $diskInfo['used_bytes_pretty'] ?>
                </span>
            </div>
        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-12">

        <div class="info-box <?= $memoryInfo['percent_used'] > 50 ? ($memoryInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
            <span class="info-box-icon"><i class="fas fa-memory"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= Yii::t('app', 'Memory') ?></span>
                <span class="info-box-number"><?= $memoryInfo['total_pretty'] ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?= $memoryInfo['percent_used'] ?>%"></div>
                </div>
                <span class="progress-description">
                    Total Memory Used: <?= $memoryInfo['used_pretty'] ?>
                </span>
            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-12">

        <div class="info-box <?= $cpuInfo['used'] > 50 ? ($cpuInfo['used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
            <span class="info-box-icon"><i class="fa-solid fa-microchip"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= Yii::t('app', 'CPU Usage') ?></span>
                <span class="info-box-number"><?= $cpuInfo['used'] ?>% </span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?= $cpuInfo['used'] ?>%"></div>
                </div>
                <span class="progress-description">
                    Total Cpu Free: <?= $cpuInfo['free'] ?>%
                </span>
            </div>

        </div>

    </div>

</div>