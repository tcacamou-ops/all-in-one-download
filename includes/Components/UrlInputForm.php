<?php
namespace AllI1D\Components;

class UrlInputForm {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->enqueue_scripts();
	}

	/**
	 * Render the component.
	 */
	public function render(): void {
		echo '<label for="new_url">' . esc_html__( 'Ajouter une URL', 'all-in-one-download' ) . '</label>';
		echo '<input type="text" id="new_url" name="new_url" placeholder="https://example.com" required />';
		echo '<button type="button" id="submit-url">' . esc_html__( 'Ajouter', 'all-in-one-download' ) . '</button>';
		echo '<div id="url-message" style="margin-top: 10px;"></div>';
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts(): void {
		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_script(
				'url-input-form-script',
				ALLI1D_URL . 'assets/js/components/url-input-form.js',
				[ 'jquery' ],
				'1.0.0',
				true
				);
			}
			);
	}
}
