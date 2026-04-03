jQuery(document).ready(function ($) {
    /**
     * Request the WP Rest API.
     * @param string route
     * @param array data
     * @param function callback
     * @param string method (default: 'GET')
     */
    allI1d.requestWPApi = function requestWPApi(route, data, callback, method = 'GET', error_callback = null) {
        const $trigger = $(document.activeElement);

        $.ajax({
            url: route,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', allI1d.api.nonce);
            },
            method: method,
            data: data,
            success: function(response) {
                callback(response, data);
            },
            error: function(request, error) {
                if (error_callback) {
                    error_callback(request, error);
                }
                else {
                    console.log(request);
                    console.log(error);
                }
            }
        });
    }
});