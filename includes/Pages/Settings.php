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
				'sanitize_callback' => [ $this, 'sanitize_directory' ],
				'default'           => \AllI1D\Models\Movie::DEFAULT_DIRECTORY,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			'tv_show_directory',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_directory' ],
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
	 * @param mixed $value The raw value.
	 * @return string
	 */
	public function sanitize_directory( $value ): string {
		$value = sanitize_text_field( (string) $value );
		return rtrim( $value, '/' );
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
