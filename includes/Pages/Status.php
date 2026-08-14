<?php
/**
 * Status page class.
 *
 * @package AllI1D
 */

namespace AllI1D\Pages;

use AllI1D\Components\ToastMessage;

class Status {
	/**
	 * Render the status page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'alli1d' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'all-in-one-download' ) );
		}

		$status = apply_filters( 'alli1d_process_status', [] );
		$modals = apply_filters( 'alli1d_provider_settings_modals', [] );
		( new ToastMessage() )->render();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Statut – All-in-one Download', 'all-in-one-download' ); ?></h1>

			<div class="alli1d-status-actions" style="margin: 12px 0;">
				<button type="button" id="reset-indexing-button" class="button button-secondary">
					<?php esc_html_e( 'Réinitialiser l\'indexation', 'all-in-one-download' ); ?>
				</button>
			</div>

			<style>
				.alli1d-status-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
					gap: 16px;
					margin-top: 20px;
				}

				.alli1d-status-card {
					background: #fff;
					border: 1px solid #dcdcde;
					border-radius: 10px;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
					overflow: hidden;
				}

				.alli1d-status-card__head {
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 12px 14px;
					background: #f6f7f7;
					border-bottom: 1px solid #dcdcde;
					gap: 8px;
				}

				.alli1d-status-card__actions {
					display: flex;
					gap: 8px;
					align-items: center;
				}

				.alli1d-status-card__settings-link {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					width: 28px;
					height: 28px;
					border-radius: 4px;
					background: transparent;
					border: 1px solid #dcdcde;
					color: #3c434a;
					cursor: pointer;
					text-decoration: none;
					transition: all 0.2s ease;
				}

				.alli1d-status-card__settings-link:hover {
					background: #e7e7e7;
					color: #1d2327;
					border-color: #8c8f94;
				}

				.alli1d-status-card__settings-link:focus {
					outline: 2px solid #2271b1;
					outline-offset: 1px;
				}

				.alli1d-status-card__settings-link .dashicons {
					font-size: 16px;
					width: 16px;
					height: 16px;
				}

				.alli1d-status-card__title {
					margin: 0;
					font-size: 14px;
					line-height: 1.4;
					text-transform: capitalize;
				}

				.alli1d-status-card__body {
					padding: 14px;
				}

				.alli1d-status-list {
					margin: 0;
				}

				.alli1d-status-list__row {
					display: flex;
					justify-content: space-between;
					gap: 12px;
					padding: 7px 0;
					border-bottom: 1px dashed #e2e4e7;
				}

				.alli1d-status-list__row:last-child {
					border-bottom: 0;
				}

				.alli1d-status-list__key {
					font-weight: 600;
					color: #1d2327;
				}

				.alli1d-status-list__value {
					color: #3c434a;
					text-align: right;
				}

				.alli1d-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 999px;
					font-size: 11px;
					font-weight: 600;
					line-height: 1.2;
					text-transform: uppercase;
				}

				.alli1d-badge--ok {
					background: #e7f7ed;
					color: #116329;
				}

				.alli1d-badge--error {
					background: #fbeaea;
					color: #8a2424;
				}

				.alli1d-badge--unknown {
					background: #eef0f1;
					color: #3c434a;
				}
			</style>

			<?php if ( empty( $status ) ) : ?>
				<p><?php esc_html_e( 'Aucun statut disponible pour le moment.', 'all-in-one-download' ); ?></p>
			<?php else : ?>
				<div class="alli1d-status-grid">
					<?php foreach ( $status as $provider => $data ) : ?>
						<?php
						$badge_label = __( 'Unknown', 'all-in-one-download' );
						$badge_class = 'alli1d-badge--unknown';

						if ( is_array( $data ) && ! empty( $data['error'] ) ) {
							$badge_label = __( 'Error', 'all-in-one-download' );
							$badge_class = 'alli1d-badge--error';
						} elseif ( is_array( $data ) && isset( $data['status'] ) && 'connected' === (string) $data['status'] ) {
							$badge_label = __( 'Connected', 'all-in-one-download' );
							$badge_class = 'alli1d-badge--ok';
						}
						?>
						<div class="alli1d-status-card">
							<div class="alli1d-status-card__head">
								<h2 class="alli1d-status-card__title"><?php echo esc_html( (string) $provider ); ?></h2>
							<div class="alli1d-status-card__actions">
								<?php
                                $settings_url = null;
                                if ( is_array( $data ) && isset( $data['settings_url'] ) ) {
                                    $settings_url = esc_url( $data['settings_url'] );
                                    unset( $data['settings_url'] );
                                }
								$modal_slug = 'alli1d-settings-modal-' . sanitize_title( $provider );
								if ( isset( $modals[ $provider ] ) ) {
									?>
									<button type="button" class="alli1d-status-card__settings-link" data-modal="<?php echo esc_attr( $modal_slug ); ?>" title="<?php esc_attr_e( 'Paramètres', 'all-in-one-download' ); ?>">
										<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
									</button>
									<?php
								} elseif ( $settings_url ) {
									?>
									<a href="<?php echo esc_url( $settings_url ); ?>" class="alli1d-status-card__settings-link" title="<?php esc_attr_e( 'Paramètres', 'all-in-one-download' ); ?>">
										<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
									</a>
									<?php
								}
								?>
								<span class="alli1d-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span>
							</div>
							</div>
							<div class="alli1d-status-card__body">
								<?php if ( is_array( $data ) ) : ?>
									<dl class="alli1d-status-list">
										<?php foreach ( $data as $key => $value ) : ?>
											<div class="alli1d-status-list__row">
												<dt class="alli1d-status-list__key"><?php echo ucfirst( esc_html( (string) $key ) ); ?></dt>
												<dd class="alli1d-status-list__value"><?php echo ucfirst( esc_html( $this->stringify_value( $value ) ) ); ?></dd>
											</div>
										<?php endforeach; ?>
									</dl>
								<?php else : ?>
									<p><?php echo ucfirst( esc_html( $this->stringify_value( $data ) ) ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php foreach ( $modals as $provider => $modal ) : ?>
				<?php
				$modal_id    = 'alli1d-settings-modal-' . sanitize_title( $provider );
				$modal_title = isset( $modal['title'] ) ? $modal['title'] : (string) $provider;
				$modal_html  = isset( $modal['html'] ) ? $modal['html'] : '';
				?>
				<template id="<?php echo esc_attr( $modal_id ); ?>" data-title="<?php echo esc_attr( $modal_title ); ?>">
					<?php echo $modal_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML provided by trusted add-on filters ?>
				</template>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Convert any status value to a readable string.
	 *
	 * @param mixed $value Raw status value.
	 */
	private function stringify_value( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( null === $value ) {
			return 'null';
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( is_array( $value ) ) {
			return wp_json_encode( $value ) ?: '';
		}

		return __( 'Complex value', 'all-in-one-download' );
	}
}
