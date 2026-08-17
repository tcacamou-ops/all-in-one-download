<?php
/**
 * Toast message component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

class ToastMessage {
	/**
	 * Render the toast message container.
	 */
	public function render(): void {
		echo '<div id="toast-container" style="position: fixed; top: 60px; right: 20px; z-index: 100000;"></div>';
	}
}
