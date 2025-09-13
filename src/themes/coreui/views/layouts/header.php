<?php

use yii\bootstrap5\Breadcrumbs;

$name_split = explode(' ', Yii::$app->user->identity->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

$this->registerJs(<<<JS
onPjaxReady((root) => {
  // ---- Badge + lista de notificações ----
  const badge = document.getElementById('notif-badge');
  const list  = document.getElementById('notif-list');
  const updatedAt = document.getElementById('notif-updated-at');

  function setBadge(n) {
    if (!badge) return;
    badge.textContent = n;
    badge.style.display = n > 0 ? 'inline-block' : 'none';
  }

  function asDateLabel(dt) {
    if (!dt) return '';
    // aceita 'YYYY-MM-DD HH:MM:SS' ou ISO
    const s = dt.toString().includes(' ') ? dt.replace(' ', 'T') : dt;
    const d = new Date(s);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString();
  }

  function render(items) {
    if (!list) return;
    list.innerHTML = '';
    if (!items || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'p-3 text-center text-muted';
      empty.textContent = 'Sem notificações';
      list.appendChild(empty);
      return;
    }

    items.forEach(item => {
      const a = document.createElement('a');
      a.href = item.url || item.link || item.href || '#';
      a.className = 'list-group-item list-group-item-action';

      const title = item.title || item.subject || item.name || '(sem título)';
      const body  = item.body || item.message || item.content || '';
      const when  = asDateLabel(item.created_at || item.createdAt);

      a.innerHTML = `
        <div class="d-flex w-100 justify-content-between">
          <strong class="text-truncate" style="max-width:75%">\${title}</strong>
          <small class="text-nowrap">\${when}</small>
        </div>
        <div class="small text-muted text-truncate">\${body}</div>
      `;

      a.addEventListener('click', () => {
        if (item.id) {
          // marca como lida usando status (feito no servidor)
          fetch('/notification/read?id=' + item.id, { credentials: 'include' })
            .then(r => r.json())
            .then(j => {
              if (j && typeof j.unread !== 'undefined') setBadge(j.unread);
            })
            .catch(() => {});
        }
      });

      list.appendChild(a);
    });
  }

    let lastUnread = 0;

    async function loadNotifications() {
        try {
            const r = await fetch('/notification/list?limit=20', { credentials: 'include' });
            const j = await r.json();
            if (!j || !j.success) return;

            const unread = j.unread || 0;

            // Se há novas notificações em relação à última contagem
            if (unread > lastUnread) {
                const diff = unread - lastUnread;

                // --- Toastr ---
                if (window.toastr) {
                    toastr.options = {
                    closeButton: true,
                    newestOnTop: true,
                    progressBar: true,
                    positionClass: 'toast-bottom-right',
                    timeOut: 4000
                    };
                    toastr.info(
                    diff === 1
                        ? 'Você recebeu 1 nova notificação'
                        : `Você recebeu \${diff} novas notificações`,
                    'Notificações'
                    );
                }
            }

            lastUnread = unread;

            setBadge(unread);
            render(j.items || []);
            if (updatedAt) updatedAt.textContent = new Date().toLocaleTimeString();
        } catch (e) {
            // silencioso
        }
    }

  // Atualiza ao abrir o dropdown
  const dd = document.getElementById('notifDropdown');
  if (dd) {
    dd.addEventListener('click', () => {
      // pequeno debounce visual
      setTimeout(loadNotifications, 50);
    });
  }

  // Carrega agora e a cada 20s
  loadNotifications();
  const notifInterval = setInterval(loadNotifications, 20000);

  // Se a página usar PJAX e destruir elementos, limpe o interval quando precisar
  document.addEventListener('pjax:beforeSend', () => clearInterval(notifInterval), { once: true });
});

JS);

if (!empty($config->file_id) && $config->file !== null) {
    $avatar = Yii::getAlias('@web') . $config->file->urlThumb;
} else {
    $avatar =  $assetDir . '/images/croacworks-logo-hq.png';
}

?>

<header class="header header-sticky p-0 mb-4">
    <div class="container-fluid border-bottom px-4">
        <button class="header-toggler" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()" style="margin-inline-start: -14px;">
            <svg class="icon icon-lg">
                <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-menu"></use>
            </svg>
        </button>
        <ul class="header-nav d-none d-lg-flex">
            <li class="nav-item"><a class="nav-link" href="/site/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/users">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/configuration/<?= $config->id; ?>">Settings</a></li>
        </ul>
        <ul class="header-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link position-relative" href="#" id="notifDropdown" data-coreui-toggle="dropdown" aria-expanded="false">
                    <svg class="icon icon-lg">
                        <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-bell"></use>
                    </svg>
                    <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                </a>

                <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notifDropdown" style="min-width:360px">
                    <div class="p-2 border-bottom fw-bold d-flex align-items-center justify-content-between">
                        <span>Notificações</span>
                        <small class="text-muted" id="notif-updated-at"></small>
                    </div>
                    <div id="notif-list" class="list-group list-group-flush" style="max-height:360px; overflow:auto"></div>
                    <div class="p-2 text-center">
                        <a class="small text-decoration-none" href="/notification/list">Ver todas</a>
                    </div>
                </div>
            </li>
        </ul>
        <ul class="header-nav">
            <li class="nav-item py-1">
                <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
            </li>
            <li class="nav-item dropdown">
                <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button" aria-expanded="false" data-coreui-toggle="dropdown">
                    <svg class="icon icon-lg theme-icon-active">
                        <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-contrast"></use>
                    </svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 8rem;">
                    <li>
                        <button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="light">
                            <svg class="icon icon-lg me-3">
                                <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-sun"></use>
                            </svg>Light
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="dark">
                            <svg class="icon icon-lg me-3">
                                <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-moon"></use>
                            </svg>Dark
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center active" type="button" data-coreui-theme-value="auto">
                            <svg class="icon icon-lg me-3">
                                <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-contrast"></use>
                            </svg>Auto
                        </button>
                    </li>
                </ul>
            </li>
            <li class="nav-item py-1">
                <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
            </li>
            <li class="nav-item dropdown"><a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-md"><img class="avatar-img" src="<?= $avatar; ?>" alt="<?= $name_user; ?>"></div>
                </a>
                <div class="dropdown-menu dropdown-menu-end pt-0">
                    <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">Account</div><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-bell"></use>
                        </svg> Updates<span class="badge badge-sm bg-info ms-2">42</span></a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-envelope-open"></use>
                        </svg> Messages<span class="badge badge-sm bg-success ms-2">42</span></a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-task"></use>
                        </svg> Tasks<span class="badge badge-sm bg-danger ms-2">42</span></a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-comment-square"></use>
                        </svg> Comments<span class="badge badge-sm bg-warning ms-2">42</span></a>
                    <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold my-2">
                        <div class="fw-semibold">Settings</div>
                    </div><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                        </svg> Profile</a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-settings"></use>
                        </svg> Settings</a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-credit-card"></use>
                        </svg> Payments<span class="badge badge-sm bg-secondary ms-2">42</span></a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-file"></use>
                        </svg> Projects<span class="badge badge-sm bg-primary ms-2">42</span></a>
                    <div class="dropdown-divider"></div><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                        </svg> Lock Account</a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-account-logout"></use>
                        </svg> Logout</a>
                </div>
            </li>
        </ul>
    </div>
    <div class="container-fluid px-4">
        <?php
        echo Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            'options' => [
                'class' => 'breadcrumb float-sm-right'
            ]
        ]); ?>
    </div>
</header>