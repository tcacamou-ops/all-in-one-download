jQuery(document).ready(function ($) {
    // On cache la baniière de base 
    toggle_cron_banner($('#cron-status-banner').is(':hidden'));
    
    const $document = $(document); // Cache document lookup
    console.log(allI1d);
    // init events listeners
    $document.on('click', '#media-sync-cron', media_sync_cron);
    $document.on('click', '#tv-show-cron', tv_show_cron);
    $document.on('click', '#movie-cron', movie_cron);
    $document.on('click', '#toggle-cron-banner', function() {
        toggle_cron_banner($('#cron-status-banner').is(':hidden'));
    });

    function toggle_cron_banner(show) {
        const $btn = $('#toggle-cron-banner');
        if (show) {
            $('#cron-status-banner').show();
            $btn.attr('data-active', 'true');
        } else {
            $('#cron-status-banner').hide();
            $btn.attr('data-active', 'false');
        }
        updateToolbarPosition();
    }

    function updateToolbarPosition() {
        let maxHeight = 0;
        $('#cron-status-banner:visible, #logs-status-banner:visible').each(function() {
            maxHeight = Math.max(maxHeight, $(this).outerHeight());
        });
        const bottom = maxHeight > 0 ? maxHeight + 16 : 24;
        $('.alli1d-toolbar').css('bottom', bottom + 'px');
    }
    function media_sync_cron(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.media_run_cron,
            {},
            function(response, data) {
                allI1d.showToast('Media sync cron started', 'success');
                $(allI1d).trigger('allI1d:refreshMedias');
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error in cron: ' + request.responseJSON.message, 'error');
            }
        );
    }

    function tv_show_cron(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.tvshow_run_cron,
            {},
            function(response, data) {
                allI1d.showToast('Tv Show sync cron started', 'success');
                $(allI1d).trigger('allI1d:refreshMedias');
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error in cron: ' + request.responseJSON.message, 'error');
            }
        );
    }

     function movie_cron(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.movie_run_cron,
            {},
            function(response, data) {
                allI1d.showToast('Movie sync cron started', 'success');
                $(allI1d).trigger('allI1d:refreshMedias');
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error in cron: ' + request.responseJSON.message, 'error');
            }
        );
    }

});