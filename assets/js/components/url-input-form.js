jQuery(document).ready(function ($) {
const $document = $(document); // Cache document lookup

// init events listeners
$document.on('click', '#submit-url', add_url);


function add_url(event) {
    let url_to_add = $('#new_url').val();
    allI1d.requestWPApi(
        allI1d.api.routes.media,
        {
            url: url_to_add
        },
        function(response, data) {
            allI1d.showToast('URL added: ' + url_to_add, 'success');
            $('#new_url').val(''); // Clear the input field
            $(allI1d).trigger('allI1d:refreshMedias');
        },
        'POST',
        function(request, error) {
            allI1d.showToast('Error adding URL: ' + request.responseJSON.message, 'error');
        }
    );
}
});