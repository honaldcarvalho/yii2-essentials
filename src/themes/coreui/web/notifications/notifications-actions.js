(function(){
  var cfg           = window.notifConfig || {};
  var csrfToken     = cfg.csrfToken;
  var readUrl       = cfg.readUrl;
  var deleteUrl     = cfg.deleteUrl;
  var deleteAllUrl  = cfg.deleteAllUrl;
  var pjaxContainer = cfg.pjaxContainer || '#pjax-notifications';
  var inFlight      = false;

  function t(msg){ return yii && yii.t ? yii.t('app', msg) : msg; }

  function confirmSwal(msg) {
    if (typeof Swal === 'undefined') {
      return Promise.resolve( confirm(msg) ? { isConfirmed: true } : { isConfirmed: false } );
    }
    return Swal.fire({
      title: msg,
      icon:'warning',
      showCancelButton:true,
      confirmButtonText: t('Yes'),
      cancelButtonText: t('Cancel')
    });
  }

  function postJson(url, payload) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/json','X-CSRF-Token': csrfToken},
      body: JSON.stringify(payload || {})
    }).then(function(r){ return r.json(); });
  }

  function safeReload() {
    if (inFlight) return;
    inFlight = true;
    $.pjax.reload({container: pjaxContainer, timeout: 0, scrollTo: false})
      .always(function(){ inFlight = false; });
  }

  function updateHeaderBadge(count) {
    var badge = document.getElementById('notif-badge');
    if (!badge) return;
    var n = Number(count || 0);
    badge.textContent = n;
    badge.style.display = n > 0 ? 'inline-block' : 'none';
  }

  document.addEventListener('click', function(ev){
    var t = ev.target;

    // Mark all read
    if (t.id === 'btn-mark-all-read') {
      ev.preventDefault();
      confirmSwal(t('Mark ALL notifications as read?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(readUrl, {all: 1})
          .then(function(resp){
            if (resp && (resp.ok === true || typeof resp.count !== 'undefined')) {
              Swal.fire(t('Done!'), t('All marked as read.'), 'success');
              updateHeaderBadge(resp.count);
              safeReload();
            } else {
              Swal.fire(t('Oops'), t('Could not mark as read.'), 'error');
            }
          })
          .catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
      });
      return;
    }

    // Delete read
    if (t.id === 'btn-delete-read') {
      ev.preventDefault();
      confirmSwal(t('Delete ALL read notifications?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteAllUrl, {onlyRead: 1})
          .then(function(resp){
            if (resp && resp.ok) {
              Swal.fire(t('Done!'), t('Read notifications deleted.'), 'success');
              safeReload();
            } else {
              Swal.fire(t('Oops'), t('Could not delete.'), 'error');
            }
          })
          .catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
      });
      return;
    }

    // Delete all
    if (t.id === 'btn-delete-all') {
      ev.preventDefault();
      confirmSwal(t('Delete ALL notifications (read and unread)?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteAllUrl, {})
          .then(function(resp){
            if (resp && resp.ok) {
              Swal.fire(t('Done!'), t('All notifications deleted.'), 'success');
              updateHeaderBadge(0);
              safeReload();
            } else {
              Swal.fire(t('Oops'), t('Could not delete.'), 'error');
            }
          })
          .catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
      });
      return;
    }

    // Delete one
    if (t.closest && t.closest('.js-notif-delete')) {
      ev.preventDefault();
      var btn = t.closest('.js-notif-delete');
      var id = btn.getAttribute('data-id');
      if (!id) return;
      confirmSwal(t('Delete this notification?')).then(function(res){
        if (!res.isConfirmed) return;
        postJson(deleteUrl + '?id=' + encodeURIComponent(id), {})
          .then(function(resp){
            if (resp && resp.ok) {
              Swal.fire(t('Done!'), t('Notification deleted.'), 'success');
              safeReload();
            } else {
              Swal.fire(t('Oops'), t('Could not delete.'), 'error');
            }
          })
          .catch(function(){ Swal.fire(t('Oops'), t('Network error.'), 'error'); });
      });
      return;
    }
  });
})();
