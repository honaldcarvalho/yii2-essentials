(function(){
  var cfg          = window.notifConfig || {};
  var csrfToken    = cfg.csrfToken;
  var listUrl      = cfg.listUrl;
  var viewUrl      = cfg.viewUrl;        // <--- NOVO
  var readUrl      = cfg.readUrl;
  var deleteUrl    = cfg.deleteUrl;
  var deleteAllUrl = cfg.deleteAllUrl;
  var markOnOpen   = (cfg.markOnOpen !== false); // default true

  function t(msg){ return yii && yii.t ? yii.t('app', msg) : msg; }

  function postJson(url, payload) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/json','X-CSRF-Token': csrfToken},
      body: JSON.stringify(payload || {})
    }).then(function(r){ return r.json(); });
  }

  function getJson(url) {
    return fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {'Accept':'application/json'}
    }).then(function(r){ return r.json(); });
  }

  function updateHeaderBadge(count) {
    var badge = document.getElementById('notif-badge');
    if (!badge) return;
    var n = Number(count || 0);
    badge.textContent = n;
    badge.style.display = n > 0 ? 'inline-block' : 'none';
  }

  // =============== DETALHE ===============
  function openNotificationDetail(id) {
    if (!id || !viewUrl) return;

    getJson(viewUrl + '?id=' + encodeURIComponent(id))
      .then(function(data){
        if (!data || !data.ok || !data.item) {
          Swal.fire(t('Oops'), t('Failed to load notification.'), 'error');
          return;
        }

        var it   = data.item; // {id,title,description,content_html,url,icon,read,created_at,from,tags,attachments:[{name,url}]}
        var html = buildDetailHtml(it);

        // marca como lida automaticamente ao abrir (opcional)
        var markPromise = Promise.resolve();
        if (markOnOpen && !it.read && readUrl) {
          markPromise = postJson(readUrl, {id: it.id}).then(function(resp){
            if (resp && typeof resp.count !== 'undefined') updateHeaderBadge(resp.count);
            it.read = true;
            markRowAsRead(it.id);
          }).catch(function(){ /* ignore */ });
        }

        markPromise.finally(function(){
          Swal.fire({
            title: (it.title || t('Notification')),
            html: html,
            width: 800,
            showCancelButton: false,
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: function(modalEl){
              attachDetailHandlers(modalEl, it);
            }
          });
        });

        if (typeof data.unread_count !== 'undefined') {
          updateHeaderBadge(data.unread_count);
        }
      })
      .catch(function(){
        Swal.fire(t('Oops'), t('Failed to load notification.'), 'error');
      });
  }

  function buildDetailHtml(it){
    // Segurança: assuma que content_html já vem sanitizado do servidor.
    // Se não vier, você pode cair para texto puro.
    var content = (it.content_html && typeof it.content_html === 'string')
      ? it.content_html
      : '<pre style="white-space:pre-wrap">' + (it.content || '') + '</pre>';

    var desc = (it.description ? '<div class="notif-desc">'+ it.description +'</div>' : '');
    var meta = [
      it.created_at ? '<div><strong>'+t('Created at')+':</strong> '+ it.created_at +'</div>' : '',
      it.from       ? '<div><strong>'+t('From')+':</strong> '+ it.from +'</div>' : '',
      it.tags && it.tags.length ? '<div><strong>'+t('Tags')+':</strong> '+ it.tags.join(', ') +'</div>' : ''
    ].join('');

    var atts = (it.attachments && it.attachments.length)
      ? (
        '<div class="notif-atts"><strong>'+t('Attachments')+':</strong><ul style="margin:.25rem 0 0 .9rem">'
        + it.attachments.map(function(a){
            var name = a.name || a.url || '';
            return '<li><a href="'+a.url+'" target="_blank" rel="noopener">'+ name +'</a></li>';
          }).join('')
        + '</ul></div>'
      )
      : '';

    var urlBtn = it.url ? '<button class="btn-open-link" data-url="'+it.url+'">'+t('Open link')+'</button>' : '';

    return [
      '<style>',
        '.notif-detail .meta{opacity:.8;margin-bottom:.5rem;font-size:.9rem}',
        '.notif-detail .actions{display:flex;gap:.5rem;margin:.75rem 0 0}',
        '.notif-detail button{padding:.4rem .65rem;border:1px solid #e1e5ea;border-radius:.5rem;background:#f1f3f5;cursor:pointer;font-size:.9rem}',
        '.notif-detail .danger{background:#ffe5e5;border-color:#ffb3b3}',
        '.notif-desc{opacity:.95;margin:.35rem 0}',
        '.notif-content{margin-top:.5rem}',
      '</style>',
      '<div class="notif-detail">',
        '<div class="meta">', meta ,'</div>',
        desc,
        '<div class="notif-content">', content ,'</div>',
        atts,
        '<div class="actions">',
          (!it.read ? '<button class="btn-mark-read" data-id="'+it.id+'">'+t('Mark read')+'</button>' : ''),
          urlBtn,
          '<button class="btn-delete danger" data-id="'+it.id+'">'+t('Delete')+'</button>',
        '</div>',
      '</div>'
    ].join('');
  }

  function attachDetailHandlers(modalEl, it){
    modalEl.addEventListener('click', function(ev){
      var btn = ev.target.closest('button');
      if (!btn) return;

      if (btn.classList.contains('btn-mark-read')) {
        postJson(readUrl, {id: it.id}).then(function(resp){
          if (resp && typeof resp.count !== 'undefined') updateHeaderBadge(resp.count);
          markRowAsRead(it.id);
          btn.remove(); // remove o botão, já que ficou lida
          Swal.fire(t('Done!'), t('Marked as read.'), 'success');
        }).catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
        return;
      }

      if (btn.classList.contains('btn-open-link')) {
        var url = btn.getAttribute('data-url');
        if (url) window.open(url, '_blank', 'noopener');
        return;
      }

      if (btn.classList.contains('btn-delete')) {
        Swal.fire({
          title: t('Delete this notification?'),
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: t('Yes'),
          cancelButtonText: t('Cancel')
        }).then(function(res){
          if (!res.isConfirmed) return;
          postJson(deleteUrl + '?id=' + encodeURIComponent(it.id), {}).then(function(resp){
            if (resp && resp.ok) {
              removeRow(it.id);
              Swal.close(); // fecha o modal
              Swal.fire(t('Done!'), t('Notification deleted.'), 'success');
            } else {
              Swal.fire(t('Oops'), t('Could not delete.'), 'error');
            }
          }).catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
        });
        return;
      }
    });
  }

  // Helpers: atualiza a linha na lista/header se existir
  function markRowAsRead(id){
    // header dropdown
    var row = document.querySelector('.notif-row[data-id="'+id+'"]');
    if (row) row.classList.remove('is-unread');

    // grid/list index
    var tr = document.querySelector('tr[data-key="'+id+'"]');
    if (tr) tr.classList.remove('is-unread');
  }
  function removeRow(id){
    var row = document.querySelector('.notif-row[data-id="'+id+'"]');
    if (row) row.remove();
    var tr = document.querySelector('tr[data-key="'+id+'"]');
    if (tr) tr.remove();
  }

  // =============== ABRIR POR CLIQUE ===============
  // Use .js-notif-open data-id="<ID>" tanto no header quanto na listagem.
  document.addEventListener('click', function(ev){
    var el = ev.target.closest('.js-notif-open');
    if (!el) return;
    var id = el.getAttribute('data-id');
    if (!id) return;
    ev.preventDefault();
    openNotificationDetail(id);
  });

  // (opcional) ainda pode manter o “abre tudo” se você usa o botão do sino
  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('#notif-bell-full'); // se tiver um botão alternativo
    if (!btn) return;
    ev.preventDefault();
    // aqui você poderia chamar um modal com TODAS, mas seu fluxo agora é por item.
  });
})();
