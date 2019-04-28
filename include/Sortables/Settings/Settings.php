<?php
/**
 *	@package Sortables\Settings
 *	@version 1.0.0
 *	2018-09-22
 */

namespace Sortables\Settings;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use Sortables\Core;

abstract class Settings extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct(){

		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		parent::__construct();

	}

	/**
	 *	@action admin_init
	 */
	abstract function register_settings();


	/**
	 *	Print a checkbox
	 *
	 *	@param $args	array( $option_name, $label )
	 */
	public function checkbox_ui( $args ) {
		@list( $option_name, $label, $description ) = array_values( $args );

		$option_value = get_option( $option_name );

		?><label>
			<input type="hidden" name="<?php echo $option_name ?>" value="0" />
			<input type="checkbox" <?php checked( boolval( $option_value ), true, true ); ?> name="<?php echo $option_name ?>" value="1" />
			<?php echo $label ?>
		</label>
		<?php
			if ( ! empty( $description ) ) {
				printf( '<p class="description">%s</p>', $description );
			}
		?>
		<?php

	}

	/**
	 *	Sanitize checkbox input
	 *
	 *	@param $value
	 *	@return boolean
	 */
	public function sanitize_checkbox( $value ) {
		return boolval( $value );
	}

}
