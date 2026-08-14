<?php
namespace AllI1D;

use AllI1D\Pages\Dashboard;
use AllI1D\Pages\Settings;
use AllI1D\Pages\Status;
use AllI1D\Api;

class Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {

		add_filter( 'login_redirect', [ $this, 'admin_default_page' ] );

		// Ajouter le menu d'administration.
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 20 );

		// Enregistrer les réglages du plugin.
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Enqueue les scripts et styles pour l'administration.
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
	}

	/**
	 * Redirect to admin default page after login.
	 *
	 * @return string
	 */
	public function admin_default_page() {
		return 'wp-admin/admin.php?page=all-in-one-download';
	}

	/**
	 * Register admin menu.
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'All-in-one Download', 'all-in-one-download' ), // Titre de la page.
			__( 'Downloads', 'all-in-one-download' ),           // Titre du menu.
			'alli1d',                                 // Capacité requise.
			'all-in-one-download',                           // Slug du menu.
			[ $this, 'render_dashboard' ],                     // Fonction de rappel pour afficher le contenu.
			'dashicons-download',                            // Icône du menu.
			20                                               // Position dans le menu.
		);
		add_submenu_page(
			'all-in-one-download',
			__( 'All-in-one Download', 'all-in-one-download' ), // Titre de la page.
			__( 'Downloads', 'all-in-one-download' ),           // Titre du menu.
			'alli1d',                                 // Capacité requise.
			'all-in-one-download',                           // Slug du menu.
			[ $this, 'render_dashboard' ],
			0,
		);
		add_submenu_page(
			'all-in-one-download',
			__( 'Paramètres', 'all-in-one-download' ),
			__( 'Paramètres', 'all-in-one-download' ),
			'alli1d',
			'all-in-one-download-settings',
			[ $this, 'render_settings' ],
			10,
		);
        add_submenu_page(
			'all-in-one-download',
			__( 'Statut', 'all-in-one-download' ),
			__( 'Statut', 'all-in-one-download' ),
			'alli1d',
			'all-in-one-download-status',
			[ $this, 'render_status' ],
			10,
		);
	}

	/**
	 * Determine whether the current admin screen belongs to this plugin.
	 *
	 * @return bool
	 */
	private function is_plugin_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && strpos( $screen->id, 'all-in-one-download' ) !== false;
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_enqueue_scripts(): void {
		if ( ! $this->is_plugin_screen() ) {
			return;
		}

		// Enqueue le script JavaScript pour l'administration.
		wp_enqueue_script(
			'allI1d-admin', // Handle du script.
			ALLI1D_URL . 'assets/js/allI1d-admin.js', // URL du script.
			[ 'jquery' ], // Dépendances.
			'1.0.0', // Version.
			true // Charger dans le footer.
		);
		wp_enqueue_script(
			'toast-message-script',
			ALLI1D_URL . 'assets/js/components/toast-message.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'url-input-form-script',
			ALLI1D_URL . 'assets/js/components/url-input-form.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'medias-meter-script',
			ALLI1D_URL . 'assets/js/components/medias-meter.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'crons-manager-script',
			ALLI1D_URL . 'assets/js/components/crons-manager.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
        wp_enqueue_script(
			'logs-manager-script',
			ALLI1D_URL . 'assets/js/components/logs-manager.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'listing-script',
			ALLI1D_URL . 'assets/js/components/listing.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'modale-script',
			ALLI1D_URL . 'assets/js/components/modale.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_enqueue_script(
			'provider-search-modal-script',
			ALLI1D_URL . 'assets/js/components/provider-search-modal.js',
			[ 'jquery', 'modale-script' ],
			'1.0.0',
			true
		);
		// Localiser le script pour passer des données PHP à JavaScript.
		$api = Api::get_instance();
		wp_localize_script(
			'allI1d-admin',
			'allI1d',
			[
				'api'     => $api->get_data(),
				'isAdmin' => current_user_can( 'alli1d_admin' ),
			]
		);
	}

	/**
	 * Enqueue admin styles.
	 */
	public function admin_enqueue_styles(): void {
		if ( ! $this->is_plugin_screen() ) {
			return;
		}

		// Feuille de style Tailwind compilée localement (voir tailwind.config.js
		// et assets/css/tailwind-build.css), en remplacement du script CDN
		// https://cdn.tailwindcss.com anciennement chargé sans SRI.
		wp_enqueue_style(
			'allI1d-tailwind-css',
			ALLI1D_URL . 'assets/css/tailwind-build.css',
			[],
			'1.0.0'
		);

		// Enqueue le CSS pour l'administration.
		wp_enqueue_style(
			'allI1d-admin-css', // Handle du style.
			ALLI1D_URL . 'assets/css/allI1d-admin.css', // URL du style.
			[ 'allI1d-tailwind-css' ],
			'1.0.0' // Version.
		);
		wp_enqueue_style(
			'toast-message-css',
			ALLI1D_URL . 'assets/css/components/toast-message.css',
			[],
			'1.0.0'
		);
		wp_enqueue_style(
			'modale-css',
			ALLI1D_URL . 'assets/css/components/modale.css',
			[],
			'1.0.0'
		);
		wp_enqueue_style(
			'crons-manager-css',
			ALLI1D_URL . 'assets/css/components/crons-manager.css',
			[],
			'1.0.0'
		);
		wp_enqueue_style(
			'logs-manager-css',
			ALLI1D_URL . 'assets/css/components/logs-manager.css',
			[],
			'1.0.0'
		);
		wp_enqueue_style(
			'toolbar-css',
			ALLI1D_URL . 'assets/css/components/toolbar.css',
			[],
			'1.0.0'
		);
		wp_enqueue_style(
			'provider-search-modal-css',
			ALLI1D_URL . 'assets/css/components/provider-search-modal.css',
			[],
			'1.0.0'
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard(): void {
		// Déléguer l'affichage au Dashboard.
		$dashboard = new Dashboard();
		$dashboard->render();
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings(): void {
		$settings = new Settings();
		$settings->register_settings();
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings(): void {
		$settings = new Settings();
		$settings->render();
	}

    public function render_status(): void {
        $status = new Status();
        $status->render();
    }
}
