<?php
namespace AllI1D\Pages;

use AllI1D\Components\UrlInputForm;
use AllI1D\Components\ToastMessage;
use AllI1D\Components\MediasMeter;
use AllI1D\Components\CronsManager;
use AllI1D\Components\ListingContainer;
use AllI1D\Components\LogsManager;
use AllI1D\Components\ProviderSearchModal;

class Dashboard {
	/**
	 * Render the dashboard page.
	 */
	public function render(): void {
		$toast_message         = new ToastMessage();
		$urlinputform          = new UrlInputForm();
		$medias_meter          = new MediasMeter();
		$crons_manager         = new CronsManager();
		$logs_manager          = new LogsManager();
		$provider_search_modal = new ProviderSearchModal();

		// Shared toolbar with both toggle buttons.
		echo '<div class="alli1d-toolbar">';
		$provider_search_modal->render_toggle();
		$crons_manager->render_toggle();
		$logs_manager->render_toggle();
		echo '</div>';

		// Banners (fixed, outside document flow).
		$crons_manager->render_banner();
		$logs_manager->render_banner();

		$provider_search_modal->render_modal_template();

		$toast_message->render();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type              = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'tvshow';
		$listing_container = new ListingContainer( $type, $search );
		$listing_container->render();
	}
}
