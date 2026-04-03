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
		$html = '<div class="gap-1 px-6 flex flex-1 justify-center py-5">
			<input type="hidden" class="item-id" value="' . $this->item->get_id() . '">
			<div class="layout-content-container flex flex-col w-80">
				<div class="flex w-full grow bg-white @container p-4">
				<div class="w-full gap-1 overflow-hidden bg-white @[480px]:gap-2 aspect-[3/2] rounded-xl flex">
					<div class="w-full bg-center bg-no-repeat bg-cover aspect-auto rounded-none flex-1" style="background-image: url(&quot;' . $this->item->get_cover_image() . '&quot;);"></div>
				</div>
				</div>
			</div>
			<div class="layout-content-container flex flex-col max-w-[960px] flex-1">
				<div class="flex flex-wrap justify-between gap-3 p-4"><p class="text-[#141414] tracking-light text-[32px] font-bold leading-tight min-w-72">' . $this->item->get_title() . '</p></div>
				<div class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
				<label class="flex flex-col min-w-40 flex-1">
					<p class="text-[#141414] text-base font-medium leading-normal pb-2">Search Title</p>
					<input class="search_title form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal" value="' . $this->item->get_search_title() . '">
				</label>
				</div>
				<div class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
				<label class="flex flex-col min-w-40 flex-1">
					<p class="text-[#141414] text-base font-medium leading-normal pb-2">Statut</p>
					<select class="status form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 bg-[image:--select-button-svg] placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal">
					<option value="' . Movie::$actif . '" ' . ( $this->item->get_status() === Movie::$actif ? 'selected' : '' ) . '>' . ucfirst( Movie::$actif ) . '</option>
					<option value="' . Movie::$inactif . '" ' . ( $this->item->get_status() === Movie::$inactif ? 'selected' : '' ) . '>' . ucfirst( Movie::$inactif ) . '</option>
					<option value="' . Movie::$downloaded . '" ' . ( $this->item->get_status() === Movie::$downloaded ? 'selected' : '' ) . '>' . ucfirst( Movie::$downloaded ) . '</option>
					</select>
				</label>
				</div>
            <div class="flex px-4 py-3 justify-center">
              <button class="save-movie flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-4 bg-black text-white text-sm font-bold leading-normal tracking-[0.015em]">
                <span class="truncate">Sauvegarder</span>
              </button>
            </div>
          </div>
        </div>';

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}
}
