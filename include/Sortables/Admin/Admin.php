<?php

namespace Sortables\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use Sortables\Core;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'admin_init' ) );
		add_action( 'admin_print_scripts', array( $this , 'enqueue_assets' ) );
	}


	/**
	 *	Admin init
	 *	@action admin_init
	 */
	function admin_init() {
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'sortables-admin' , $this->core->get_asset_url( '/css/admin.css' ) );

		wp_enqueue_script( 'sortables-admin' , $this->core->get_asset_url( 'js/admin.js' ) );
		wp_localize_script('sortables-admin' , 'sortables_admin' , array(
		) );
	}

}
