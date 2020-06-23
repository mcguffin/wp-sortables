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

		add_action( 'admin_init', [ $this , 'admin_init' ] );
		add_action( 'admin_print_scripts', [ $this , 'enqueue_assets' ] );
	}


	/**
	 *	Admin init
	 *	@action admin_init
	 */
	public function admin_init() {

		foreach ( $this->core->get_sortable_post_types() as $post_type ) {
			// allow to disable column
			if ( ! apply_filters( 'show_sort_column', true ) || ! apply_filters( "show_{$post_type}_sort_column", true ) ) {
				continue;
			}
			add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_sort_column' ] );
			add_filter( "manage_{$post_type}_posts_custom_column", [ $this, 'display_post_sort_column' ], 10, 2 );
		}

		foreach ( $this->core->get_sortable_taxonomies() as $taxonomy ) {
			$cols_hook = "manage_edit-{$taxonomy}_columns";
			$col_hook = "manage_{$taxonomy}_custom_column";
			add_filter( "manage_edit-{$taxonomy}_sortable_columns", [ $this, 'sortable_columns' ] );
			add_filter( $cols_hook, [ $this, 'add_sort_column' ] );
			add_filter( $col_hook, [ $this, 'display_term_sort_column' ], 10, 3 );


		}
	}

	/**
	 *	@filter manage_edit-{$post_type}_sortable_columns
	 *	@filter manage_edit-{$taxonomy}_sortable_columns
	 */
	public function sortable_columns( $columns ) {
		$columns['menu_order'] = 'menu_order';
		return $columns;
	}

	/**
	 *	@filter manage_{$post_type}_posts_columns
	 *	@filter manage_edit-{$taxonomy}_columns
	 */
	public function add_sort_column( $columns ) {
		return [ 'menu_order' => __('#','wp-sortables') ] + $columns;// + [ '_sortables_hidden' => '' ];
	}

	/**
	 *	@filter manage_{$post_type}_posts_custom_column
	 */
	public function display_post_sort_column( $column, $post_id ) {
		if ( $column === 'menu_order' ) {
			$post = get_post( $post_id );
			printf(
				'<span data-parent-id="%d" class="sort-handle">%d</span>',
				intval( $post->post_parent ),
				intval( $post->menu_order )
			);
		}
	}

	/**
	 *	@filter manage_{$taxonomy}_custom_column
	 */
	public function display_term_sort_column( $content, $column, $term_id ) {
		if ( $column === 'menu_order' ) {
			return sprintf(
				'<span data-parent-id="%d" class="sort-handle">%d</span>',
				intval( get_term( $term_id )->parent ),
				intval( get_term_meta( $term_id, 'menu_order', true ) )
			);
		}
		return $content;
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	public function enqueue_assets() {

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

		wp_enqueue_style( 'sortables-admin', $this->core->get_asset_url( '/css/admin/admin.css' ), [], $this->core->get_version() );
		wp_enqueue_script( 'sortables-admin', $this->core->get_asset_url( 'js/admin/admin.js' ), ['wp-api', 'jquery-ui-sortable'], $this->core->get_version() );
		wp_localize_script('sortables-admin', 'sortables_admin', [
			'options'	=> [
				'object_type'	=> $object_type,
				'rest_base'	=> $obj->rest_base,
			],
			'l10n'		=> [],
		] );
	}

}
