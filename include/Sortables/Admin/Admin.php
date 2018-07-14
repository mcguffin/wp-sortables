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
			add_filter( $col_hook, array( $this, 'display_post_sort_column' ), 10, 2 );
		}

		foreach ( $this->core->get_sortable_taxonomies() as $taxonomy ) {
			$cols_hook = "manage_edit-{$taxonomy}_columns";
			$col_hook = "manage_{$taxonomy}_custom_column";
			add_filter( $cols_hook, array( $this, 'add_sort_column' ) );
			add_filter( $col_hook, array( $this, 'display_term_sort_column' ), 10, 3 );


		}
	}
	/**
	 *	@filter manage_{$post_type}_posts_columns
	 *	@filter manage_edit-{$taxonomy}_columns
	 */
	public function add_sort_column( $columns ) {
		return array( 'menu_order' => __('#','wp-sortables') ) + $columns;
	}

	/**
	 *	@filter manage_{$post_type}_posts_custom_column
	 */
	public function display_post_sort_column( $column, $post_id ) {
		if ( $column === 'menu_order' ) {
			printf( '<span class="sort-handle">%d</span>', get_post($post_id)->menu_order );
		}
	}

	/**
	 *	@filter manage_{$taxonomy}_custom_column
	 */
	public function display_term_sort_column( $content, $column, $term_id ) {
		if ( $column === 'menu_order' ) {
			return sprintf( '<span class="sort-handle">%d</span>', get_term_meta( $term_id, 'menu_order', true ) );
		}
		return $content;
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	function enqueue_assets() {

		$screen = get_current_screen();
		if ( $screen->taxonomy ) {
			$object_type = $screen->taxonomy;
			$obj = get_taxonomy( $object_type );
		} else if ( $screen->post_type ) {
			$object_type = $screen->post_type;
			$obj = get_post_type_object( $object_type );
		} else {
			return;
		}

		wp_enqueue_style( 'sortables-admin' , $this->core->get_asset_url( '/css/admin/admin.css' ) );
		wp_enqueue_script( 'sortables-admin' , $this->core->get_asset_url( 'js/admin/admin.js' ), array('wp-api') );
		wp_localize_script('sortables-admin' , 'sortables_admin' , array(
			'options'	=> array(
				'object_type'	=> $object_type,
				'rest_base'	=> $obj->rest_base,
			),
			'l10n'		=> array(

			),
		) );
	}

}
