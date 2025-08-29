<?php

namespace croacworks\essentials\themes\coreui\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

class CoreuiMenu extends \yii\widgets\Menu
{
    public $coreuiIconBaseHref = '/vendors/@coreui/icons/svg/free.svg';
    public $compactChildren    = true;
    public $openOnActive       = true;
    public $activeLinkClass    = 'active';

    public $options = [
        'class' => 'sidebar-nav',
        'data-coreui' => 'navigation',
        'data-simplebar' => '',
    ];

    public $itemOptions     = ['class' => 'nav-item'];
    public $activateParents = true;
    public $encodeLabels    = false;

    protected function renderItems($items)
    {
        $lines = [];

        foreach ($items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }

            // Header
            if (!empty($item['header'])) {
                $lines[] = Html::tag('li',
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
                // GRUPO: só ativa se filho ativo (regra 1)
                Html::removeCssClass($options, 'nav-item');
                Html::addCssClass($options, 'nav-group');

                $groupActive = $this->isGroupActive($item); // <<< apenas filhos

                if ($this->openOnActive && $groupActive) {
                    Html::addCssClass($options, 'show'); // CoreUI abre com show no <li.nav-group>
                }

                $groupLinkClasses = ['nav-link', 'nav-group-toggle'];
                if ($groupActive) {
                    $groupLinkClasses[] = $this->activeLinkClass;
                }

                $link = Html::a(
                    $this->renderIcon($item) . Html::tag('span', $this->encodeLabels ? Html::encode($item['label']) : $item['label']),
                    '#',
                    [
                        'class' => implode(' ', $groupLinkClasses),
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
                // FOLHA
                $url      = isset($item['url']) ? Url::to($item['url']) : '#';
                $isActive = $this->isLeafActive($item); // <<< regra 2

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

    /* =======================
       ATIVAÇÃO: REGRAS NOVAS
       ======================= */

    // Regra 1: grupo ativo somente se filho visível ativo
    protected function isGroupActive(array $item): bool
    {
        if (empty($item['items']) || !is_array($item['items'])) {
            return false;
        }
        foreach ($item['items'] as $child) {
            $childVisible = !isset($child['visible']) || (bool)$child['visible'] === true;
            if ($childVisible && ($this->hasChildren($child) ? $this->isGroupActive($child) : $this->isLeafActive($child))) {
                return true;
            }
        }
        return false;
    }

    // Regra 2: folha ativa conforme controller + active do item
    protected function isLeafActive(array $item): bool
    {
        $controller = Yii::$app->controller;
        if ($controller === null) return false;

        $currentControllerId   = $controller->id;
        $currentControllerFqcn = get_class($controller);
        $currentActionId       = $controller->action->id ?? '';

        // Controller do item (FQCN explícito tem prioridade; senão, inferir do url)
        [$itemControllerId, $itemControllerFqcn] = $this->extractItemController($item);

        // Controller deve bater
        $controllerMatches =
            ($itemControllerId   && $itemControllerId   === $currentControllerId) ||
            ($itemControllerFqcn && $itemControllerFqcn === $currentControllerFqcn);

        if (!$controllerMatches) {
            return false;
        }

        // Normaliza 'active'
        $rawActive = $item['active'] ?? null;
        if (is_string($rawActive)) {
            $rawActive = trim($rawActive);
            if ($rawActive === '') {
                $rawActive = null; // vazio => qualquer action do controller
            }
        }

        // bool => força
        if (is_bool($rawActive)) {
            return $rawActive;
        }

        // Ausente/null => qualquer action do controller
        if ($rawActive === null) {
            return true;
        }

        // String igual ao controller (id ou FQCN) => qualquer action
        if (is_string($rawActive)) {
            $activeStr = ltrim($rawActive, '\\');
            if ($activeStr === ($itemControllerId ?? '') || $activeStr === ($itemControllerFqcn ?? '')) {
                return true;
            }
        }

        // Lista de actions ("a;b;c")
        $actions = $this->parseActionList($rawActive);
        if (empty($actions)) {
            return true; // lista vazia ⇒ tratar como "qualquer action"
        }

        return in_array($currentActionId, $actions, true);
    }

    protected function hasChildren(array $item): bool
    {
        return !empty($item['items']) && is_array($item['items']);
    }

    /**
     * Extrai o controller do item:
     *  - 'controller' FQCN (opcional)
     *  - ou infere a partir do route em 'url'
     * Ignora '#'/vazio para não sujar o id.
     * @return array [controllerId|null, controllerFqcn|null]
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
        if ($active === null || is_bool($active)) return [];

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
