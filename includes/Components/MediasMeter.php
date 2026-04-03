<?php
namespace AllI1D\Components;

use AllI1D\Models\Repositories\MediaRepository;

class MediasMeter {
	/**
	 * Render the component.
	 */
	public function render(): void {
		// Récupérer le nombre de médias dans la base de données.
		$repository = MediaRepository::get_instance();

		// Afficher le cercle avec le nombre de médias.
		echo '<div id="medias-meter" style="display: flex; justify-content: center; align-items: center; width: 100px; height: 100px; border-radius: 50%; background-color: #4caf50; color: white; font-size: 20px; font-weight: bold;">';
		echo count( $repository->get_all_urls() );
		echo '</div>';
	}
}
