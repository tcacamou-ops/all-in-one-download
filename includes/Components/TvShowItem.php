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
		$html = '<div class="gap-1 px-6 flex flex-1 justify-center py-5">
          <input type="hidden" class="item-id" value="' . $this->item->get_id() . '">
          <div class="layout-content-container flex flex-col w-80">
            <div class="flex w-full grow @container p-4">
              <div class="w-full gap-1 overflow-hidden bg-white @[480px]:gap-2 aspect-[2/3] rounded-xl flex">
                <div class="w-full bg-center bg-no-repeat bg-cover aspect-auto rounded-none flex-1" style="background-image: url(&quot;' . $this->item->get_cover_image() . '&quot;);">caca</div>
              </div>
            </div>
          </div>
          <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
            <div class="flex flex-wrap justify-between gap-3 p-4">
              <p class="text-[#141414] tracking-light text-[32px] font-bold leading-tight min-w-72">' . $this->item->get_title() . '</p>
            </div>
            <div class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
              <label class="flex flex-col min-w-40 flex-1">
                <p class="text-[#141414] text-base font-medium leading-normal pb-2">Search Title</p>
                <input class="search_title form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal" value="' . $this->item->get_search_title() . '">
              </label>
            </div>
            <div class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
              <label class="flex flex-col min-w-40 flex-1">
                <p class="text-[#141414] text-base font-medium leading-normal pb-2">Status</p>
                <select class="status form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 bg-[image:--select-button-svg] placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal">
                  <option value="actif" ' . ( $this->item->get_status() === 'actif' ? 'selected' : '' ) . '>Actif</option>
                  <option value="inactif" ' . ( $this->item->get_status() === 'inactif' ? 'selected' : '' ) . '>Inactif</option>
                </select>
              </label>
            </div>
            <h2 class="text-[#141414] text-[22px] font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-5">Seasons &nbsp;&nbsp; <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-4 bg-[#f2f2f2] text-[#141414] text-sm font-bold leading-normal tracking-[0.015em]" style="display:inline">
                <button id="add-season" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-4 bg-black text-white text-sm font-bold leading-normal tracking-[0.015em]" style="display:inline">
                <span class="truncate">Add Season</span>
              </button></h2>
            <div class="flex px-4 py-3 justify-start">
            </div>
            <div id="seasons-container">';

		foreach ( $this->item->get_saisons() as $saison ) {
			$html .= '<div id="saison-' . $saison['id'] . '" class="flex max-w-[480px] flex-wrap items-end gap-4 px-4 py-3">
                <label class="flex flex-col min-w-30 flex-1">
                    <p class="text-[#141414] text-base font-medium leading-normal pb-2">Season ' . $saison['id'] . '</p>
                    <input data-saison-id="' . $saison['id'] . '" class="last-ep form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal" placeholder="Dernier épisode dl" value="' . $saison['lastepisode'] . '">
                </label>
                <label class="flex flex-col min-w-40 flex-1">
                    <select id="saison-' . $saison['id'] . '-isactiv" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border border-[#e0e0e0] bg-white focus:border-[#e0e0e0] h-14 bg-[image:--select-button-svg] placeholder:text-[#757575] p-[15px] text-base font-normal leading-normal">
                        <option value="actif" ' . ( 'actif' === $saison['status'] ? 'selected' : '' ) . '>Actif</option>
                        <option value="inactif" ' . ( 'inactif' === $saison['status'] ? 'selected' : '' ) . '>Inactif</option>
                    </select>
                </label>
                <label class="flex flex-col min-w-40 flex-1">
                  <button data-saison-id="' . $saison['id'] . '" class="delete-season-btn ml-2 text-[#757575] hover:text-red-600 p-[15px]" title="Supprimer la saison" data-saison-id="' . $saison['id'] . '" style="background:transparent;border:none;cursor:pointer;align-items:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3a3 3 0 0 1 6 0h5a1 1 0 1 1 0 2h-1.07l-.86 13.77A3 3 0 0 1 15.08 21H8.92a3 3 0 0 1-2.99-2.23L5.07 5H4a1 1 0 1 1 0-2h5Zm1 0a1 1 0 0 1 2 0h-2Zm7.07 2H6.93l.85 13.6a1 1 0 0 0 .99.8h6.16a1 1 0 0 0 .99-.8L17.07 5ZM9 9a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm3 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>
                  </button>
                </label>
                </div>';
		}

			$html .= '</div><div class="flex px-4 py-3 justify-center">
              <button class="save-tv-show flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-4 bg-black text-white text-sm font-bold leading-normal tracking-[0.015em]">
                <span class="truncate">Save</span>
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
