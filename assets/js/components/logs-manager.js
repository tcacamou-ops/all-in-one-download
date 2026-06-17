jQuery(document).ready(function ($) {
    // On cache la baniière de base 
    toggle_logs_banner($('#logs-status-banner').is(':hidden'));
    
    const $document = $(document); // Cache document lookup
    console.log(allI1d);
    // init events listeners
    $document.on('click', '#media-sync-logs', media_sync_logs);
    $document.on('click', '#tv-show-logs', tv_show_logs);
    $document.on('click', '#movie-logs', movie_logs);
    $document.on('click', '#toggle-logs-banner', function() {
        toggle_logs_banner($('#logs-status-banner').is(':hidden'));
    });

    function toggle_logs_banner(show) {
        const $btn = $('#toggle-logs-banner');
        if (show) {
            $('#logs-status-banner').show();
            $btn.attr('data-active', 'true');
        } else {
            $('#logs-status-banner').hide();
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
    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatLogContent(raw) {
        return raw
            .split('\n')
            .map(line => {
                if (/\[error\]/i.test(line))   return `<span class="alli1d-log-error">${line}</span>`;
                if (/\[warning\]/i.test(line))  return `<span class="alli1d-log-warning">${line}</span>`;
                if (/\[debug\]/i.test(line))    return `<span class="alli1d-log-debug">${line}</span>`;
                return `<span class="alli1d-log-notice">${line}</span>`;
            })
            .join('\n');
    }

    function media_sync_logs(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.get_log,
            {
                file: 'medias.log',
                num_lines: 100
            },
            function(response) {
                const raw  = escapeHtml(response.content || '(log vide)');
                const html = `<pre class="alli1d-log-viewer">${formatLogContent(raw)}</pre>`;
                allI1d.showModale(html);
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error : ' + request.responseJSON.message, 'error');
            }
        );
    }

    function tv_show_logs(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.get_log,
            {
                file: 'series.log',
                num_lines: 100
            },
            function(response) {
                const raw  = escapeHtml(response.content || '(log vide)');
                const html = `<pre class="alli1d-log-viewer">${formatLogContent(raw)}</pre>`;
                allI1d.showModale(html);
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error : ' + request.responseJSON.message, 'error');
            }
        );
    }

    function movie_logs(event) {
        allI1d.requestWPApi(
            allI1d.api.routes.get_log,
            {
                file: 'films.log',
                num_lines: 100
            },
            function(response) {
                const raw  = escapeHtml(response.content || '(log vide)');
                const html = `<pre class="alli1d-log-viewer">${formatLogContent(raw)}</pre>`;
                allI1d.showModale(html);
            },
            'GET',
            function(request, error) {
                allI1d.showToast('Error : ' + request.responseJSON.message, 'error');
            }
        );
    }

});