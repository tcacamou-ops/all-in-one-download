<?php
namespace AllI1D\Models;

class Media {

	/**
	 * Media ID.
	 *
	 * @var int|null
	 */
	public ?int $id;

	/**
	 * Media URL.
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Whether the media was found.
	 *
	 * @var string
	 */
	public string $found = 'false';

	/**
	 * Media title.
	 *
	 * @var string
	 */
	public string $title = '';

	/**
	 * Media type.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Cover image URL.
	 *
	 * @var string
	 */
	public string $cover_image = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $attributes Attributes to initialize the model.
	 */
	public function __construct( array $attributes = [] ) {
		// Initialiser les propriétés à partir du tableau associatif.
		$this->id  = isset( $attributes['id'] ) ? (int) $attributes['id'] : null;
		$this->url = (string) ( $attributes['url'] ?? '' );
	}
}
