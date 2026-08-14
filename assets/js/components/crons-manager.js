jQuery(document).ready(function ($) {
    allI1d.toggle_cron_banner = function(show) {
        const $btn = $('#toggle-cron-banner');
        if (show) {
            allI1d.toggle_logs_banner(false);
            $('#cron-status-banner').show();
            $btn.attr('data-active', 'true');
        } else {
            $('#cron-status-banner').hide();
            $btn.attr('data-active', 'false');
        }
        updateToolbarPosition();
    }

    // On cache la banière de base 
    allI1d.toggle_cron_banner($('#cron-status-banner').is(':hidden'));
    
    const $document = $(document); // Cache document lookup
    // init events listeners
    $document.on('click', '#media-sync-cron', media_sync_cron);
    $document.on('click', '#tv-show-cron', tv_show_cron);
    $document.on('click', '#movie-cron', movie_cron);
    $document.on('click', '#feed-catalog-cron', feed_catalog_cron);
    $document.on('click', '#reset-indexing-button', reset_indexing);
    $document.on('click', '#toggle-cron-banner', function() {
        allI1d.toggle_cron_banner($('#cron-status-banner').is(':hidden'));
    });

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

    function feed_catalog_cron(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.feed_catalog_run_cron,
            {},
            function(response, data) {
                allI1d.showToast('Feed catalog indexing cron started', 'success');
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error in cron: ' + request.responseJSON.message, 'error');
            }
        );
    }

    function reset_indexing(event) {
        if (!window.confirm('Réinitialiser l\'indexation ? Cette action vide le catalogue indexé et remet à zéro le suivi de recherche générale pour tous les films et séries. Cette action est irréversible.')) {
            return;
        }
        allI1d.requestWPApi(
            allI1d.api.routes.indexing_reset,
            {},
            function(response, data) {
                allI1d.showToast('Indexation réinitialisée (' + response.catalog_removed + ' entrées catalogue supprimées)', 'success');
            },
            'POST',
            function(request, error) {
                allI1d.showToast('Erreur lors de la réinitialisation : ' + (request.responseJSON ? request.responseJSON.message : error), 'error');
            }
        );
    }

});