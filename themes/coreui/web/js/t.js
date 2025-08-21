window.onload = function(event) {

    $('.lang-menu button').click(function() {
        lang = $(this).data('lang');
        $('#lang-selected').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>');
        $.ajax({
          method: "POST",
          url: '/user/change-lang',
          data: { lang: lang }
        }).done(function( msg ) {
            location.reload();
        });

    });

};