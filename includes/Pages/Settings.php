<?php
/**
 * Settings page class.
 *
 * @package AllI1D
 */

namespace AllI1D\Pages;

class Settings {

	public const OPTION_GROUP        = 'alli1d_settings';
	public const SECTION_DIRECTORIES = 'alli1d_directories';

	/**
	 * Register plugin settings.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			'movie_directory',
			[
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $this->sanitize_directory( $v, 'movie_directory' ),
				'default'           => \AllI1D\Models\Movie::DEFAULT_DIRECTORY,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			'tv_show_directory',
			[
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $this->sanitize_directory( $v, 'tv_show_directory' ),
				'default'           => \AllI1D\Models\TvShow::DEFAULT_DIRECTORY,
			]
		);

		add_settings_section(
			self::SECTION_DIRECTORIES,
			__( 'Répertoires de téléchargement', 'all-in-one-download' ),
			[ $this, 'render_section_directories' ],
			self::OPTION_GROUP
		);

		add_settings_field(
			'movie_directory',
			__( 'Répertoire des films', 'all-in-one-download' ),
			[ $this, 'render_field_movie_directory' ],
			self::OPTION_GROUP,
			self::SECTION_DIRECTORIES
		);

		add_settings_field(
			'tv_show_directory',
			__( 'Répertoire des séries', 'all-in-one-download' ),
			[ $this, 'render_field_tv_show_directory' ],
			self::OPTION_GROUP,
			self::SECTION_DIRECTORIES
		);
	}

	/**
	 * Sanitize a directory path value.
	 *
	 * Resolves symlinks and .. segments via realpath(), then blocks writes to
	 * system directories. Returns the previous saved value on any validation
	 * failure so a rejected submission never overwrites a working configuration.
	 *
	 * @param mixed  $value  The raw submitted value.
	 * @param string $option The option name being updated (e.g. 'movie_directory').
	 * @return string Canonical absolute path, or the previous value on error.
	 */
	private function sanitize_directory( $value, string $option ): string {
		$value = sanitize_text_field( (string) $value );
		$value = (string) preg_replace( '/^\s+|\s+$/u', '', $value ); // /u flag strips Unicode whitespace (U+00A0 etc.) from copy-paste.
		$value = rtrim( $value, '/' );

		$registered = get_registered_settings();
		$default    = $registered[ $option ]['default'] ?? '';
		$previous   = (string) get_option( $option, $default );
		if ( '' === $previous ) {
			$previous = $default;
		}

		if ( '' === $value ) {
			return $previous;
		}

		// Reject relative paths — they have no defined root in this context.
		if ( '/' !== substr( $value, 0, 1 ) ) {
			add_settings_error(
				$option,
				'not_absolute',
				__( 'Le répertoire doit être un chemin absolu.', 'all-in-one-download' )
			);
			return $previous;
		}

		// realpath() resolves symlinks and collapses .. segments.
		$real = realpath( $value );

		if ( false === $real ) {
			// Directory does not exist yet: resolve parent to guard against traversal
			// before the directory is created (e.g. /downloads/../../../etc/new).
			$parent = realpath( dirname( $value ) );
			if ( false === $parent ) {
				add_settings_error(
					$option,
					'invalid_path',
					__( 'Le répertoire parent n\'existe pas.', 'all-in-one-download' )
				);
				return $previous;
			}
			$real = $parent . '/' . basename( $value );
		}

		// Block writes to system and WordPress core paths.
		foreach ( $this->get_blocked_path_prefixes() as $blocked ) {
			if ( $real === $blocked || 0 === strpos( $real, $blocked . '/' ) ) {
				add_settings_error(
					$option,
					'path_not_allowed',
					__( 'Ce répertoire n\'est pas autorisé.', 'all-in-one-download' )
				);
				return $previous;
			}
		}

		return $real;
	}

	/**
	 * List of filesystem roots that must never be used as download directories.
	 *
	 * @return string[]
	 */
	private function get_blocked_path_prefixes(): array {
		return [
			'/bin',
			'/boot',
			'/dev',
			'/etc',
			'/home',
			'/lib',
			'/lib64',
			'/proc',
			'/root',
			'/run',
			'/sbin',
			'/sys',
			'/tmp',
			'/usr',
			'/var',
		];
	}

	/**
	 * Render the directories section description.
	 */
	public function render_section_directories(): void {
		echo '<p>' . esc_html__( 'Définissez les chemins absolus des répertoires de téléchargement sur le serveur.', 'all-in-one-download' ) . '</p>';
	}

	/**
	 * Render the movie_directory field.
	 */
	public function render_field_movie_directory(): void {
		$value = get_option( 'movie_directory', \AllI1D\Models\Movie::DEFAULT_DIRECTORY );
		?>
		<input
			type="text"
			id="movie_directory"
			name="movie_directory"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description"><?php esc_html_e( 'Chemin absolu du répertoire de destination pour les films.', 'all-in-one-download' ); ?></p>
		<?php
	}

	/**
	 * Render the tv_show_directory field.
	 */
	public function render_field_tv_show_directory(): void {
		$value = get_option( 'tv_show_directory', \AllI1D\Models\TvShow::DEFAULT_DIRECTORY );
		?>
		<input
			type="text"
			id="tv_show_directory"
			name="tv_show_directory"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description"><?php esc_html_e( 'Chemin absolu du répertoire de destination pour les séries TV.', 'all-in-one-download' ); ?></p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'alli1d' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'all-in-one-download' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Paramètres – All-in-one Download', 'all-in-one-download' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::OPTION_GROUP );
				submit_button( __( 'Enregistrer les paramètres', 'all-in-one-download' ) );
				?>
			</form>
		</div>
		<?php
	}
}
