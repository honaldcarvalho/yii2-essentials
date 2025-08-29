<?php

namespace croacworks\essentials\themes\coreui\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * CoreuiMenu
 *
 * 'items' suportados:
 *  - 'label'       string
 *  - 'url'         array|string|null  Ex.: ['/user/index'] (usado para inferir o controller)
 *  - 'items'       array              Subitens (vira "nav-group")
 *  - 'badge'       string             HTML do badge
 *  - 'icon'        string             'cil-*' (CoreUI SVG) OU nome FA ('user')
 *  - 'iconClass'   string             Classe direta <i>
 *  - 'iconStyle'   string             Estilo FA ('fas','far')
 *  - 'target'      string
 *  - 'visible'     bool
 *  - 'options'     array              atributos do <li>
 *  - 'controller'  string             FQCN do controller (opcional, ajuda a desambiguar)
 *  - 'active'      string|array|bool|null
 *      -> ausente, null ou ''          : ativo em QUALQUER action do controller do item
 *      -> 'a;b;c' ou ['a','b']        : ativo se action atual ∈ lista
 *      -> igual ao controller (id/FQCN): ativo em QUALQUER action desse controller
 *      -> bool                         : força
 */
class CoreuiMenu extends \yii\widgets\Menu
{
    public $coreuiIconBaseHref = '/vendors/@coreui/icons/svg/free.svg';
    public $compactChildren    = true;
    public $openOnActive       = true;
    public $activeLinkClass    = 'active';

    public $options = [
        'class'         => 'sidebar-nav',
        'data-coreui'   => 'navigation',
        'data-simplebar'=> '',
    ];

    public $itemOptions     = ['class' => 'nav-item'];
    public $activateParents = true;
    public $encodeLabels    = false;

    /* ========================= RENDER ========================= */

