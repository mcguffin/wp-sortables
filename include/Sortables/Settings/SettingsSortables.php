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

class SettingsSortables extends Settings {

	private $optionset = 'sortables';


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		parent::__construct();

	}

	/**
	 *	Add Settings page
	 *
	 *	@action admin_menu
	 */
	public function admin_menu() {
		add_options_page( __('WP Sortables Settings' , 'wp-sortables' ),__( 'Sortables' , 'wp-sortables' ), 'manage_options', $this->optionset, array( $this, 'settings_page' ) );
	}

	/**
	 *	Render Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('WP Sortables Settings', 'wp-sortables') ?></h2>

			<form action="options.php" method="post">
				<?php
				settings_fields(  $this->optionset );
				do_settings_sections( $this->optionset );
				submit_button( __('Save Settings' , 'wp-sortables' ) );
				?>
			</form>
		</div><?php
	}


	/**
	 * Enqueue settings Assets
	 *
	 *	@action load-options-{$this->optionset}.php

	 */
	public function enqueue_assets() {

	}


	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$settings_section	= 'sortables_settings';

		add_settings_section( $settings_section, __( 'Sortable Types',  'wp-sortables' ), array( $this, 'sortables_description' ), $this->optionset );

		$post_types = get_post_types(array(
			'show_ui'	=> true,
		));


		// more settings go here ...
		$option_name		= 'sortable_post_types';
		register_setting( $this->optionset , $option_name, array( $this, 'sanitize_post_types' ) );
		add_settings_field(
			$option_name,
			__( 'Post Types',  'wp-sortables' ),
			array( $this, 'select_post_types' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
			)
		);


		// more settings go here ...
		$option_name		= 'sortable_taxonomies';
		register_setting( $this->optionset , $option_name, array( $this, 'sanitize_taxonomies' ) );
		add_settings_field(
			$option_name,
			__( 'Taxonomies',  'wp-sortables' ),
			array( $this, 'select_taxonomies' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
			)
		);

	}

	/**
	 * Print some documentation for the optionset
	 */
	public function sortables_description( $args ) {

		?>
		<div class="inside">
			<p><?php _e( 'Select which Content Types you would like to make sortable.', 'wp-sortables' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output Theme selectbox
	 */
	public function select_post_types( $args ) {

		@list( $option_name ) = array_values( $args );

		$option_value = (array) get_option( $option_name );
		$post_types = get_post_types( array(
			'show_ui'	=> true,
		), 'objects' );

		foreach ( $post_types as $pto ) {
			$id = $option_name . '-' . $pto->name;
			?>
				<label for="<?php echo $id ?>">
					<input type="checkbox" id="<?php echo $id ?>" name="<?php echo $option_name ?>[]" value="<?php echo $pto->name; ?>" <?php checked( in_array($pto->name,$option_value) ) ?> />
					<?php echo $pto->labels->name ?>
				</label>
			<?php
		}

			?>
		<?php

	}


	/**
	 * Output Theme selectbox
	 */
	public function select_taxonomies( $args ) {

		@list( $option_name ) = array_values( $args );

		$option_value = (array) get_option( $option_name );
		$taxonomies = get_taxonomies( array(
			'show_ui'	=> true,
		), 'objects' );

		foreach ( $taxonomies as $txo ) {
			$id = $option_name . '-' . $txo->name;
			?>
				<label for="<?php echo $id ?>">
					<input type="checkbox" id="<?php echo $id ?>" name="<?php echo $option_name ?>[]" value="<?php echo $txo->name; ?>" <?php checked( in_array($txo->name,$option_value) ) ?> />
					<?php echo $txo->labels->name ?>
				</label>
			<?php
		}

			?>
		<?php

	}

	/**
	 * Sanitize Taxonomy array
	 *
	 * @return array sanitized value
	 */
	public function sanitize_taxonomies( $value ) {
		// do sanitation here!
		$value = array_filter( $value, 'taxonomy_exists');
		return $value;
	}


	/**
	 * Sanitize Taxonomy array
	 *
	 * @return array sanitized value
	 */
	public function sanitize_post_types( $value ) {
		// do sanitation here!
		$value = array_filter( $value, 'post_type_exists');
		return $value;
	}




	/**
	 *	Fired on plugin activation
	 */
	public function activate() {
		add_option( 'sortable_post_types' , array() , '' , false );
		add_option( 'sortable_taxonomies' , array() , '' , false );
	}


	/**
	 *	Fired on plugin updgrade
	 *
	 *	@param string $nev_version
	 *	@param string $old_version
	 *	@return array(
	 *		'success' => bool,
	 *		'messages' => array,
	 * )
	 */
	public function upgrade( $new_version, $old_version ) {
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public function deactivate() {
	}

	/**
	 *	Fired on plugin deinstallation
	 */
	public static function uninstall() {
		delete_option( 'sortable_post_types' );
		delete_option( 'sortable_taxonomies' );

	}


}
