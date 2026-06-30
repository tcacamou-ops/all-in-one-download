<?php
/**
 * Crons manager component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class CronsManager {

	/**
	 * Render the toggle button only (used in the shared toolbar).
	 */
	public function render_toggle(): void {
        if ( ! $this->_can_manage_crons() ) {
            return;
        }
		echo '<button id="toggle-cron-banner" class="alli1d-toolbar-btn" data-active="false">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
		echo '<span>' . esc_html__( 'Crons', 'all-in-one-download' ) . '</span>';
		echo '</button>';
	}

	/**
	 * Render the status banner only.
	 */
	public function render_banner(): void {
        if ( ! $this->_can_manage_crons() ) {
            return;
        }
		echo '<div id="cron-status-banner">';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Synchronisation des médias', 'all-in-one-download' ) . '</span>';
		echo '<button id="media-sync-cron" class="alli1d-banner-btn">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Téléchargement des Séries', 'all-in-one-download' ) . '</span>';
		echo '<button id="tv-show-cron" class="alli1d-banner-btn">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Téléchargement des Films', 'all-in-one-download' ) . '</span>';
		echo '<button id="movie-cron" class="alli1d-banner-btn">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the full component (toggle + banner).
	 */
	public function render(): void {
        if ( ! $this->_can_manage_crons() ) {
            return;
        }
		$this->render_toggle();
		$this->render_banner();
	}

    /**
     * Check if the current user can manage crons.
     * @return bool True if the user can manage crons, false otherwise.
     */
    protected function _can_manage_crons(): bool {
        return current_user_can( 'alli1d_admin' );
    }
}