    protected function renderItems($items)
    {
        $lines = [];

        foreach ($items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }

            // Header
            if (!empty($item['header'])) {
                $lines[] = Html::tag(
                    'li',
                    $this->encodeLabels ? Html::encode($item['label']) : $item['label'],
                    ['class' => 'nav-title']
                );
                continue;
            }

            // Divider
            if (!empty($item['divider'])) {
                $lines[] = Html::tag('li', '', ['class' => 'nav-divider']);
                continue;
            }

            $options     = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
            $hasChildren = !empty($item['items']);

            if ($hasChildren) {
                // === GRUPO ===
                // Regra 1: grupo ignora controller/active próprio. Só abre se algum descendente visível estiver ativo.
                Html::removeCssClass($options, 'nav-item');
                Html::addCssClass($options, 'nav-group');

                $groupActive = $this->isGroupActive($item); // SOMENTE descendentes

                if ($this->openOnActive && $groupActive) {
                    Html::addCssClass($options, 'show'); // CoreUI abre com 'show' no <li.nav-group>
                }

                $groupLinkClasses = ['nav-link', 'nav-group-toggle'];
                if ($groupActive) {
                    $groupLinkClasses[] = $this->activeLinkClass;
                }

                $link = Html::a(
                    $this->renderIcon($item) . Html::tag('span', $this->encodeLabels ? Html::encode($item['label']) : $item['label']),
                    '#',
                    [
                        'class'         => implode(' ', $groupLinkClasses),
                        'aria-expanded' => $groupActive ? 'true' : 'false',
                    ]
                );

                $childrenUlClasses = ['nav-group-items'];
                if ($this->compactChildren) {
                    $childrenUlClasses[] = 'compact';
                }

                $childrenHtml = $this->renderItems($item['items']);
                $ul           = Html::tag('ul', $childrenHtml, ['class' => implode(' ', $childrenUlClasses)]);

                $lines[] = Html::tag('li', $link . $ul, $options);
            } else {
                // === FOLHA ===
                $url      = isset($item['url']) ? Url::to($item['url']) : '#';
                $isActive = $this->isLeafActive($item);

                $linkClasses = ['nav-link'];
                if ($isActive) {
                    $linkClasses[] = $this->activeLinkClass;
                }

                $aOptions = ['class' => implode(' ', $linkClasses)];
                if (!empty($item['target'])) {
                    $aOptions['target'] = $item['target'];
                }

                $labelHtml = Html::tag('span', $this->encodeLabels ? Html::encode($item['label']) : $item['label']);
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

    protected function renderItem($item) { return ''; }

    protected function renderIcon(array $item): string
    {
        $icon = trim((string)($item['icon'] ?? ''));

        // CoreUI SVG
        if ($icon !== '' && strpos($icon, 'cil-') === 0) {
            $href = $this->coreuiIconBaseHref . '#' . $icon;
            return '<svg class="nav-icon"><use xlink:href="' . Html::encode($href) . '"></use></svg> ';
        }

        // Classe custom direta
        if (!empty($item['iconClass'])) {
            return '<i class="' . Html::encode($item['iconClass']) . '"></i> ';
        }

        // Fallback FA
        $iconStyle = $item['iconStyle'] ?? 'fas';
        $name      = $icon !== '' ? 'fa-' . $icon : 'fa-circle';
        $cls       = implode(' ', ['nav-icon', $iconStyle, $name]);

        return '<i class="' . Html::encode($cls) . '"></i> ';
    }

    /* ========================= ATIVAÇÃO ========================= */

    /**
     * Decide se um item está ativo conforme as regras:
     * - Grupo: ativo se ALGUM descendente visível estiver ativo (ignora próprio controller/active).
     * - Folha: aplica regras de controller/active do próprio item.
     */
    protected function isItemActive($item): bool
    {
        $hasChildren = !empty($item['items']);
        return $hasChildren ? $this->isGroupActive($item) : $this->isLeafActive($item);
    }

    /**
     * Grupo ativo se algum filho/neto/bisneto **visível** estiver ativo.
     * Ignora totalmente qualquer 'controller'/'active' do próprio grupo.
     */
    protected function isGroupActive(array $item): bool
    {
        if (empty($item['items']) || !is_array($item['items'])) {
            return false;
        }

        foreach ($item['items'] as $child) {
            $childVisible = !isset($child['visible']) || (bool)$child['visible'] === true;
            if (!$childVisible) {
                continue;
            }
            $hasChildren = !empty($child['items']);
            if ($hasChildren) {
                if ($this->isGroupActive($child)) {
                    return true;
                }
            } else {
                if ($this->isLeafActive($child)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Regra de item FOLHA (sem filhos):
     * - Usa controller do item (FQCN via 'controller' OU deduzido do 'url').
     * - Campo 'active' define ações:
     *    • ausente | null | '' | igual ao controller (id/FQCN) => ativo em qualquer action (desde que controller bata)
     *    • "a;b;c" | ['a','b','c'] => ativo se action atual ∈ lista (e controller bata)
     *    • bool => força
     */
    protected function isLeafActive(array $item): bool
    {
        $controller = Yii::$app->controller;
        if ($controller === null) return false;

        $currentControllerId   = $controller->id;
        $currentControllerFqcn = get_class($controller);
        $currentActionId       = $controller->action->id ?? '';

        // Controller do item (id/FQCN)
        [$itemControllerId, $itemControllerFqcn] = $this->extractItemController($item);

        // bool => força
        if (array_key_exists('active', $item) && is_bool($item['active'])) {
            return (bool)$item['active'];
        }

        // Precisa bater controller (id OU FQCN)
        $controllerMatches =
            (($itemControllerId   && $itemControllerId   === $currentControllerId) ||
             ($itemControllerFqcn && $itemControllerFqcn === $currentControllerFqcn));

        if (!$controllerMatches) {
            return false;
        }

        // Normaliza lista de actions a partir de 'active'
        $activeParam = $item['active'] ?? null;

        // Se 'active' ausente/null/'' => qualquer action (controller já bateu)
        if ($activeParam === null || $activeParam === '') {
            return true;
        }

        // Se 'active' é string e igual ao controller (id/FQCN) => qualquer action
        if (is_string($activeParam)) {
            $activeStr = ltrim(trim($activeParam), '\\');
            if ($activeStr === ($itemControllerId ?? '') || $activeStr === ($itemControllerFqcn ?? '')) {
                return true;
            }
        }

        // Lista de actions
        $actions = $this->parseActionList($activeParam);
        if (empty($actions)) {
            // Nada válido => qualquer action
            return true;
        }

        return in_array($currentActionId, $actions, true);
    }

    /**
     * Extrai controller do item:
     *  - usa 'controller' (FQCN) se presente;
     *  - senão deduz 'id' do 'url' (primeiro segmento da rota).
     */
    protected function extractItemController(array $item): array
    {
        $fqcn = isset($item['controller']) && is_string($item['controller'])
            ? ltrim($item['controller'], '\\')
            : null;

        $id = null;

        if (!empty($item['url'])) {
            if (is_array($item['url']) && !empty($item['url'][0]) && is_string($item['url'][0])) {
                $route = trim($item['url'][0], '/');
            } elseif (is_string($item['url'])) {
                $route = trim($item['url'], '/');
            } else {
                $route = '';
            }

            if ($route !== '' && $route !== '#') {
                $first = explode('/', $route)[0] ?? '';
                $id    = $first !== '' ? $first : null;
            }
        }

        return [$id, $fqcn];
    }

    protected function parseActionList($active): array
    {
        if ($active === null || $active === '' || is_bool($active)) return [];

        if (is_string($active)) {
            return $this->splitSemicolon($active);
        }

        if (is_array($active)) {
            $out = [];
            foreach ($active as $part) {
                if (is_string($part)) {
                    $out = array_merge($out, $this->splitSemicolon($part));
                }
            }
            $out = array_values(array_filter(array_map('strtolower', $out), static fn($v) => $v !== ''));
            return array_unique($out);
        }

        return [];
    }

    protected function splitSemicolon(string $s): array
    {
        $parts = array_map('trim', explode(';', strtolower($s)));
        return array_values(array_filter($parts, static fn($v) => $v !== ''));
    }
}