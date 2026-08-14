jQuery(document).ready(function ($) {

var PROVIDER_LABELS = {
    tr4ker: 'Tr4ker',
    c411: 'C411',
    gemini_tracker: 'Gemini Tracker'
};

function providerLabel(providerId) {
    if (PROVIDER_LABELS[providerId]) {
        return PROVIDER_LABELS[providerId];
    }
    if (!providerId) {
        return 'Provider';
    }
    return providerId.charAt(0).toUpperCase() + providerId.slice(1);
}

// État interne du composant (réinitialisé à chaque ouverture de la modale).
var state = {
    criteria: {
        type: 'tvshow',
        title: '',
        saison: 1,
        episode: 1,
        suivi: true,
        audio_format: '',
        quality: ['any']
    },
    results: { items: [], errors: {} },
    filters: { quality: '', language: '', provider: '' },
    selectedIndex: null
};

function resetState() {
    state = {
        criteria: {
            type: 'tvshow',
            title: '',
            saison: 1,
            episode: 1,
            suivi: true,
            audio_format: '',
            quality: ['1080p', '2160p']
        },
        results: { items: [], errors: {} },
        filters: { quality: '', language: '', provider: '' },
        selectedIndex: null
    };
}

function escapeHtml(value) {
    return $('<span>').text(value == null ? '' : value).html();
}

function renderRoot(innerHtml) {
    var $root = $('#alli1d-search-modal');
    if ($root.length) {
        $root.html(innerHtml);
    }
}

// ---- Step 1: critères ----

function renderStep1() {
    var c = state.criteria;
    var isTvShow = c.type === 'tvshow';
    var html = ''
        + '<form id="alli1d-search-criteria-form" class="alli1d-search-form">'
        +   '<div class="alli1d-field">'
        +     '<label>' + escapeHtml('Type de média') + '</label>'
        +     '<select id="alli1d-search-type" name="type">'
        +       '<option value="movie"' + ( ! isTvShow ? ' selected' : '' ) + '>Film</option>'
        +       '<option value="tvshow"' + ( isTvShow ? ' selected' : '' ) + '>Série</option>'
        +     '</select>'
        +   '</div>'
        +   '<div class="alli1d-field">'
        +     '<label>Titre recherché</label>'
        +     '<input type="text" id="alli1d-search-title" name="title" value="' + escapeHtml(c.title) + '" placeholder="Titre du film ou de la série" required>'
        +   '</div>'
        +   '<div id="alli1d-search-tvshow-fields" style="' + ( isTvShow ? '' : 'display:none;' ) + '">'
        +     '<div class="alli1d-search-row">'
        +       '<div class="alli1d-field">'
        +         '<label>Saison</label>'
        +         '<input type="number" id="alli1d-search-saison" name="saison" min="1" value="' + escapeHtml(c.saison) + '">'
        +       '</div>'
        +       '<div class="alli1d-field">'
        +         '<label>Épisode</label>'
        +         '<input type="number" id="alli1d-search-episode" name="episode" min="0" value="' + escapeHtml(c.episode) + '">'
        +       '</div>'
        +     '</div>'
        +     '<div class="alli1d-field alli1d-search-suivi-field">'
        +       '<label>'
        +         '<input type="checkbox" id="alli1d-search-suivi" name="suivi"' + ( c.suivi ? ' checked' : '' ) + '> '
        +         'Suivre la série (rechercher automatiquement les prochains épisodes)'
        +       '</label>'
        +     '</div>'
        +   '</div>'
        +   '<div class="alli1d-field">'
        +     '<label>Format audio</label>'
        +     '<select id="alli1d-search-audio-format" name="audio_format">'
        +       '<option value="">Peu importe</option>'
        +       '<option value="VF"' + ( c.audio_format === 'VF' ? ' selected' : '' ) + '>VF</option>'
        +       '<option value="VOSTFR"' + ( c.audio_format === 'VOSTFR' ? ' selected' : '' ) + '>VOSTFR</option>'
        +       '<option value="MULTI"' + ( c.audio_format === 'MULTI' ? ' selected' : '' ) + '>MULTI</option>'
        +     '</select>'
        +   '</div>'
        +   '<div class="alli1d-field">'
        +     '<label>Qualité</label>'
        +     '<div class="quality-group">'
        +       '<label><input type="checkbox" class="quality-tier" value="720p"' + ( c.quality.indexOf('720p') !== -1 ? ' checked' : '' ) + ( c.quality.indexOf('any') !== -1 ? ' disabled' : '' ) + '> HD (720p)</label>'
        +       '<label><input type="checkbox" class="quality-tier" value="1080p"' + ( c.quality.indexOf('1080p') !== -1 ? ' checked' : '' ) + ( c.quality.indexOf('any') !== -1 ? ' disabled' : '' ) + '> 1080p</label>'
        +       '<label><input type="checkbox" class="quality-tier" value="2160p"' + ( c.quality.indexOf('2160p') !== -1 ? ' checked' : '' ) + ( c.quality.indexOf('any') !== -1 ? ' disabled' : '' ) + '> 4K (2160p)</label>'
        +       '<label><input type="checkbox" class="quality-any"' + ( c.quality.indexOf('any') !== -1 ? ' checked' : '' ) + '> Toutes</label>'
        +     '</div>'
        +   '</div>'
        +   '<button type="submit" class="alli1d-save-btn">Rechercher</button>'
        + '</form>';
    renderRoot(html);
}

$(document).on('change', '#alli1d-search-type', function () {
    var isTvShow = $(this).val() === 'tvshow';
    $('#alli1d-search-tvshow-fields').toggle(isTvShow);
});

$(document).on('change', '#alli1d-search-criteria-form .quality-any', function () {
    var $tiers = $(this).closest('.quality-group').find('.quality-tier');
    if ($(this).is(':checked')) {
        $tiers.prop('checked', false).prop('disabled', true);
    } else {
        $tiers.prop('disabled', false);
    }
});

function getCriteriaQuality() {
    var isAny = $('#alli1d-search-criteria-form .quality-any').is(':checked');
    var quality = $('#alli1d-search-criteria-form .quality-group input.quality-tier:checked').map(function () {
        return this.value;
    }).get();
    if (isAny || quality.length === 0) {
        return ['any'];
    }
    return quality;
}

$(document).on('submit', '#alli1d-search-criteria-form', function (e) {
    e.preventDefault();

    var type = $('#alli1d-search-type').val();
    state.criteria = {
        type: type,
        title: $('#alli1d-search-title').val(),
        saison: type === 'tvshow' ? parseInt($('#alli1d-search-saison').val(), 10) || 1 : null,
        episode: type === 'tvshow' ? parseInt($('#alli1d-search-episode').val(), 10) || 0 : null,
        suivi: type === 'tvshow' ? $('#alli1d-search-suivi').is(':checked') : false,
        audio_format: $('#alli1d-search-audio-format').val(),
        quality: getCriteriaQuality()
    };

    renderLoading();

    allI1d.requestWPApi(
        allI1d.api.routes.search,
        {
            type: state.criteria.type,
            title: state.criteria.title,
            saison: state.criteria.saison,
            episode: state.criteria.episode,
            audio_format: state.criteria.audio_format
        },
        function (response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            state.results = {
                items: data.items || [],
                errors: data.errors || {}
            };
            state.filters = { quality: '', language: '', provider: '' };
            renderStep2();
        },
        'POST',
        function (request) {
            var message = 'La recherche a échoué.';
            if (request && request.responseJSON && request.responseJSON.message) {
                message = request.responseJSON.message;
            }
            allI1d.showToast(message, 'error');
            renderStep1();
        }
    );
});

function renderLoading() {
    renderRoot('<p class="alli1d-search-loading">Recherche en cours…</p>');
}

// ---- Step 2: résultats ----

function uniqueValues(items, key) {
    var values = [];
    items.forEach(function (item) {
        var value = item[key];
        if (value && values.indexOf(value) === -1) {
            values.push(value);
        }
    });
    return values;
}

function filteredItems() {
    return state.results.items.filter(function (item) {
        if (state.filters.quality && item.quality !== state.filters.quality) {
            return false;
        }
        if (state.filters.language && item.language !== state.filters.language) {
            return false;
        }
        if (state.filters.provider && item.provider !== state.filters.provider) {
            return false;
        }
        return true;
    });
}

function renderErrorBadges() {
    var errors = state.results.errors;
    var providerIds = Object.keys(errors || {});
    if (!providerIds.length) {
        return '';
    }
    var html = '<div class="alli1d-search-errors">';
    providerIds.forEach(function (providerId) {
        html += '<span class="alli1d-search-badge alli1d-search-badge--unavailable" title="' + escapeHtml(errors[providerId]) + '">'
            + escapeHtml(providerLabel(providerId)) + ' indisponible</span>';
    });
    html += '</div>';
    return html;
}

function renderFilterSelect(id, label, options, selected) {
    var html = '<div class="alli1d-search-filter">'
        + '<label>' + escapeHtml(label) + '</label>'
        + '<select id="' + id + '">'
        + '<option value="">Tous</option>';
    options.forEach(function (option) {
        html += '<option value="' + escapeHtml(option) + '"' + ( option === selected ? ' selected' : '' ) + '>' + escapeHtml(option) + '</option>';
    });
    html += '</select></div>';
    return html;
}

function renderResultItem(item, index) {
    var scoreLabel = item.score != null ? item.score : ( item.extra && item.extra.seeders != null ? item.extra.seeders : '' );
    return '<li class="alli1d-search-result-item" data-index="' + index + '">'
        + '<div class="alli1d-search-result-title">' + escapeHtml(item.title) + '</div>'
        + '<div class="alli1d-search-result-meta">'
        +   ( item.quality ? '<span class="alli1d-search-badge alli1d-search-badge--quality">' + escapeHtml(item.quality) + '</span>' : '' )
        +   ( item.language ? '<span class="alli1d-search-badge alli1d-search-badge--language">' + escapeHtml(item.language) + '</span>' : '' )
        +   '<span class="alli1d-search-badge alli1d-search-badge--provider">' + escapeHtml(providerLabel(item.provider)) + '</span>'
        +   ( scoreLabel !== '' ? '<span class="alli1d-search-result-score">' + escapeHtml(scoreLabel) + ' seeders</span>' : '' )
        + '</div>'
        + '</li>';
}

function renderStep2() {
    var items = state.results.items;
    var visibleItems = filteredItems();

    var qualities = uniqueValues(items, 'quality');
    var languages = uniqueValues(items, 'language');
    var providers = uniqueValues(items, 'provider');

    var html = '<div id="alli1d-search-results">'
        + '<button type="button" id="alli1d-search-back-to-criteria" class="alli1d-search-back">&larr; Modifier les critères</button>'
        + renderErrorBadges()
        + '<div class="alli1d-search-filters">'
        +   renderFilterSelect('alli1d-search-filter-quality', 'Qualité', qualities, state.filters.quality)
        +   renderFilterSelect('alli1d-search-filter-language', 'Langue', languages, state.filters.language)
        +   renderFilterSelect('alli1d-search-filter-provider', 'Provider', providers, state.filters.provider)
        + '</div>';

    if (!visibleItems.length) {
        html += '<p class="alli1d-search-empty">Aucun résultat' + ( items.length ? ' pour ces filtres.' : '.' ) + '</p>';
    } else {
        html += '<ul id="alli1d-search-result-list" class="alli1d-search-result-list">';
        visibleItems.forEach(function (item) {
            var originalIndex = items.indexOf(item);
            html += renderResultItem(item, originalIndex);
        });
        html += '</ul>';
    }

    html += '</div>';

    renderRoot(html);
}

$(document).on('click', '#alli1d-search-back-to-criteria', function () {
    renderStep1();
});

$(document).on('change', '#alli1d-search-filter-quality', function () {
    state.filters.quality = $(this).val();
    renderStep2();
});
$(document).on('change', '#alli1d-search-filter-language', function () {
    state.filters.language = $(this).val();
    renderStep2();
});
$(document).on('change', '#alli1d-search-filter-provider', function () {
    state.filters.provider = $(this).val();
    renderStep2();
});

$(document).on('click', '.alli1d-search-result-item', function () {
    state.selectedIndex = parseInt($(this).data('index'), 10);
    renderStep3();
});

// ---- Step 3: confirmation ----

function renderStep3() {
    var item = state.results.items[state.selectedIndex];
    if (!item) {
        renderStep2();
        return;
    }
    var c = state.criteria;
    var isTvShow = c.type === 'tvshow';

    var html = '<div id="alli1d-search-confirm">'
        + '<button type="button" id="alli1d-search-back-to-results" class="alli1d-search-back">&larr; Retour aux résultats</button>'
        + '<form id="alli1d-search-confirm-form" class="alli1d-search-form">'
        +   '<div class="alli1d-field">'
        +     '<label>Titre</label>'
        +     '<input type="text" id="alli1d-confirm-title" value="' + escapeHtml(item.title) + '">'
        +   '</div>';

    if (isTvShow) {
        html += '<div class="alli1d-search-row">'
            +   '<div class="alli1d-field">'
            +     '<label>Saison</label>'
            +     '<input type="number" id="alli1d-confirm-saison" min="1" value="' + escapeHtml(c.saison) + '">'
            +   '</div>'
            +   '<div class="alli1d-field">'
            +     '<label>Épisode</label>'
            +     '<input type="number" id="alli1d-confirm-episode" min="0" value="' + escapeHtml(c.episode) + '">'
            +   '</div>'
            + '</div>'
            + '<div class="alli1d-field alli1d-search-suivi-field">'
            +   '<label>'
            +     '<input type="checkbox" id="alli1d-confirm-suivi"' + ( c.suivi ? ' checked' : '' ) + '> '
            +     'Suivre la série (rechercher automatiquement les prochains épisodes)'
            +   '</label>'
            + '</div>';
    }

    html += '<button type="submit" class="alli1d-save-btn">Confirmer et télécharger</button>'
        + '</form>'
        + '</div>';

    renderRoot(html);
}

$(document).on('click', '#alli1d-search-back-to-results', function () {
    renderStep2();
});

$(document).on('submit', '#alli1d-search-confirm-form', function (e) {
    e.preventDefault();

    var item = state.results.items[state.selectedIndex];
    if (!item) {
        return;
    }
    var c = state.criteria;
    var isTvShow = c.type === 'tvshow';

    var payload = {
        provider: item.provider,
        result: item,
        type: c.type,
        title: $('#alli1d-confirm-title').val(),
        saison: isTvShow ? ( parseInt($('#alli1d-confirm-saison').val(), 10) || 1 ) : 0,
        episode: isTvShow ? ( parseInt($('#alli1d-confirm-episode').val(), 10) || 0 ) : 0,
        suivi: isTvShow ? $('#alli1d-confirm-suivi').is(':checked') : false,
        audio_format: c.audio_format,
        quality: c.quality
    };

    allI1d.requestWPApi(
        allI1d.api.routes.search_select,
        payload,
        function () {
            allI1d.showToast('Média ajouté et téléchargement lancé.', 'success');
            $(allI1d).trigger('allI1d:refreshMedias');
            allI1d.closeModale();
        },
        'POST',
        function (request) {
            var message = 'La sélection a échoué.';
            if (request && request.responseJSON && request.responseJSON.message) {
                message = request.responseJSON.message;
            }
            allI1d.showToast(message, 'error');
        }
    );
});

// L'ouverture de la modale (clic sur [data-modal="provider-search-modal"]) est
// gérée par le mécanisme générique de modale.js (lookup du <template>, injection
// du HTML de step 1). On réinitialise juste l'état interne à cette occasion.
$(document).on('click', '[data-modal="provider-search-modal"]', function () {
    resetState();
});

});
