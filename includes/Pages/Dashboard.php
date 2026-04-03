<?php
namespace AllI1D\Pages;

use AllI1D\Components\UrlInputForm;
use AllI1D\Components\ToastMessage;
use AllI1D\Components\MediasMeter;
use AllI1D\Components\CronsManager;
use AllI1D\Components\ListingContainer;

class Dashboard {
	/**
	 * Render the dashboard page.
	 */
	public function render(): void {
		$toast_message = new ToastMessage();
		$urlinputform  = new UrlInputForm();
		$medias_meter  = new MediasMeter();
		$crons_manager = new CronsManager();
		$crons_manager->render();
		$toast_message->render();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type              = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'tvshow';
		$listing_container = new ListingContainer( $type, $search );
		$listing_container->render();
	}
}
