// pjax-bootstrap.js — Ciclo de vida genérico para Yii2 + PJAX
(function ($, window, document) {
  'use strict';

  // ===== Registry de inicializadores =====
  const initializers = [];
  function onPjaxReady(fn) { if (typeof fn === 'function') initializers.push(fn); }

  function runInitializers($root) {
    // (1) Executa <script> inline vindos pelo PJAX (ver marcações abaixo)
    execInlineScripts($root);

    // (2) Roda inicializadores registrados globalmente
    for (const fn of initializers) {
      try { fn($root); } catch (e) { console.error('[PJAX init]', e); }
    }
  }

  // ===== Executa <script> inline que veio na parcial =====
  // Marque seus scripts inline com UM dos padrões abaixo:
  //   <script data-exec-on-pjax> ... </script>            → executa SEMPRE quando vier via PJAX
  //   <script data-exec-once> ... </script>               → executa só 1x por elemento
  //   <script type="application/pjax-init"> ... </script> → executa SEMPRE (alternativa sem data-attrs)
  function execInlineScripts($scope) {
    const $scripts = $scope.find('script[data-exec-on-pjax], script[data-exec-once], script[type="application/pjax-init"]');
    $scripts.each(function () {
      const $s = $(this);

      // controla execuções "once"
      const once = $s.is('[data-exec-once]');
      const key  = $s.attr('data-exec-key') || '';
      const flag = 'pjaxExec' + (key ? ':' + key : '');

      if (once && $s.data(flag)) return;

      const src = $s.attr('src');
      if (src) {
        // carregamento síncrono (raro; prefira inicializadores em onPjaxReady)
        $.ajax({ url: src, dataType: 'script', cache: true, async: false });
      } else {
        $.globalEval($s.text());
      }

      if (once) $s.data(flag, true);
    });
  }

  // ===== Delegation helper (eventos que não quebram com troca de DOM) =====
  function delegate(event, selector, handler, ns) {
    const evt = ns ? `${event}.${ns}` : event;
    $(document).off(evt, selector).on(evt, selector, handler);
  }

  // ===== Re-init de plugins populares (estenda aqui o que você usa) =====
  onPjaxReady(function ($root) {
    // Fancybox 5
    if (window.Fancybox && typeof Fancybox.bind === 'function') {
      // evita rebind global: passa só os elementos do container
      const list = $root.find('[data-fancybox]').get();
      if (list.length) Fancybox.bind(list);
    }

    // Select2
    if ($.fn.select2) {
      $root.find('.select2').each(function () {
        if (!$(this).data('select2')) $(this).select2();
      });
    }

    // Bootstrap Tooltip
    if (window.bootstrap?.Tooltip) {
      $root.find('[data-bs-toggle="tooltip"]').each(function () {
        if (!this._tooltip) this._tooltip = new bootstrap.Tooltip(this);
      });
    }

    // Toastr não precisa de re-init (é global), mas é aqui que você poderia reconfigurar, se quiser.

    // jQuery UI
    if ($.fn.datepicker) {
      $root.find('[data-widget="datepicker"]').each(function () {
        if (!$(this).data('datepicker')) $(this).datepicker(); // exemplo
      });
    }

    // iCheck / outros plugins: re-instancie aqui conforme necessidade.
  });

  // ===== Dispara no load inicial =====
  $(function () { runInitializers($(document)); });

  // ===== Dispara após QUALQUER atualização PJAX =====
  $(document).on('pjax:end pjax:success', function (e) {
    const $root = $(e.target);
    runInitializers($root.length ? $root : $(document));
  });

  // ===== Expor helpers globais =====
  window.PjaxBootstrap = { onPjaxReady, delegate, runInitializers };
  window.onPjaxReady   = onPjaxReady; // alias curto

})(jQuery, window, document);
