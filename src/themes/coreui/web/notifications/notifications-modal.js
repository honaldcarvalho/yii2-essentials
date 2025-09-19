(function(){
  var cfg       = window.notifConfig || {};
  var csrfToken = cfg.csrfToken;
  var listUrl   = cfg.listUrl;
  var readUrl   = cfg.readUrl;
  var deleteUrl = cfg.deleteUrl;
  var deleteAllUrl = cfg.deleteAllUrl;

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

  function openNotificationsModal() {
    if (!listUrl) return;
    getJson(listUrl).then(function(data){
      var items = (data && data.items) ? data.items : [];
      var html = items.length
        ? items.map(function(i){
            return '<div>'+ (i.title || '') +'</div>';
          }).join('')
        : '<div>'+t('No notifications to show.')+'</div>';

      Swal.fire({
        title: t('Notifications'),
        html: html,
        width: 800,
        showConfirmButton: false,
        showCloseButton: true
      });

      if (typeof data.unread_count !== 'undefined') {
        updateHeaderBadge(data.unread_count);
      }
    }).catch(function(){
      Swal.fire(t('Oops'), t('Failed to load notifications.'), 'error');
    });
  }

  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('#notif-bell, .js-open-notifications');
    if (!btn) return;
    ev.preventDefault();
    openNotificationsModal();
  });

  window.openNotificationsModal = openNotificationsModal;
})();
