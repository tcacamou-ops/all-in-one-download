<?php
/**
 * Movie item component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Movie;

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

		$html .= '<button class="save-movie alli1d-save-btn">' . esc_html__( 'Sauvegarder', 'all-in-one-download' ) . '</button>';

		$html .= '</div>';
		$html .= '</div>';

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}
}
