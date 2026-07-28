<?php
/**
 * Cover image upload/storage helper.
 *
 * @package AllI1D
 */

namespace AllI1D\Services;

class CoverImageUploader {

	private const ALLOWED_EXTENSIONS = [ 'jpg', 'jpeg', 'png', 'webp' ];

	private const MAX_SIZE_BYTES = 5 * 1024 * 1024;

	private const COVERS_SUBDIR = 'alli1d-covers';

	/**
	 * Validate and store an uploaded cover image for a fiche, overwriting any
	 * previously stored cover image for the same fiche (see delete_if_exists()).
	 *
	 * @param array<string, mixed> $file    A $_FILES-shaped upload array (name, type, tmp_name, error, size).
	 * @param string               $type    Fiche type, 'movie' or 'tvshow'.
	 * @param int                  $item_id The fiche ID.
	 * @return string The public URL of the stored image.
	 * @throws \InvalidArgumentException If the upload is missing, too large, or not an allowed image type.
	 */
	public static function store( array $file, string $type, int $item_id ): string {
		if ( empty( $file['tmp_name'] ) || ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
			throw new \InvalidArgumentException( 'No valid file uploaded.' );
		}

		if ( (int) ( $file['size'] ?? 0 ) > self::MAX_SIZE_BYTES ) {
			throw new \InvalidArgumentException( 'File exceeds the maximum allowed size.' );
		}

		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		$ext      = strtolower( (string) $filetype['ext'] );

		if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) ) {
			throw new \InvalidArgumentException( 'Unsupported image type.' );
		}

		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . self::COVERS_SUBDIR;
		wp_mkdir_p( $target_dir );

		self::delete_if_exists( $type, $item_id );

		$filename    = "{$type}-{$item_id}.{$ext}";
		$target_path = trailingslashit( $target_dir ) . $filename;

		if ( ! copy( $file['tmp_name'], $target_path ) ) {
			throw new \InvalidArgumentException( 'Unable to store the uploaded file.' );
		}

		return trailingslashit( $upload_dir['baseurl'] ) . self::COVERS_SUBDIR . '/' . $filename;
	}

	/**
	 * Best-effort removal of a previously stored cover image for a fiche.
	 * Never throws: a missing or unwritable file is not an error here.
	 *
	 * @param string $type    Fiche type, 'movie' or 'tvshow'.
	 * @param int    $item_id The fiche ID.
	 * @return void
	 */
	public static function delete_if_exists( string $type, int $item_id ): void {
		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . self::COVERS_SUBDIR );

		$matches = glob( $target_dir . $type . '-' . $item_id . '.*' );
		foreach ( $matches ? $matches : [] as $match ) {
			wp_delete_file( $match );
		}
	}
}
