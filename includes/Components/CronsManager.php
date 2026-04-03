<?php
/**
 * Crons manager component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class CronsManager {
	/**
	 * Render the crons manager HTML.
	 */
	public function render(): void {
		echo '<div style="position: relative;">';
		// Toggle button for banner.
		echo '<button id="toggle-cron-banner" style="margin-top: 20px; top: 10px; left: 10px; background-color: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">';
		echo esc_html__( 'Crons', 'all-in-one-download' );
		echo '</button>';

		// Status banner.
		echo '<div id="cron-status-banner">';
		echo '<div id="cron-status-message" style="font-size: 16px; font-weight: bold;">' . esc_html__( 'Synchronisation des médias', 'all-in-one-download' ) . '</div>';
		echo '<button id="media-sync-cron" class="button button-primary">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		// TV show crons.
		echo '<div id="cron-status-message" style="font-size: 16px; font-weight: bold;">' . esc_html__( 'Telechargement des Series', 'all-in-one-download' ) . '</div>';
		echo '<button id="tv-show-cron" class="button button-primary">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		// Movies crons.
		echo '<div id="cron-status-message" style="font-size: 16px; font-weight: bold;">' . esc_html__( 'Telechargement des Films', 'all-in-one-download' ) . '</div>';
		echo '<button id="movie-cron" class="button button-primary">' . esc_html__( 'Lancer le cron', 'all-in-one-download' ) . '</button>';
		echo '</div>';
		echo '</div>';
	}
}
