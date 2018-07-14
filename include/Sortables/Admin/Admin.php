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
		foreach ( $this->core->get_sortable_post_types() as $post_type ) {
			// allow to disable column
			if ( ! apply_filters( 'show_sort_column', true ) || ! apply_filters( "show_{$post_type}_sort_column", true ) ) {
				continue;
			}
			$cols_hook = "manage_{$post_type}_posts_columns";
			$col_hook = "manage_{$post_type}_posts_custom_column";
			add_filter( $cols_hook, array( $this, 'add_sort_column' ) );
			add_filter( $col_hook, array( $this, 'display_sort_column' ), 10, 2 );
		}
	}

	public function add_sort_column( $columns ) {
		return array( 'menu_order' => __('#','wp-sortables') ) + $columns;
	}

	public function display_sort_column( $column, $post_id ) {
		if ( $column === 'menu_order' ) {
			printf( '<span class="sort-handle">%d</span>', get_post($post_id)->menu_order );
		}
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	function enqueue_assets() {

		if ( ! ( $post_type = get_post_type() ) ) {
			if ( isset( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
			} else {
				return;
			}
		}
		$pto = get_post_type_object( $post_type );
		$pto->rest_base;

		wp_enqueue_style( 'sortables-admin' , $this->core->get_asset_url( '/css/admin/admin.css' ) );
		wp_enqueue_script( 'sortables-admin' , $this->core->get_asset_url( 'js/admin/admin.js' ), array('wp-api') );
		wp_localize_script('sortables-admin' , 'sortables_admin' , array(
			'options'	=> array(
				'post_type'	=> $post_type,
				'rest_base'	=> $pto->rest_base,
			),
			'l10n'		=> array(

			),
		) );
	}

}
