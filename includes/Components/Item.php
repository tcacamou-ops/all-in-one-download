<?php
/**
 * Generic item component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class Item {
	/**
	 * The item object.
	 *
	 * @var object
	 */
	private object $item;

	/**
	 * Constructor.
	 *
	 * @param object $item The item object.
	 */
	public function __construct( $item ) {
		$this->item = $item;
	}

	/**
	 * Render the item HTML.
	 *
	 * @param bool $render Whether to echo or return.
	 * @return string|void
	 */
	public function render( $render = true ) {
		$id    = (int) $this->item->get_id();
		$type  = esc_attr( $this->item->get_type() );
		$cover = esc_url( $this->item->get_cover_image() );
		$title = esc_html( $this->item->get_title() );

		$html  = '<div class="flex flex-col gap-3 pb-3 listing-item" data-id="' . $id . '" data-type="' . $type . '">';
		$html .= '<div class="w-full bg-center bg-no-repeat aspect-[3/4] bg-cover rounded-xl" style="max-width: 176px; background-image: url(' . $cover . ');" ></div>';
		$html .= '<p class="text-[#141414] text-base font-medium leading-normal">' . $title . '</p>';
		$html .= '</div>';

		if ( $render ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all values are individually escaped above
			echo $html;
		} else {
			return $html;
		}
	}
}
