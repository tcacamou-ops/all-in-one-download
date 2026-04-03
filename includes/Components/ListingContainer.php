<?php
namespace AllI1D\Components;

class ListingContainer {
	/**
	 * Listing type.
	 *
	 * @var string
	 */
	private string $type = 'tvshow';
	/**
	 * Search query.
	 *
	 * @var string
	 */
	private string $search = '';
	/**
	 * TV checked state.
	 *
	 * @var string
	 */
	private string $tv_checked = 'checked';
	/**
	 * Movie checked state.
	 *
	 * @var string
	 */
	private string $movie_checked = '';

	/**
	 * Constructor.
	 *
	 * @param string $type   The listing type.
	 * @param string $search The search query.
	 */
	public function __construct( $type = 'tvshow', $search = '' ) {
		$this->type   = $type;
		$this->search = $search;
		if ( 'movie' === $this->type ) {
			$this->tv_checked    = '';
			$this->movie_checked = 'checked';
		} elseif ( 'tvshow' === $this->type ) {
			$this->tv_checked    = 'checked';
			$this->movie_checked = '';
		}
	}
	/**
	 * Render the component.
	 *
	 * @param bool $render Whether to echo or return.
	 * @return string|void
	 */
	public function render( $render = true ) {
		$listing = new Listing( $this->type, $this->search );
		$html    = '<div class="px-40 flex flex-1 justify-center py-5">
          <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
            <div class="px-4 py-3">
              <label class="flex flex-col min-w-40 h-12 w-full">
                <div class="flex w-full flex-1 items-stretch rounded-xl h-full">
                  <div class="text-[#757575] flex border-none bg-[#f2f2f2] items-center justify-center pl-4 rounded-l-xl border-r-0" data-icon="MagnifyingGlass" data-size="24px" data-weight="regular">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                      <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                    </svg>
                  </div>
                  <input placeholder="Rechercher des médias" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#141414] focus:outline-0 focus:ring-0 border-none bg-[#f2f2f2] focus:border-none h-full placeholder:text-[#757575] px-4 rounded-l-none border-l-0 pl-2 text-base font-normal leading-normal" value="' . $this->search . '" id="search-input">
                </div>
              </label>
            </div>
            <div class="flex px-4 py-3">
              <div class="flex h-10 flex-1 items-center justify-center rounded-xl bg-[#f2f2f2] p-1">
                <label class="flex cursor-pointer h-full grow items-center justify-center overflow-hidden rounded-xl px-2 has-[:checked]:bg-white has-[:checked]:shadow-[0_0_4px_rgba(0,0,0,0.1)] has-[:checked]:text-[#141414] text-[#757575] text-sm font-medium leading-normal filter-listing-type">
                  <span class="truncate">Films</span>
                  <input type="radio" name="7554a655-bfdd-4d23-ae36-4c8b0b5b2280" class="invisible w-0" value="movie" ' . $this->movie_checked . '>
                </label>
                <label class="flex cursor-pointer h-full grow items-center justify-center overflow-hidden rounded-xl px-2 has-[:checked]:bg-white has-[:checked]:shadow-[0_0_4px_rgba(0,0,0,0.1)] has-[:checked]:text-[#141414] text-[#757575] text-sm font-medium leading-normal filter-listing-type">
                  <span class="truncate">Séries TV</span>
                  <input type="radio" name="7554a655-bfdd-4d23-ae36-4c8b0b5b2280" class="invisible w-0" value="tvshow" ' . $this->tv_checked . '>
                </label>
              </div>
            </div>';
		$html   .= '<div id="listing-container">';
		$html   .= $listing->render( false );
		$html   .= '</div>';
		$html   .= '</div>
        </div>';
		if ( ! $render ) {
			return $html;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all item values are individually escaped in Listing::render()
		echo $html;
	}
}
