<?php
namespace croacworks\essentials\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * CoreUI Sidebar Menu
 *
 * Uso:
 * echo CoreUiMenu::widget([
 *   'items' => [
 *     ['label' => 'Dashboard', 'icon' => 'tachometer-alt', 'url' => ['/site/index']],
 *     [
 *       'label' => 'Cadastros',
 *       'icon' => 'folder',
 *       'items' => [
 *         ['label' => 'Usuários', 'url' => ['/user/index'], 'iconStyle' => 'far', 'icon' => 'user'],
 *         ['label' => 'Perfis',  'url' => ['/user-profile/index'], 'icon' => 'id-card'],
 *       ],
 *     ],
 *     ['label' => 'Separador', 'header' => true],
 *     ['label' => 'Gii', 'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank'],
 *   ],
 * ]);
 */
class CoreUiMenu extends \yii\widgets\Menu
{
    /** @inheritdoc */
    public $activateParents = true;

    /** @inheritdoc */
    public $options = [
        'class' => 'sidebar-nav',   // <ul class="sidebar-nav">
    ];

    /** @inheritdoc */
    public $itemOptions = [
        'class' => 'nav-item',      // <li class="nav-item"> (links simples) | "nav-group" (com filhos)
    ];

    /** Ícone padrão (Font Awesome) */
    public static $iconDefault = 'circle';

    /** Estilo padrão FA */
    public static $iconStyleDefault = 'fas'; // fas, far, fab…

    /**
     * Template do link simples no CoreUI.
     * Observação: CoreUI usa <i class="nav-icon ..."></i>, badge ao lado.
     */
    public $linkTemplate = '<a class="nav-link {active}" href="{url}" {target}>{icon}<span class="nav-link-text">{label}</span>{badge}</a>';

    /**
     * Template do "título"/header de seção no CoreUI.
     */
    public $headerTemplate = '<li class="nav-title">{label}</li>';

    /**
     * Template do "grupo" (item com filhos) no CoreUI.
     * - Título “toggle” do grupo
     * - Lista de filhos (nav-group-items)
     */
    public $groupToggleTemplate = '<a class="nav-link nav-group-toggle {active}" href="#">{icon}<span class="nav-link-text">{label}</span>{badge}</a>';
    public $groupItemsWrapper   = "<ul class=\"nav-group-items\">\n{items}\n</ul>";

    /**
     * Template de badge (se vier HTML pronto, usamos direto).
     * Aqui mantemos compatibilidade com seu campo `badge` como HTML.
     */
    protected function renderBadge($badge)
    {
        if (!$badge) {
            return '';
        }
        // Se usuário já passou HTML completo (ex: <span class="badge ...">), usamos sem alterar.
        if (strip_tags($badge) !== $badge) {
            return $badge;
        }
        // Caso contrário, encapsulamos num span padrão CoreUI (badge alinhado à direita).
        return '<span class="badge ms-auto">'.$badge.'</span>';
    }

    /** @inheritdoc */
    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];

        foreach ($items as $i => $item) {
            if (!isset($item['label'])) {
                $item['label'] = '';
            }
            $item['active'] = $item['active'] ?? $this->isItemActive($item);
            $options = ArrayHelper::merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));

            // Header (nav-title)
            if (!empty($item['header'])) {
                $lines[] = strtr($this->headerTemplate, [
                    '{label}' => Html::encode($item['label']),
                ]);
                continue;
            }

            $hasChildren = !empty($item['items']);
            if ($hasChildren) {
                // Em grupos, CoreUI usa "nav-group"
                Html::removeCssClass($options, 'nav-item');
                Html::addCssClass($options, 'nav-group');

                // Estado aberto quando ativo
                if (!empty($item['active'])) {
                    Html::addCssClass($options, 'show');
                }
            }

            $menu = $this->renderItem($item);

            if ($hasChildren) {
                $submenu = $this->renderItems($item['items']);
                $menu .= strtr($this->groupItemsWrapper, [
                    '{items}' => $submenu,
                ]);
            }

            $tag = ArrayHelper::remove($options, 'tag', 'li');
            $lines[] = Html::tag($tag, $menu, $options);
        }

        return implode("\n", $lines);
    }

    /** @inheritdoc */
    protected function renderItem($item)
    {
        // Header já foi tratado em renderItems()
        $label = Html::encode($item['label']);

        // Ícone
        if (isset($item['iconClass'])) {
            $iconClass = $item['iconClass']; // classe completa fornecida
        } else {
            $iconStyle = $item['iconStyle'] ?? static::$iconStyleDefault; // fas/far/fab
            $iconName  = $item['icon'] ?? static::$iconDefault;
            $iconClass = implode(' ', ['nav-icon', $iconStyle, ($iconStyle === 'fas' ? 'fa-'.$iconName : $iconName)]);
        }
        $iconHtml = '<i class="'.Html::encode($iconClass).'"></i>';

        // Badge (pode vir como HTML pronto)
        $badgeHtml = $this->renderBadge($item['badge'] ?? '');

        // Link de grupo x link simples
        $isGroup = !empty($item['items']);
        $activeClass = !empty($item['active']) ? 'active' : '';
        $target = isset($item['target']) ? 'target="'.Html::encode($item['target']).'"' : '';

        if ($isGroup) {
            $template = ArrayHelper::getValue($item, 'groupToggleTemplate', $this->groupToggleTemplate);
            return strtr($template, [
                '{label}'  => $label,
                '{icon}'   => $iconHtml,
                '{badge}'  => $badgeHtml,
                '{active}' => $activeClass,
            ]);
        }

        // Link simples
        $template = ArrayHelper::getValue($item, 'linkTemplate', $this->linkTemplate);
        $url = isset($item['url']) ? Url::to($item['url']) : '#';

        return strtr($template, [
            '{label}'  => $label,
            '{icon}'   => $iconHtml,
            '{badge}'  => $badgeHtml,
            '{url}'    => Html::encode($url),
            '{active}' => $activeClass,
            '{target}' => $target,
        ]);
    }

    /** @inheritdoc */
    protected function isItemActive($item)
    {
        // Mantém a lógica padrão do Menu para ativação por rota/params.
        // (Se quiser, pode estender isto para regras customizadas.)
        return parent::isItemActive($item);
    }
}
