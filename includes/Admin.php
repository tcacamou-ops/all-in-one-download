<?php
namespace AllI1D;

use AllI1D\Pages\Dashboard;
use AllI1D\Api;

class Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {

		add_filter( 'login_redirect', [ $this, 'admin_default_page' ] );

		// Ajouter le menu d'administration.
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 20 );

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
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_enqueue_scripts(): void {
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
		// Localiser le script pour passer des données PHP à JavaScript.
		$api = Api::get_instance();
		wp_localize_script(
			'allI1d-admin',
			'allI1d',
			[ 'api' => $api->get_data() ]
		);
		wp_enqueue_script(
			'listing-container-script',
			'https://cdn.tailwindcss.com?plugins=forms,container-queries',
			[ 'jquery' ],
			'1.0.0',
			true
		);
	}

	/**
	 * Enqueue admin styles.
	 */
	public function admin_enqueue_styles(): void {
		// Enqueue le CSS pour l'administration.
		wp_enqueue_style(
			'allI1d-admin-css', // Handle du style.
			ALLI1D_URL . 'assets/css/allI1d-admin.css', // URL du style.
			[],
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
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard(): void {
		// Déléguer l'affichage au Dashboard.
		$dashboard = new Dashboard();
		$dashboard->render();
	}
}
