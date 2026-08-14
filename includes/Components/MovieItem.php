<?php
/**
 * Movie item component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Movie;
use AllI1D\Services\TorrentMetadataParser;

class MovieItem {
	/**
	 * The movie object.
	 *
	 * @var Movie|null
	 */
	private ?Movie $item;

	/**
	 * Constructor.
	 *
	 * @param int $item_id The movie ID.
	 */
	public function __construct( $item_id ) {
		$this->item = MovieRepository::get_instance()->get_by_id( (int) $item_id );
	}

	/**
	 * Render the movie item HTML.
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
		$html .= '<input type="file" class="cover-image-input" accept="image/png,image/jpeg,image/webp" data-item-id="' . esc_attr( $this->item->get_id() ) . '" data-item-type="movie" hidden>';
		$html .= '</label>';
		$html .= '</div>';

		$html .= '<div class="alli1d-item-form">';
		$html .= '<p class="alli1d-item-title">' . esc_html( $this->item->get_title() ) . '</p>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Search Title', 'all-in-one-download' ) . '</label>';
		$html .= '<input class="search_title" type="text" value="' . esc_attr( $this->item->get_search_title() ) . '">';
		$html .= '</div>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Statut', 'all-in-one-download' ) . '</label>';
		$html .= '<select class="status">';
		$html .= '<option value="' . esc_attr( Movie::$actif ) . '"' . selected( $this->item->get_status(), Movie::$actif, false ) . '>' . esc_html( ucfirst( Movie::$actif ) ) . '</option>';
		$html .= '<option value="' . esc_attr( Movie::$inactif ) . '"' . selected( $this->item->get_status(), Movie::$inactif, false ) . '>' . esc_html( ucfirst( Movie::$inactif ) ) . '</option>';
		$html .= '<option value="' . esc_attr( Movie::$downloaded ) . '"' . selected( $this->item->get_status(), Movie::$downloaded, false ) . '>' . esc_html( ucfirst( Movie::$downloaded ) ) . '</option>';
		$html .= '</select>';
		$html .= '</div>';

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Langue', 'all-in-one-download' ) . '</label>';
		$html .= '<select class="audio_format">';
		$html .= '<option value="VF"' . selected( $this->item->get_audio_format(), 'VF', false ) . '>' . esc_html__( 'VF', 'all-in-one-download' ) . '</option>';
		$html .= '<option value="VOSTFR"' . selected( $this->item->get_audio_format(), 'VOSTFR', false ) . '>' . esc_html__( 'VOSTFR', 'all-in-one-download' ) . '</option>';
		$html .= '</select>';
		$html .= '</div>';

		$quality        = $this->item->get_quality();
		$is_any_quality = ( 'any' === $quality || '' === $quality );
		$selected_tiers = $is_any_quality ? [] : explode( ',', $quality );
		$quality_labels = [
			'720p'  => __( 'HD (720p)', 'all-in-one-download' ),
			'1080p' => __( '1080p', 'all-in-one-download' ),
			'2160p' => __( '4K (2160p)', 'all-in-one-download' ),
		];

		$html .= '<div class="alli1d-field">';
		$html .= '<label>' . esc_html__( 'Qualité', 'all-in-one-download' ) . '</label>';
		$html .= '<div class="quality-group">';
		foreach ( TorrentMetadataParser::SELECTABLE_QUALITIES as $tier ) {
			$checked  = in_array( $tier, $selected_tiers, true ) ? ' checked' : '';
			$disabled = $is_any_quality ? ' disabled' : '';
			$html    .= '<label><input type="checkbox" class="quality-tier" value="' . esc_attr( $tier ) . '"' . $checked . $disabled . '> ' . esc_html( $quality_labels[ $tier ] ) . '</label>';
		}
		$html .= '<label><input type="checkbox" class="quality-any"' . ( $is_any_quality ? ' checked' : '' ) . '> ' . esc_html__( 'Toutes', 'all-in-one-download' ) . '</label>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="alli1d-item-actions">';
		$html .= '<button class="save-movie alli1d-save-btn">' . esc_html__( 'Sauvegarder', 'all-in-one-download' ) . '</button>';
		if ( current_user_can( 'alli1d_admin' ) ) {
			$html .= '<button class="delete-fiche-btn alli1d-delete-btn" data-item-id="' . esc_attr( $this->item->get_id() ) . '" data-item-type="movie" title="' . esc_attr__( 'Supprimer cette fiche', 'all-in-one-download' ) . '">';
			$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3a3 3 0 0 1 6 0h5a1 1 0 1 1 0 2h-1.07l-.86 13.77A3 3 0 0 1 15.08 21H8.92a3 3 0 0 1-2.99-2.23L5.07 5H4a1 1 0 1 1 0-2h5Zm1 0a1 1 0 0 1 2 0h-2Zm7.07 2H6.93l.85 13.6a1 1 0 0 0 .99.8h6.16a1 1 0 0 0 .99-.8L17.07 5ZM9 9a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm3 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>';
			$html .= '</button>';
		}
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
