<?php
namespace AllI1D\Components;

use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\Repositories\MovieRepository;

class Listing {
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
	 * Listing items.
	 *
	 * @var array<int, mixed>
	 */
	private array $items = [];

	/**
	 * Constructor.
	 *
	 * @param string $type   The listing type.
	 * @param string $search The search query.
	 */
	public function __construct( $type = 'tvshow', $search = '' ) {
		$this->type   = $type;
		$this->search = $search;
		$search       = [];
		if ( ! empty( $this->search ) ) {
			$search = [ 'search_title' => [ 'LIKE', $this->search ] ];
		}
		if ( 'tvshow' === $this->type ) {
			$this->items = TvShowRepository::get_instance()->get_all_tv_shows( $search );

		} elseif ( 'movie' === $this->type ) {
			$this->items = MovieRepository::get_instance()->get_all_movies( $search );
		}
	}

	/**
	 * Render the component.
	 *
	 * @param bool $render Whether to echo or return.
	 * @return string|void
	 */
	public function render( $render = true ) {
		$html = '<div class="grid grid-cols-[repeat(auto-fit,minmax(158px,1fr))] gap-3 p-4">';
		foreach ( $this->items as $item ) {
			$item_component = new Item( $item );
			$html          .= $item_component->render( false );
		}
		$html .= '</div>';
		if ( ! $render ) {
			return $html;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all item values are individually escaped in Item::render()
		echo $html;
	}
}
