<?php
/**
 * Provider search modal component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class ProviderSearchModal {

	/**
	 * Render the persistent toolbar button that opens the guided search modal.
	 */
	public function render_toggle(): void {
		echo '<button type="button" class="alli1d-toolbar-btn" data-modal="provider-search-modal">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
		echo '<span>' . esc_html__( 'Rechercher un film/une série', 'all-in-one-download' ) . '</span>';
		echo '</button>';
	}

	/**
	 * Render the <template> consumed by modale.js's data-modal mechanism.
	 * Only step 1 (criteria form) is server-rendered here; steps 2 and 3
	 * (results, confirmation) are rendered client-side by
	 * assets/js/components/provider-search-modal.js, which replaces the
	 * inner content of #alli1d-search-modal on submit/selection.
	 */
	public function render_modal_template(): void {
		?>
		<template id="provider-search-modal" data-title="<?php esc_attr_e( 'Rechercher un film/une série', 'all-in-one-download' ); ?>">
			<div id="alli1d-search-modal">
				<form id="alli1d-search-criteria-form" class="alli1d-search-form">
					<div class="alli1d-field">
						<label><?php esc_html_e( 'Type de média', 'all-in-one-download' ); ?></label>
						<select id="alli1d-search-type" name="type">
							<option value="movie"><?php esc_html_e( 'Film', 'all-in-one-download' ); ?></option>
							<option value="tvshow" selected><?php esc_html_e( 'Série', 'all-in-one-download' ); ?></option>
						</select>
					</div>
					<div class="alli1d-field">
						<label><?php esc_html_e( 'Titre recherché', 'all-in-one-download' ); ?></label>
						<input type="text" id="alli1d-search-title" name="title" placeholder="<?php esc_attr_e( 'Titre du film ou de la série', 'all-in-one-download' ); ?>" required>
					</div>
					<div id="alli1d-search-tvshow-fields">
						<div class="alli1d-search-row">
							<div class="alli1d-field">
								<label><?php esc_html_e( 'Saison', 'all-in-one-download' ); ?></label>
								<input type="number" id="alli1d-search-saison" name="saison" min="1" value="1">
							</div>
							<div class="alli1d-field">
								<label><?php esc_html_e( 'Épisode', 'all-in-one-download' ); ?></label>
								<input type="number" id="alli1d-search-episode" name="episode" min="0" value="1">
							</div>
						</div>
						<div class="alli1d-field alli1d-search-suivi-field">
							<label>
								<input type="checkbox" id="alli1d-search-suivi" name="suivi" checked>
								<?php esc_html_e( 'Suivre la série (rechercher automatiquement les prochains épisodes)', 'all-in-one-download' ); ?>
							</label>
						</div>
					</div>
					<div class="alli1d-field">
						<label><?php esc_html_e( 'Format audio', 'all-in-one-download' ); ?></label>
						<select id="alli1d-search-audio-format" name="audio_format">
							<option value=""><?php esc_html_e( 'Peu importe', 'all-in-one-download' ); ?></option>
							<option value="VF">VF</option>
							<option value="VOSTFR">VOSTFR</option>
							<option value="MULTI">MULTI</option>
						</select>
					</div>
					<button type="submit" class="alli1d-save-btn"><?php esc_html_e( 'Rechercher', 'all-in-one-download' ); ?></button>
				</form>
			</div>
		</template>
		<?php
	}
}
