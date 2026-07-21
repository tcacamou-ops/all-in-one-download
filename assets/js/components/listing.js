jQuery(document).ready(function ($) {
    function refreshListing() {
        const searchQuery = $('#search-input').val();
        const listingType = $('.filter-listing-type [type="radio"]:checked').val();
        allI1d.requestWPApi(
            allI1d.api.routes.listing_refresh,
            {
                search: searchQuery,
                type: listingType
            },
            function (response, data) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                $('#listing-container').html(data.message);
            },
            'POST',
            function (request, error) {
                $('#listing-container').html('<p class="error">An error occurred while fetching listings.</p>');
            }
        );
    }

    // let searchTimeout;
    // $(document).on('input', '#search-input', function () {
    //     clearTimeout(searchTimeout);
    //     searchTimeout = setTimeout(function () {
    //         refreshListing();
    //     }, 800); // 400ms après la dernière frappe
    // });

    $(document).on('change', '.filter-listing-type', function () {
        refreshListing();
    });
    $(document).on('keypress', '#search-input', function (e) {
        if(e.which == 13) {
            try {
                const value = $('#search-input').val();
                new URL(value);
                console.log(value);
                allI1d.requestWPApi(
                    allI1d.api.routes.media,
                    {
                        url: value
                    },
                    function(response, data) {
                        allI1d.showToast('URL added: ' + value, 'success');
                        $('#search-input').val(''); // Clear the input field
                    },
                    'POST',
                    function(request, error) {
                        allI1d.showToast('Error adding URL: ' + request.responseJSON.message, 'error');
                    }
                );
                
            } catch (err) {
                refreshListing();
            }
            console.log('Search input keypress');
        }
    });

    $(document).on('click', '.listing-item', function () {
        let itemId = $(this).data('id');
        let route = allI1d.api.routes.movie_item;
        if ($(this).data('type') === "tvshow") {
            route = allI1d.api.routes.tvshow_item;
        }
        console.log('Listing item clicked:', $(this).data('id'));
        allI1d.requestWPApi(
            route,
            {
                itemId: itemId
            },
            function (response, data) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                allI1d.showModale(data.message);
            },
            'GET',
            function (request, error) {
                $('#listing-container').html('<p class="error">An error occurred while fetching listings.</p>');
            }
        );
    });

    $(document).on('click', '#add-season', function () {
        console.log('Add season button clicked');
        let randomId = Math.floor(Math.random() * 1000000);
        let html = `<div id="saison-`+randomId+`" class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
                <label class="flex flex-col min-w-30 flex-1">
                    <p class="text-[#141414] text-base font-medium leading-normal pb-2">New Season</p>
                    <input data-saison-id="`+randomId+`" class="new-saison form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal" placeholder="Numéro de la saison" value="">
                </label>
                <label class="flex flex-col min-w-40 flex-1">
                    <input id="new-saison-`+randomId+`-last-ep" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal" placeholder="Dernier épisode dl" value="">
                </label>
                <label class="flex flex-col min-w-40 flex-1">
                  <button data-saison-id="`+randomId+`" class="delete-season-btn ml-2 text-[#757575] hover:text-red-600 p-[15px]" title="Supprimer la saison" data-saison-id="'.$saison['id'].'" style="background:transparent;border:none;cursor:pointer;align-items:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3a3 3 0 0 1 6 0h5a1 1 0 1 1 0 2h-1.07l-.86 13.77A3 3 0 0 1 15.08 21H8.92a3 3 0 0 1-2.99-2.23L5.07 5H4a1 1 0 1 1 0-2h5Zm1 0a1 1 0 0 1 2 0h-2Zm7.07 2H6.93l.85 13.6a1 1 0 0 0 .99.8h6.16a1 1 0 0 0 .99-.8L17.07 5ZM9 9a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm3 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>
                  </button>
                </label>
                
                </div>`;
        $('#seasons-container').append(html);
    });

    $(document).on('click', '.delete-season-btn', function () {
        $('#saison-' + $(this).data('saison-id')).remove();
    });

    $(document).on('click', '.save-tv-show', function () {
        console.log('Save TV Show button clicked');
        let seasons = [];
        $('.last-ep').each(function () {
            let saisonId = $(this).data('saison-id');
            let lastEpisode = $(this).val();
            let isActive = $("#saison-" + saisonId + "-isactiv").val();
            seasons.push({
                id: saisonId,
                lastepisode: lastEpisode,
                status: isActive
            });
        });

        $('.new-saison').each(function () {
            console.log('New season input:', $(this).val());
            let saisonNumber = parseInt($(this).val());
            if( !isNaN(saisonNumber) && saisonNumber >= 0) {
                let saisonId = $(this).data('saison-id');
                let lastEpisode = $('#new-saison-' + saisonId + '-last-ep').val()?? 0;
                seasons.push({
                    id: saisonNumber,
                    lastepisode: lastEpisode,
                    status: "actif"
                });
            }
        });

        let searchTitle = $('.search_title').val();
        let status = $('.status').val();
        let audioFormat = $('.audio_format').val();
        let itemId = $('.item-id').val();

        allI1d.requestWPApi(
            allI1d.api.routes.tvshow,
            {
                tvShowId: itemId,
                tvShowStatus: status,
                tvShowSearchTitle: searchTitle,
                tvShowAudioFormat: audioFormat,
                tvShowSeasons: seasons
            },
            function (response, data) {
                allI1d.closeModale();
                allI1d.showToast('TV Show saved successfully!', 'success');
            },
            'POST',
            function (request, error) {
                console.log('Error saving TV Show:', request, error);
                allI1d.closeModale();
                allI1d.showToast('Error saving TV Show!', 'error');
                // $('#listing-container').html('<p class="error">An error occurred while fetching listings.</p>');
            }
        );
    });
    
	$(document).on('click', '.save-movie', function () {
		console.log('Save Movie button clicked');
		let searchTitle = $('.search_title').val();
		let status = $('.status').val();
		let audioFormat = $('.audio_format').val();
		let itemId = $('.item-id').val();
		console.log('Item ID:', itemId);
		console.log('Search Title:', searchTitle);
		console.log('Status:', status);

		allI1d.requestWPApi(
			allI1d.api.routes.movie,
			{
				movieId: itemId,
				movieStatus: status,
				movieSearchTitle: searchTitle,
				movieAudioFormat: audioFormat
			},
			function (response, data) {
				allI1d.closeModale();
				allI1d.showToast('Movie saved successfully!', 'success');
			},
			'POST',
			function (request, error) {
				console.log('Error saving Movie:', request, error);
				allI1d.closeModale();
				allI1d.showToast('Error saving Movie!', 'error');
			}
		);
	});
});