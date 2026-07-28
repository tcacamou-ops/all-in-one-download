<?php
/**
 * TvShow item component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\TvShow;

class TvShowItem {
	/**
	 * The TvShow object.
	 *
	 * @var TvShow|null
	 */
	private ?TvShow $item;

	/**
	 * Constructor.
	 *
	 * @param int $item_id The TvShow ID.
	 */
	public function __construct( $item_id ) {
		$this->item = TvShowRepository::get_instance()->get_tv_show_by_id( (int) $item_id );
	}

	/**
	 * Render the TvShow item HTML.
	 *
	 * @param bool $render Whether to echo or return.
	 * @return string|void
	 */
	public function render( $render = true ) {
		if ( null === $this->item ) {
			return '';
		}

		$html  = '<div class="alli1d-item-layout">';
		$html .= '<input type="hidden" class="item-id" value="' . esc_attr( $this->item->get_id() ) . '">';

		$html .= '<div class="alli1d-item-poster">';
		$html .= '<div class="poster-img" style="background-image: url(\'' . esc_url( $this->item->get_cover_image() ) . '\');"></div>';
		$html .= '<label class="alli1d-cover-image-upload-label">';
		$html .= esc_html__( 'Changer l\'image', 'all-in-one-download' );
		$html .= '<input type="file" class="cover-image-input" accept="image/png,image/jpeg,image/webp" data-item-id="' . esc_attr( $this->item->get_id() ) . '" data-item-type="tvshow" hidden>';
		$html .= '</label>';
		$html .= '</div>';

		$html .= '<div class="alli1d-item-form">';
		$html .= '<p class="alli1d-item-title">' . esc_html( $this->item->get_title() ) . '</p>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Search Title', 'all-in-one-download' ) . '</label>';
		$html .= '<input class="search_title" type="text" value="' . esc_attr( $this->item->get_search_title() ) . '">';
		$html .= '</div>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Status', 'all-in-one-download' ) . '</label>';
		$html .= '<select class="status">';
		$html .= '<option value="actif"' . selected( $this->item->get_status(), 'actif', false ) . '>' . esc_html__( 'Actif', 'all-in-one-download' ) . '</option>';
		$html .= '<option value="inactif"' . selected( $this->item->get_status(), 'inactif', false ) . '>' . esc_html__( 'Inactif', 'all-in-one-download' ) . '</option>';
		$html .= '</select>';
		$html .= '</div>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Langue', 'all-in-one-download' ) . '</label>';
		$html .= '<select class="audio_format">';
		$html .= '<option value="VF"' . selected( $this->item->get_audio_format(), 'VF', false ) . '>' . esc_html__( 'VF', 'all-in-one-download' ) . '</option>';
		$html .= '<option value="VOSTFR"' . selected( $this->item->get_audio_format(), 'VOSTFR', false ) . '>' . esc_html__( 'VOSTFR', 'all-in-one-download' ) . '</option>';
		$html .= '</select>';
		$html .= '</div>';

		$html .= '<div class="alli1d-seasons-header">';
		$html .= '<h2>' . esc_html__( 'Saisons', 'all-in-one-download' ) . '</h2>';
		$html .= '<button id="add-season" class="alli1d-banner-btn">' . esc_html__( 'Ajouter une saison', 'all-in-one-download' ) . '</button>';
		$html .= '</div>';

		$html .= '<div id="seasons-container">';
		foreach ( $this->item->get_saisons() as $saison ) {
			$html .= '<div id="saison-' . esc_attr( $saison['id'] ) . '" class="alli1d-season-row">';

			$html .= '<div class="alli1d-field">';
			$html .= '<label>' . esc_html__( 'Saison', 'all-in-one-download' ) . ' ' . esc_html( $saison['id'] ) . '</label>';
			$html .= '<input data-saison-id="' . esc_attr( $saison['id'] ) . '" class="last-ep" type="text" placeholder="' . esc_attr__( 'Dernier épisode dl', 'all-in-one-download' ) . '" value="' . esc_attr( $saison['lastepisode'] ) . '">';
			$html .= '</div>';

			$html .= '<div class="alli1d-field">';
			$html .= '<label>' . esc_html__( 'Statut', 'all-in-one-download' ) . '</label>';
			$html .= '<select id="saison-' . esc_attr( $saison['id'] ) . '-isactiv">';
			$html .= '<option value="actif"' . selected( $saison['status'], 'actif', false ) . '>' . esc_html__( 'Actif', 'all-in-one-download' ) . '</option>';
			$html .= '<option value="inactif"' . selected( $saison['status'], 'inactif', false ) . '>' . esc_html__( 'Inactif', 'all-in-one-download' ) . '</option>';
			$html .= '</select>';
			$html .= '</div>';

			$html .= '<button data-saison-id="' . esc_attr( $saison['id'] ) . '" class="delete-season-btn alli1d-season-delete" title="' . esc_attr__( 'Supprimer la saison', 'all-in-one-download' ) . '">';
			$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3a3 3 0 0 1 6 0h5a1 1 0 1 1 0 2h-1.07l-.86 13.77A3 3 0 0 1 15.08 21H8.92a3 3 0 0 1-2.99-2.23L5.07 5H4a1 1 0 1 1 0-2h5Zm1 0a1 1 0 0 1 2 0h-2Zm7.07 2H6.93l.85 13.6a1 1 0 0 0 .99.8h6.16a1 1 0 0 0 .99-.8L17.07 5ZM9 9a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm3 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>';
			$html .= '</button>';

			$html .= '</div>';
		}
		$html .= '</div>';

		$html .= '<div class="alli1d-item-actions">';
		$html .= '<button class="save-tv-show alli1d-save-btn">' . esc_html__( 'Sauvegarder', 'all-in-one-download' ) . '</button>';
		$html .= '<button class="delete-fiche-btn alli1d-delete-btn" data-item-id="' . esc_attr( $this->item->get_id() ) . '" data-item-type="tvshow" title="' . esc_attr__( 'Supprimer cette fiche', 'all-in-one-download' ) . '">';
		$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3a3 3 0 0 1 6 0h5a1 1 0 1 1 0 2h-1.07l-.86 13.77A3 3 0 0 1 15.08 21H8.92a3 3 0 0 1-2.99-2.23L5.07 5H4a1 1 0 1 1 0-2h5Zm1 0a1 1 0 0 1 2 0h-2Zm7.07 2H6.93l.85 13.6a1 1 0 0 0 .99.8h6.16a1 1 0 0 0 .99-.8L17.07 5ZM9 9a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm3 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>';
		$html .= '</button>';
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}
}
