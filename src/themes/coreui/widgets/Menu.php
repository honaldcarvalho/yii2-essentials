<?php
namespace croacworks\essentials\themes\coreui\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Menu para CoreUI (v4), preservando a lógica original.
 *
 * Mesmas opções/itens do widget original (AdminLTE), mas com classes/markup
 * do CoreUI:
 * - UL raiz: .sidebar-nav
 * - Item simples: <li class="nav-item"><a class="nav-link">...</a></li>
 * - Grupo: <li class="nav-group"><a class="nav-link nav-group-toggle">...</a><ul class="nav-group-items">...</ul></li>
 * - Título: <li class="nav-title">...</li>
 * - Ícones: usa <i class="nav-icon ..."></i> (FontAwesome como no original)
 */
class Menu extends \yii\widgets\Menu
{
    /** Link normal (item simples) */
    public $linkTemplate = '<a class="nav-link {active}" href="{url}" {target}>{icon}<span class="nav-link-text">{label}</span>{badge}</a>';

    /** Rótulo interno do link */
    public $labelTemplate = '{label}';

    /** Submenu (CoreUI) */
    public $treeTemplate = "\n<ul class='nav-group-items'>\n{items}\n</ul>\n";

    /** Defaults de ícone */
    public static $iconDefault = 'circle';
    public static $iconStyleDefault = 'fas';

    /** Opções de item (fallback para itens simples) */
    public $itemOptions = ['class' => 'nav-item'];

    /** Ativar pais quando filho ativo */
    public $activateParents = true;

    /** Opções do UL raiz (CoreUI) */
    public $options = [
        'class' => 'sidebar-nav',
        'role'  => 'menu',
    ];

    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];

        foreach ($items as $i => $item) {
            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));

            $hasChildren = isset($item['items']) && !empty($item['items']);

            // CoreUI: grupo quando tem filhos; caso contrário, item simples
            if ($hasChildren) {
                Html::removeCssClass($options, 'nav-item');
                Html::addCssClass($options, 'nav-group');
            } else {
                // Garante nav-item para item simples
                Html::removeCssClass($options, 'nav-group');
                Html::addCssClass($options, 'nav-item');
            }

            // Header/título
            if (isset($item['header']) && $item['header']) {
                // CoreUI: nav-title
                Html::removeCssClass($options, 'nav-item');
                Html::removeCssClass($options, 'nav-group');
                Html::addCssClass($options, 'nav-title');
            }

            $tag = ArrayHelper::remove($options, 'tag', 'li');

            $class = [];
            if (!empty($item['active'])) {
                $class[] = $this->activeCssClass; // 'active'
            }
            if ($i === 0 && $this->firstItemCssClass !== null) {
                $class[] = $this->firstItemCssClass;
            }
            if ($i === $n - 1 && $this->lastItemCssClass !== null) {
                $class[] = $this->lastItemCssClass;
            }
            Html::addCssClass($options, $class);

            $menu = $this->renderItem($item);

            if ($hasChildren) {
                $treeTemplate = ArrayHelper::getValue($item, 'treeTemplate', $this->treeTemplate);
                $menu .= strtr($treeTemplate, [
                    '{items}' => $this->renderItems($item['items']),
                ]);

                // CoreUI: grupo aberto quando ativo
                if (!empty($item['active'])) {
                    // .show em nav-group (li)
                    Html::addCssClass($options, 'show');
                }
            }

            $lines[] = Html::tag($tag, $menu, $options);
        }

        return implode("\n", $lines);
    }

    protected function renderItem($item)
    {
        // Título (header)
        if (isset($item['header']) && $item['header']) {
            // Apenas o texto do título (CoreUI usa conteúdo direto dentro do li.nav-title)
            return Html::encode($item['label']);
        }

        // Ícone (mantém a lógica original)
        if (isset($item['iconClass'])) {
            $iconClass = $item['iconClass'];
        } else {
            $iconStyle = $item['iconStyle'] ?? static::$iconStyleDefault;
            $icon = $item['icon'] ?? static::$iconDefault;

            // Em AdminLTE era 'nav-icon' + FA; aqui mantemos 'nav-icon' (ok no CoreUI)
            $iconClassArr = ['nav-icon', $iconStyle, ($iconStyle === 'fas' ? 'fa-' . $icon : $icon)];
            if (isset($item['iconClassAdded'])) {
                $iconClassArr[] = $item['iconClassAdded'];
            }
            $iconClass = implode(' ', $iconClassArr);
        }
        $iconHtml = '<i class="' . Html::encode($iconClass) . '"></i>';

        // Badge (html livre como antes)
        $badgeHtml = $item['badge'] ?? '';

        $hasChildren = isset($item['items']) && !empty($item['items']);

        // Em grupos, o link precisa da classe 'nav-group-toggle' (CoreUI)
        // Mantemos a mesma lógica do original (define template aqui), apenas ajustando as classes.
        if ($hasChildren) {
            $template = ArrayHelper::getValue(
                $item,
                'template',
                '<a class="nav-link nav-group-toggle {active}" href="#" {target}>'
                . '{icon}<span class="nav-link-text">{label}</span>{badge}</a>'
            );
        } else {
            $template = ArrayHelper::getValue(
                $item,
                'template',
                (isset($item['linkTemplate'])) ? $item['linkTemplate'] : $this->linkTemplate
            );
        }

        // label interno
        $labelInner = strtr($this->labelTemplate, [
            '{label}'   => Html::encode($item['label']),
            '{badge}'   => $badgeHtml,
            '{treeFlag}' => '', // CoreUI não usa seta aqui; a classe nav-group-toggle cuida disso
        ]);

        return strtr($template, [
            '{label}'  => $labelInner,
            '{url}'    => isset($item['url']) ? Url::to($item['url']) : '#',
            '{icon}'   => $iconHtml,
            '{active}' => !empty($item['active']) ? $this->activeCssClass : '',
            '{target}' => isset($item['target']) ? 'target="' . Html::encode($item['target']) . '"' : '',
            '{badge}'  => $badgeHtml,
        ]);
    }
}
