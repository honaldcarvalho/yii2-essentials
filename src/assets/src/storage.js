(function($){
  function csrf() {
    if (typeof yii !== 'undefined' && yii.getCsrfToken) return yii.getCsrfToken();
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : null;
  }

  function apiGet(url, data) {
    return $.ajax({ url, data, method: 'GET', dataType: 'json' });
  }
  function apiPost(url, data, isFormData) {
    var opts = { url, method: 'POST', dataType: 'json' };
    if (isFormData) {
      opts.data = data;
      opts.processData = false;
      opts.contentType = false;
    } else {
      data = data || {};
      var t = csrf(); if (t) data._csrf = t;
      opts.data = data;
    }
    return $.ajax(opts);
  }

  // -------- FileUploadInput --------
  function initFileUpload($wrap){
    var $hidden = $wrap.find('input[type=hidden].cw-file-id');
    var $file   = $wrap.find('input[type=file].cw-file-input');
    var $prog   = $wrap.find('.cw-progress');
    var $bar    = $wrap.find('.cw-progress-bar');
    var $prev   = $wrap.find('.cw-preview');
    var $btnRm  = $wrap.find('.cw-btn-remove');

    function setPreviewByInfo(info) {
      $prev.empty();
      if (!info) return;
      var url = info.urlThumb || info.url;
      if (info.type === 'image' && url) {
        $prev.append($('<img>').attr('src', url));
      } else if (info.type === 'video' && url) {
        $prev.append($('<img>').attr('src', info.urlThumb || url));
        $prev.append($('<div>').text(info.name + ' (' + (info.extension||'') + ')'));
      } else {
        $prev.append($('<div>').text(info.name || ('Arquivo #' + info.id)));
      }
    }

    // carga inicial se já houver id
    var currentId = $hidden.val();
    if (currentId) {
      apiGet('/storage/info', { id: currentId }).done(function(resp){
        if (resp && resp.ok && resp.data) setPreviewByInfo(resp.data);
      });
    }

    // upload on file change
    $file.on('change', function(){
      var f = this.files && this.files[0];
      if (!f) return;
      var fd = new FormData();
      fd.append('file', f);
      fd.append('save', 1);
      fd.append('thumb_aspect', $wrap.data('thumbAspect') || 1);
      fd.append('_csrf', csrf() || '');

      $prog.show(); $bar.css('width','0%');

      $.ajax({
        url: '/storage/upload',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        xhr: function(){
          var xhr = $.ajaxSettings.xhr();
          if (xhr.upload) {
            xhr.upload.addEventListener('progress', function(e){
              if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 100);
                $bar.css('width', pct + '%');
              }
            }, false);
          }
          return xhr;
        }
      }).done(function(resp){
        if (resp && resp.ok) {
          var d = resp.data;
          $hidden.val(d.id);
          setPreviewByInfo(d);
        } else {
          alert((resp && (resp.error || resp.errors)) || 'Falha no upload');
        }
      }).fail(function(){
        alert('Erro de rede no upload.');
      }).always(function(){
        $prog.hide(); $bar.css('width','0%');
        $file.val('');
      });
    });

    // remover (apenas limpa o campo; se quiser deletar no servidor, ligue data-delete-on-clear)
    $btnRm.on('click', function(e){
      e.preventDefault();
      var delServer = $wrap.data('deleteOnClear') === 1 || $wrap.data('deleteOnClear') === '1' || $wrap.data('deleteOnClear') === true;
      var id = $hidden.val();
      $hidden.val(''); $prev.empty();

      if (delServer && id) {
        apiPost('/storage/delete?id=' + encodeURIComponent(id), {}).done(function(resp){
          if (!resp || !resp.ok) {
            console.warn('Falha ao deletar no servidor', resp);
          }
        });
      }
    });
  }

  // -------- MediaPicker --------
  function initMediaPicker($btn){
    var target = $btn.data('targetInput');
    if (!target) return;
    var $target = $('#' + target);
    var modalId = 'cw-media-picker-' + Math.random().toString(36).slice(2);
    var html = ''+
      '<div class="modal fade" id="'+modalId+'" tabindex="-1" role="dialog">'+
      ' <div class="modal-dialog modal-lg" role="document">'+
      '  <div class="modal-content">'+
      '   <div class="modal-header"><h5 class="modal-title">Biblioteca de Mídia</h5>'+
      '    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>'+
      '   </div>'+
      '   <div class="modal-body">'+
      '     <div class="cw-media-toolbar">'+
      '       <input type="text" class="form-control form-control-sm cw-media-q" placeholder="Buscar...">'+
      '       <select class="form-control form-control-sm cw-media-type"><option value="">Todos</option><option value="image">Imagens</option><option value="video">Vídeos</option><option value="doc">Docs</option></select>'+
      '       <button class="btn btn-sm btn-primary cw-media-reload">Buscar</button>'+
      '     </div>'+
      '     <div class="cw-media-grid"></div>'+
      '   </div>'+
      '   <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button></div>'+
      '  </div>'+
      ' </div>'+
      '</div>';
    var $modal = $(html).appendTo('body');

    function load(page){
      page = page || 1;
      var q = $modal.find('.cw-media-q').val();
      var type = $modal.find('.cw-media-type').val();
      apiGet('/storage/list', { q:q, type:type, page:page, pageSize:24 }).done(function(resp){
        var $grid = $modal.find('.cw-media-grid').empty();
        if (!resp || !resp.ok || !resp.data || !resp.data.length) {
          $grid.append('<div class="text-muted">Nada encontrado.</div>');
          return;
        }
        resp.data.forEach(function(it){
          var url = it.urlThumb || it.url;
          var name = it.name || ('#'+it.id);
          var $card = $('<div class="cw-media-card" data-id="'+it.id+'">');
          if (it.type === 'image' && url) {
            $card.append($('<img>').attr('src', url));
          } else if (it.type === 'video' && url) {
            $card.append($('<img>').attr('src', url));
          } else {
            $card.append($('<div>').text(name));
          }
          $card.append($('<div class="small text-muted mt-1">').text(name));
          $card.on('click', function(){
            $target.val(it.id).trigger('change');
            // se houver preview ao lado do input, tente atualizar
            var box = $target.closest('.cw-fileupload');
            if (box.length) {
              var prev = box.find('.cw-preview').empty();
              var purl = it.urlThumb || it.url;
              if (it.type === 'image' && purl) prev.append($('<img>').attr('src', purl));
              else prev.append($('<div>').text(it.name));
            }
            $modal.modal('hide');
          });
          $grid.append($card);
        });
      });
    }

    $modal.on('shown.bs.modal', function(){ load(1); });
    $modal.find('.cw-media-reload').on('click', function(){ load(1); });
    $btn.on('click', function(){ $modal.modal('show'); });
  }

  // auto-init
  $(function(){
    $('.cw-fileupload').each(function(){ initFileUpload($(this)); });
    $('[data-cw-media-picker]').each(function(){ initMediaPicker($(this)); });
  });
})(jQuery);
