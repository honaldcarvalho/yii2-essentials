<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Language;
use yii\bootstrap5\Breadcrumbs;

$user = Yii::$app->user->identity;
$name_split = explode(' ', $user->profile->fullname ?? $user->username);
$name_user  = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');

$action = Yii::$app->controller->action->id;
$languages = Language::find()->where(['status' => true])->all();

$this->registerJs(<<<JS
onPjaxReady((root) => {
    const header = document.querySelector('header.header');
    document.addEventListener('scroll', () => {
    if (header) {
        header.classList.toggle('shadow-sm', document.documentElement.scrollTop > 0);
    }
    });
});

(function(){
    const badge = document.getElementById('notif-badge');
    const list  = document.getElementById('notif-list');
    const markAll = document.getElementById('notif-mark-all');

    async function fetchList() {
        try {
        const res = await fetch('/notification/list?limit=10', {credentials:'same-origin'});
        if (!res.ok) return;
        const data = await res.json();
        render(data);
        } catch(e) { /* opcional: console.error(e); */ }
    }

    function render(data){
        const count = data.count || 0;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';

        const items = data.items || [];
        list.innerHTML = items.map(item => `
        <a href="\${item.url || '#'}" class="dropdown-item d-flex align-items-start notif-item" data-id="\${item.id}">
            <div class="me-2">
            <span class="avatar bg-secondary text-white">
                <i class="cil-bell"></i>
            </span>
            </div>
            <div class="flex-grow-1">
            <div class="small text-muted">\${new Date(item.created_at.replace(' ','T')).toLocaleString()}</div>
            <div class="fw-semibold \${item.status === 0 ? '' : 'text-muted'}">\${escapeHtml(item.title)}</div>
            \${item.content ? `<div class="small text-muted">\${escapeHtml(item.content)}</div>` : ''}
            </div>
            \${item.status === 0 ? '<span class="ms-2 badge bg-primary">novo</span>' : ''}
        </a>
        `).join('') || '<div class="dropdown-item text-muted">Sem notificações</div>';

        // click marca como lida (e segue o link, se houver)
        const anchors = list.querySelectorAll('.notif-item');
        anchors.forEach(a => {
        a.addEventListener('click', async (ev) => {
            const id = Number(a.getAttribute('data-id'));
            try { await markRead([id]); } catch(e) {}
            // deixa navegar normalmente se tiver URL
        });
        });
    }

    async function markRead(ids, all=false){
        const body = all ? {all:1} : {ids};
        await fetch('/notification/read', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(body)
        }).then(r=>r.json()).then(data=>{
        badge.textContent = data.count || 0;
        badge.style.display = (data.count||0) > 0 ? 'inline-block' : 'none';
        // atualiza lista sem piscar
        fetchList();
        });
    }

    function escapeHtml(s){
        return (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    // marcar todas
    if (markAll) {
        markAll.addEventListener('click', async (e) => {
            e.preventDefault();
            await markRead([], true);
        });
    }

    // polling
    fetchList();
    setInterval(fetchList, 30000);

    // troca de idioma (dropdown CoreUI)
    jQuery(document).on('click', '.lang-menu .dropdown-item[data-lang]', function(){
        var lang = jQuery(this).data('lang');
        var label = document.getElementById('lang-selected');
        var original = label ? label.textContent : '';

        if (label) {
        label.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
        }

        jQuery.post('/user/change-lang', { lang: lang })
        .done(function(resp){
            if (resp && resp.ok) {
            location.reload();
            } else {
            if (label) label.textContent = original || (lang ? String(lang).toUpperCase() : '');
            if (window.toastr) toastr.error((resp && resp.error) || yii.t('app','Failed to change language.'));
            }
        })
        .fail(function(xhr){
            if (label) label.textContent = original || (lang ? String(lang).toUpperCase() : '');
            if (window.toastr) toastr.error(yii.t('app','Network error when switching language') + (xhr && xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '.');
        });
    });

})();


JS);

if (!empty($configuration->file_id) && $configuration->file !== null) {
    $avatar = Yii::getAlias('@web') . $configuration->file->urlThumb;
} else {
    $avatar =  $assetDir . '/images/croacworks-logo-hq.png';
}

// Helpers rápidos para flag (emoji) e rótulo amigável
$activeLang = Yii::$app->user->identity->profile->language->code; // ex.: 'pt-BR' ou 'en-US'

/** Converte 'pt-BR' -> 🇧🇷 (emoji flag) */
$codeToFlag = static function (string $code): string {
    $parts = preg_split('/[-_]/', $code);
    $country = strtoupper($parts[1] ?? $parts[0]); // tenta país; senão usa a própria code
    // Para alguns idiomas sem país (ex.: 'pt') tente mapear um default
    if (strlen($country) !== 2) {
        $country = 'UN';
    } // UN = bandeira neutra
    $A = ord('A');
    $chars = mb_str_split($country);
    $flag = '';
    foreach ($chars as $ch) {
        $flag .= mb_chr(0x1F1E6 + (ord($ch) - $A), 'UTF-8');
    }
    return $flag;
};

/** Rótulo curto (ex.: 'Português (BR)') */
$labelFrom = static function (\croacworks\essentials\models\Language $lang): string {
    $code = $lang->code ?? '';
    $name = $lang->name ?? strtoupper($code);
    $parts = preg_split('/[-_]/', (string)$code);
    $suffix = isset($parts[1]) ? strtoupper($parts[1]) : strtoupper($parts[0] ?? '');
    return trim($name . ($suffix ? " ({$suffix})" : ''));
};

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
            <li class="nav-item"><a class="nav-link" href="/users"><?= Yii::t('app', 'Users') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="/configuration/<?= $configuration->id; ?>"><?= Yii::t('app', 'Configurations') ?></a></li>
        </ul>

        <!-- NOTIFICATION -->
        <ul class="header-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link position-relative" href="#" id="notifDropdown" data-coreui-toggle="dropdown" aria-expanded="false">
                    <svg class="icon icon-lg">
                        <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-bell"></use>
                    </svg>
                    <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg pt-0" id="notif-menu" aria-labelledby="notif-toggle" style="min-width: 360px">
                    <a href="/notification" class="dropdown-header bg-light fw-bold py-2"><?= Yii::t('app', 'Notifications') ?></a>
                    <div id="notif-list" style="max-height: 360px; overflow:auto;"></div>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item text-center" id="notif-mark-all"><?= Yii::t('app', 'Mark all as read') ?></button>
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
            <li class="nav-item dropdown lang-menu">
                <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button"
                    data-coreui-toggle="dropdown" aria-expanded="false" aria-label="Selecionar idioma">
                    <svg class="icon icon-lg me-1">
                        <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-flag-alt"></use>
                    </svg>
                    <span id="lang-selected">
                        <?= htmlspecialchars(strtoupper($activeLang), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 12rem;">
                    <li class="dropdown-header bg-light fw-semibold py-2"><?= Yii::t('app', 'Language'); ?></li>

                    <?php foreach ($languages as $lang):
                        $isActive = strcasecmp($activeLang, $lang->code) === 0;
                        $flag = $codeToFlag($lang->code ?? '');
                        $label = $labelFrom($lang);
                    ?>
                        <li>
                            <button type="button"
                                class="dropdown-item d-flex align-items-center justify-content-between <?= $isActive ? 'active' : '' ?>"
                                data-lang="<?= htmlspecialchars($lang->code, ENT_QUOTES, 'UTF-8'); ?>"
                                <?= $isActive ? 'disabled' : '' ?>>
                                <span class="d-flex align-items-center">
                                    <span class="me-2" style="font-size:1.15rem; line-height:1;"><?= $flag; ?></span>
                                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-primary"><?= Yii::t('app', 'Current'); ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>

                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li class="px-3 pb-2">
                        <small class="text-muted d-block">
                            <?= Yii::t('app', 'Interface language'); ?>
                        </small>
                    </li>
                </ul>
            </li>

            <li class="nav-item py-1">
                <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-md">
                        <?php if ($user->profile && $user->profile->file): ?>
                            <img src="<?= $user->profile->file->url; ?>" class="rounded-circle avatar-img" style="width:32px;height:32px;object-fit:cover;" alt="<?= $name_user; ?>">
                        <?php else: ?>
                            <svg class="rounded-circle avatar-img" width="32" height="32">
                                <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                            </svg>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end pt-0">
                    <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2"><?= Yii::t('app','Account'); ?></div>
                    <a class="dropdown-item" href="/user/profile">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                        </svg> 
                        
                        <?= $name_user ?> 
                        <div class="flex-grow-1">
                            <div class="small text-white-50"><?= htmlspecialchars($user->group->name) ?></div>
                        </div>
                    </a>
                    <a class="dropdown-item" href="/configuration<?= AuthorizationController::isMaster() ? "/{$configuration->id}" : ''; ?>">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-settings"></use>
                        </svg> <?= Yii::t('app','Configurations'); ?> 
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="/site/logout">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                        </svg> Lock Account</a><a class="dropdown-item" href="#">
                        <svg class="icon me-2">
                            <use xlink:href="<?= $assetDir; ?>/vendors/@coreui/icons/svg/free.svg#cil-account-logout"></use>
                        </svg> Logout
                    </a>
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