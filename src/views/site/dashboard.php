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
$healthUrl = \yii\helpers\Url::to(['/dashboard/health']);
$this->registerJsVar('DASH', [
    'url' => $healthUrl,
    'intervalMs' => 30000,
]);
?>

<?php
$js = <<<JS
(function($){
  // fallback pra yii.t se não estiver carregado
  if (!window.yii) window.yii = {};
  if (typeof yii.t !== 'function') {
    yii.t = function(category, message){ return message; };
  }

  var healthUrl = (window.DASH && DASH.url) || '/dashboard/health';
  var intervalMs = (window.DASH && DASH.intervalMs) || 30000;

  // helpers
  function setCardState(\$card, percent){
    if (!\$card || !\$card.length) return;
    var p = Number(percent||0), cls = 'bg-success';
    if (p > 70) cls = 'bg-danger';
    else if (p > 50) cls = 'bg-warning';
    \$card.removeClass('bg-success bg-warning bg-danger').addClass(cls);
  }

  function animateBar(\$el, percent){
    if (!\$el || !\$el.length) return;
    var p = Math.max(0, Math.min(100, parseInt(percent||0)));
    // anima a largura via jQuery
    \$el.stop(true).animate({ width: p + '%' }, 500);
    \$el.attr('aria-valuenow', String(p));
  }

  function animateNumber(\$el, value, suffix){
    if (!\$el || !\$el.length) return;
    var current = parseFloat(\$el.text().replace(',', '.')) || 0;
    var target  = parseFloat(value) || 0;
    var hasSuffix = typeof suffix === 'string' && suffix.length > 0;

    \$({v: current}).stop(true).animate({v: target}, {
      duration: 600,
      step: function(now){
        // se for inteiro, mostra inteiro; senão 1 casa
        var isInt = Number.isInteger(target);
        var txt = isInt ? Math.round(now) : (Math.round(now * 10)/10);
        \$el.text(txt + (hasSuffix ? suffix : ''));
      }
    });
  }

  function setTextFade(\$el, newText){
    if (!\$el || !\$el.length) return;
    var txt = (newText == null ? '' : String(newText));
    // fade suave na troca
    \$el.stop(true, true).fadeOut(150, function(){
      \$el.text(txt).fadeIn(150);
    });
  }

  function updateUI(data){
    if (!data || !data.ok) return;

    // OS
    setTextFade($('#os-pretty'), data.os?.pretty_name || '');
    setTextFade($('#os-name'),   data.os?.name || '');

    // DISK
    var disk = data.disk || {};
    setCardState($('#disk-card'), disk.percent_used);
    setTextFade($('#disk-total'), disk.total_bytes_pretty || '');
    setTextFade(
      $('#disk-used'),
      (disk.used_bytes_pretty ? (yii.t('app','Total Space Used:') + disk.used_bytes_pretty) : '')
    );
    animateBar($('#disk-progress'), disk.percent_used);

    // MEMORY
    var mem = data.memory || {};
    setCardState($('#memory-card'), mem.percent_used);
    setTextFade($('#memory-total'), mem.total_pretty || '');
    setTextFade(
      $('#memory-used'),
      (mem.used_pretty ? (yii.t('app','Total Memory Used:') + mem.used_pretty) : '')
    );
    animateBar($('#memory-progress'), mem.percent_used);

    // CPU
    var cpu = data.cpu || {};
    setCardState($('#cpu-card'), cpu.used);
    // anima o número de uso (em %). Exibe só o número; o % já está na barra.
    animateNumber($('#cpu-used'), parseFloat(cpu.used||0));
    setTextFade(
      $('#cpu-free'),
      (cpu.free ? (yii.t('app','Total Cpu Free:') + cpu.free) : '')
    );
    animateBar($('#cpu-progress'), cpu.used);
  }

  function onError(err){
    if (window.toastr && typeof toastr.error === 'function'){
      toastr.error(yii.t('app','Falha ao atualizar o Dashboard.'));
    } else {
      console.error('Falha ao atualizar o Dashboard', err);
    }
  }

  function fetchHealth(){
    $.ajax({
      url: healthUrl,
      method: 'GET',
      dataType: 'json',
      cache: false
    })
    .done(updateUI)
    .fail(onError);
  }

  // primeira carga + interval
  $(function(){
    fetchHealth();
    setInterval(fetchHealth, intervalMs);
  });

})(jQuery);
JS;

$this->registerJs($js);
