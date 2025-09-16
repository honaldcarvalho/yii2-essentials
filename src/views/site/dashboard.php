<?php

/** @var yii\web\View $this */

use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\SystemInfo;

$sysInfo    = new SystemInfo();
$diskInfo   = $sysInfo->diskInfo('/');
$memoryInfo = $sysInfo->memoryInfo();
$cpuInfo    = $sysInfo->cpuInfo();
$osInfo     = $sysInfo->getOSInformation();

$params = Configuration::get();

$this->title = 'Dashboard';

?>

<div class="row">
    <ul class="nav nav-underline-border" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-coreui-toggle="tab" href="#preview-1002" role="tab">
                <i class="icon me-2" fa-brands fa-<?= $osInfo['type'] ?>"></i>
                <?= Yii::t('app', 'Server Heath'); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content rounded-bottom">
        <div class="tab-pane p-3 active preview" role="tabpanel" id="preview-1002">
            <div class="row g-4">

                <!-- OS -->
                <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                    <div class="card text-white bg-primary" id="os-card">
                        <div class="card-body">
                            <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Operation System') ?></div>
                            <div id="os-pretty"><?= $osInfo['pretty_name'] ?></div>
                            <div class="progress progress-white progress-thin my-2">
                                <div class="progress-bar" id="os-progress" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-white text-opacity-75" id="os-name">
                                <?= $osInfo['name'] ?>
                            </small>
                        </div>
                    </div>
                </div>
                <!-- /.col-->

                <!-- DISK -->
                <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                    <div class="card text-white <?= $diskInfo['percent_used'] > 50 ? ($diskInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>" id="disk-card">
                        <div class="card-body">
                            <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Disk Space') ?></div>
                            <div id="disk-total"><?= $diskInfo['total_bytes_pretty'] ?></div>
                            <div class="progress progress-white progress-thin my-2">
                                <div class="progress-bar" id="disk-progress" role="progressbar" style="width: <?= (int)$diskInfo['percent_used'] ?>%" aria-valuenow="<?= (int)$diskInfo['percent_used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-white text-opacity-75" id="disk-used">
                                <?= Yii::t('app','Total Space Used:').$diskInfo['used_bytes_pretty'] ?>
                            </small>
                        </div>
                    </div>
                </div>
                <!-- /.col-->

                <!-- MEMORY -->
                <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                    <div class="card text-white <?= $memoryInfo['percent_used'] > 50 ? ($memoryInfo['percent_used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>" id="memory-card">
                        <div class="card-body">
                            <div class="fs-4 fw-semibold"><?= Yii::t('app', 'Memory') ?></div>
                            <div id="memory-total"><?= $memoryInfo['total_pretty'] ?></div>
                            <div class="progress progress-white progress-thin my-2">
                                <div class="progress-bar" id="memory-progress" role="progressbar" style="width: <?= (int)$memoryInfo['percent_used'] ?>%" aria-valuenow="<?= (int)$memoryInfo['percent_used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-white text-opacity-75" id="memory-used">
                                <?= Yii::t('app','Total Memory Used:').$memoryInfo['used_pretty'] ?>
                            </small>
                        </div>
                    </div>
                </div>
                <!-- /.col-->

                <!-- CPU -->
                <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                    <div class="card text-white <?= $cpuInfo['used'] > 50 ? ($cpuInfo['used'] > 70 ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>" id="cpu-card">
                        <div class="card-body">
                            <div class="fs-4 fw-semibold"><?= Yii::t('app', 'CPU Usage') ?></div>
                            <div id="cpu-used"><?= (int)$cpuInfo['used'] ?></div>
                            <div class="progress progress-white progress-thin my-2">
                                <div class="progress-bar" id="cpu-progress" role="progressbar" style="width: <?= (int)$cpuInfo['used'] ?>%" aria-valuenow="<?= (int)$cpuInfo['used'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-white text-opacity-75" id="cpu-free">
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

<?php
// URL do endpoint — ajuste se seu controller/rota for diferente
$healthUrl = \yii\helpers\Url::to(['/dashboard/health']);
$js = <<<JS
(function(){
  var healthUrl = '{$healthUrl}';
  var intervalMs = 30000;

  function setCardState(cardEl, percent){
    var cls = 'bg-success';
    if (percent > 70) cls = 'bg-danger';
    else if (percent > 50) cls = 'bg-warning';

    cardEl.classList.remove('bg-success','bg-warning','bg-danger');
    cardEl.classList.add(cls);
  }

  function setProgress(el, percent){
    var p = Math.max(0, Math.min(100, parseInt(percent || 0)));
    el.style.width = p + '%';
    el.setAttribute('aria-valuenow', String(p));
  }

  function text(el, value){
    if (el) el.textContent = value;
  }

  function updateUI(data){
    if (!data || !data.ok) return;

    // OS (mantém barra decorativa em 0, só atualiza textos)
    text(document.getElementById('os-pretty'), data.os.pretty_name || '');
    text(document.getElementById('os-name'), data.os.name || '');

    // DISK
    var disk = data.disk || {};
    setCardState(document.getElementById('disk-card'), disk.percent_used || 0);
    text(document.getElementById('disk-total'), disk.total_bytes_pretty || '');
    text(document.getElementById('disk-used'), (disk.used_bytes_pretty ? ('{$this->renderDynamic("return Yii::t(\\'app\\', \\'Total Space Used:\\');")}'+disk.used_bytes_pretty) : ''));
    setProgress(document.getElementById('disk-progress'), disk.percent_used || 0);

    // MEMORY
    var mem = data.memory || {};
    setCardState(document.getElementById('memory-card'), mem.percent_used || 0);
    text(document.getElementById('memory-total'), mem.total_pretty || '');
    text(document.getElementById('memory-used'), (mem.used_pretty ? ('{$this->renderDynamic("return Yii::t(\\'app\\', \\'Total Memory Used:\\');")}'+mem.used_pretty) : ''));
    setProgress(document.getElementById('memory-progress'), mem.percent_used || 0);

    // CPU
    var cpu = data.cpu || {};
    setCardState(document.getElementById('cpu-card'), cpu.used || 0);
    text(document.getElementById('cpu-used'), parseInt(cpu.used || 0));
    text(document.getElementById('cpu-free'), (cpu.free ? ('{$this->renderDynamic("return Yii::t(\\'app\\', \\'Total Cpu Free:\\');")}'+cpu.free) : ''));
    setProgress(document.getElementById('cpu-progress'), cpu.used || 0);
  }

  function onError(err){
    // Se tiver Toastr, use; senão, console:
    if (window.toastr && typeof window.toastr.error === 'function'){
      window.toastr.error('Falha ao atualizar o Dashboard.');
    } else {
      console.error('Falha ao atualizar o Dashboard', err);
    }
  }

  function fetchHealth(){
    fetch(healthUrl, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r){ return r.json(); })
      .then(updateUI)
      .catch(onError);
  }

  // primeira atualização imediata
  fetchHealth();

  // atualiza a cada 30s
  setInterval(fetchHealth, intervalMs);
})();
JS;

$this->registerJs($js);
