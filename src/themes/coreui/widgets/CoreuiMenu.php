<?php
namespace croacworks\essentials\themes\coreui\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * CoreUI Menu widget
 *
 * Aceita a mesma estrutura de items usada no seu AdminLTE,
 * mas renderiza com a marcação e classes do CoreUI.
 *
 * Regras:
 * - Se 'header' => true, vira <li class="nav-title">...</li>
 * - Se 'divider' => true, vira <li class="nav-divider"></li>
 * - Se houver 'items', vira "nav-group" com "nav-group-toggle" e <ul class="nav-group-items">
 * - Ícones:
 *     * Se o 'icon' começar com 'cil-' => usa SVG CoreUI (vendors/@coreui/icons).
 *     * Caso contrário:
 *         - Se 'iconClass' vier pronto, usa <i>.
 *         - Senão tenta montar com FontAwesome (fallback).
 */
class CoreuiMenu extends \yii\widgets\Menu
{
    /** Caminho base para ícones CoreUI (ajuste se necessário) */
    public $coreuiIconBaseHref = '/vendors/@coreui/icons/svg/free.svg';

    /** Adiciona "compact" no <ul> dos filhos */
    public $compactChildren = true;

    /** Marca pai como aberto quando filho está ativo: adiciona class "show" no <ul> dos filhos */
    public $openOnActive = true;

    /** Classe aplicada ao link ativo */
    public $activeLinkClass = 'active';

    /** Opções do UL raiz */
    public $options = [
        'class' => 'sidebar-nav',
        'data-coreui' => 'navigation',
        'data-simplebar' => '',
    ];

    /** Cada LI */
    public $itemOptions = ['class' => 'nav-item'];

    /** Ativar pais quando filho estiver ativo */
    public $activateParents = true;

    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];

        foreach ($items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }

            // Header
            if (!empty($item['header'])) {
                $lines[] = Html::tag('li', Html::encode($item['label']), ['class' => 'nav-title']);
                continue;
            }

            // Divider
            if (!empty($item['divider'])) {
                $lines[] = Html::tag('li', '', ['class' => 'nav-divider']);
                continue;
            }

            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
            $hasChildren = !empty($item['items']);

            // Tipo: grupo ou item simples
            if ($hasChildren) {
                Html::removeCssClass($options, 'nav-item');
                Html::addCssClass($options, 'nav-group');

                // Monta o <a> do grupo
                $link = Html::a(
                    $this->renderIcon($item) . Html::tag('span', $item['label']),
                    '#',
                    ['class' => 'nav-link nav-group-toggle']
                );

                // Renderiza filhos
                $childrenUlClasses = ['nav-group-items'];
                if ($this->compactChildren) {
                    $childrenUlClasses[] = 'compact';
                }

                // Se algum filho está ativo, abrimos o grupo
                $childrenHtml = $this->renderItems($item['items']);
                $groupActive = $this->isItemActive($item);
                if ($this->openOnActive && $groupActive) {
                    $childrenUlClasses[] = 'show';
                }

                $ul = Html::tag('ul', $childrenHtml, ['class' => implode(' ', $childrenUlClasses)]);
                $content = $link . $ul;
                $lines[] = Html::tag('li', $content, $options);
            } else {
                // Item simples
                $isActive = !empty($item['active']);
                $url      = isset($item['url']) ? Url::to($item['url']) : '#';

                $linkClasses = ['nav-link'];
                if ($isActive) {
                    $linkClasses[] = $this->activeLinkClass;
                }

                $aOptions = ['class' => implode(' ', $linkClasses)];
                if (!empty($item['target'])) {
                    $aOptions['target'] = $item['target'];
                }

                $labelHtml = Html::tag('span', $item['label']);
                $badgeHtml = !empty($item['badge']) ? $item['badge'] : '';

                $content = Html::a(
                    $this->renderIcon($item) . $labelHtml . $badgeHtml,
                    $url,
                    $aOptions
                );

                $lines[] = Html::tag('li', $content, $options);
            }
        }

        return implode("\n", $lines);
    }

    protected function renderItem($item)
    {
        // Não usamos; sobrescrevemos renderItems() acima
        return '';
    }

    /**
     * Renderiza o ícone do item:
     * - Se 'icon' começar com 'cil-' => usa CoreUI SVG <svg><use xlink:href="...#cil-xxx"></use></svg>
     * - Se 'iconClass' vier => usa <i class="..."></i> (FA ou custom)
     * - Se 'iconStyle'/'icon' estilo AdminLTE => tenta fallback FA
     */
    protected function renderIcon(array $item): string
    {
        $icon = trim((string)($item['icon'] ?? ''));

        // CoreUI SVG (preferencial se passar 'cil-...')
        if ($icon !== '' && strpos($icon, 'cil-') === 0) {
            $href = $this->coreuiIconBaseHref . '#' . $icon;
            return '<svg class="nav-icon"><use xlink:href="'.Html::encode($href).'"></use></svg> ';
        }

        // Se foi passado iconClass direto (ex.: 'nav-icon fas fa-user')
        if (!empty($item['iconClass'])) {
            return '<i class="'.Html::encode($item['iconClass']).'"></i> ';
        }

        // Fallback: tentar montar FA a partir de 'iconStyle' + 'icon'
        $iconStyle = $item['iconStyle'] ?? 'fas';
        $name      = $icon !== '' ? 'fa-' . $icon : 'fa-circle';
        $cls       = implode(' ', ['nav-icon', $iconStyle, $name]);

        return '<i class="'.Html::encode($cls).'"></i> ';
    }

    /**
     * Um item "grupo" é considerado ativo se algum filho estiver ativo.
     */
    protected function isItemActive($item)
    {
        if (!empty($item['active'])) {
            return true;
        }
        if (empty($item['items'])) {
            return false;
        }
        foreach ($item['items'] as $child) {
            if ($this->isItemActive($child)) {
                return true;
            }
        }
        return false;
    }
}
