<?php
/**
 * Logs manager component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class LogsManager {

	/**
	 * Render the toggle button only (used in the shared toolbar).
	 */
	public function render_toggle(): void {
        if ( ! $this->_can_manage_logs() ) {
            return;
        }
		echo '<button id="toggle-logs-banner" class="alli1d-toolbar-btn" data-active="false">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>';
		echo '<span>' . esc_html__( 'Logs', 'all-in-one-download' ) . '</span>';
		echo '</button>';
	}

	/**
	 * Render the status banner only.
	 */
	public function render_banner(): void {
        if ( ! $this->_can_manage_logs() ) {
            return;
        }
		echo '<div id="logs-status-banner">';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Synchronisation des médias', 'all-in-one-download' ) . '</span>';
		echo '<button id="media-sync-logs" class="alli1d-banner-btn">' . esc_html__( 'Checker les logs', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Téléchargement des Séries', 'all-in-one-download' ) . '</span>';
		echo '<button id="tv-show-logs" class="alli1d-banner-btn">' . esc_html__( 'Checker les logs', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '<div class="banner-item">';
		echo '<span class="banner-label">' . esc_html__( 'Téléchargement des Films', 'all-in-one-download' ) . '</span>';
		echo '<button id="movie-logs" class="alli1d-banner-btn">' . esc_html__( 'Checker les logs', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the full component (toggle + banner).
	 */
	public function render(): void {
        if ( ! $this->_can_manage_logs() ) {
            return;
        }
		$this->render_toggle();
		$this->render_banner();
	}

    /**
     * Check if the current user can manage logs.
     * @return bool True if the user can manage logs, false otherwise.
     */
    protected function _can_manage_logs(): bool {
        return current_user_can( 'alli1d_admin' );
    }
}
