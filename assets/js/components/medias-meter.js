jQuery(document).ready(function ($) {
    $(allI1d).on('allI1d:refreshMedias', function (response) {
        allI1d.requestWPApi(
            allI1d.api.routes.media,
            {},
            function(response) {
                $('#medias-meter').text(response.length);
            },
            'GET'
        );
    });
});