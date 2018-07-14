<?php

namespace Sortables\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class Core extends Plugin {

	private $sorted_post_types = array('post');
	private $sorted_taxonomies = array();

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'load_textdomain' ) );
		add_action( 'init' , array( $this , 'init_sortables' ) );
		add_action( 'init' , array( $this , 'init' ) );
		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );

		// custom post sorting
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'get_next_post_sort', array( $this, 'get_adjacent_post_sort'), 10, 3 );
		add_filter( 'get_previous_post_sort', array( $this, 'get_adjacent_post_sort'), 10, 3 );

		add_filter( "get_previous_post_where", array( $this, 'get_adjacent_post_where' ), 10, 5 );
		add_filter( "get_next_post_where", array( $this, 'get_adjacent_post_where' ), 10, 5 );


		parent::__construct();
	}


	/**
	 *	@filter get_{$adjacent}_post_where
	 */
	public function get_adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		if ( ! $this->is_sortable_post_type($post->post_type) ) {
			return;
		}

		global $wpdb;

		if ( get_post_type_object( $post->post_type )->hierarchical && $post->post_parent != 0 ) {
			$where .= $wpdb->prepare( " AND p.post_parent = %d", $post->post_parent );
		}

		$op = current_action() === 'get_previous_post_where' ? '<' : '>';
		// Urgh. Regex. feels like back in the days ...
		$where = preg_replace( '/WHERE\sp\.post_date\s[<>]\s\'([0-9-\s:]+)\'/', '', $where );

		$where = $wpdb->prepare( "WHERE p.menu_order $op %s $where", $post->menu_order );
		return $where;
	}

	/**
	 *	@filter get_{$adjacent}_post_sort
	 */
	public function get_adjacent_post_sort( $sort, $post, $order ) {

		if ( ! $this->is_sortable_post_type($post->post_type) ) {
			return;
		}

		$sort = "ORDER BY p.menu_order 'ASC' LIMIT 1";

		return $sort;
	}

	/**
	 *	@filter pre_get_posts
	 */
	public function pre_get_posts($query) {

		if ( ! $this->is_sortable_post_type( $query->get('post_type') ) ) {
			return $query;
		}

		$query->set('orderby', 'menu_order' );
		$query->set('order', 'ASC' );

		return $query;
	}



	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}




	/**
	 *	Load text domain
	 *
	 *  @action plugins_loaded
	 */
	public function load_textdomain() {
		$path = pathinfo( dirname( SORTABLES_FILE ), PATHINFO_FILENAME );
		load_plugin_textdomain( 'wp-sortables', false, $path . '/languages' );
	}

	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	public function init() {
	}

	public function init_sortables() {
		global $wp_post_types;
		foreach ( $wp_post_types as $k => $pt ) {
			if ( post_type_supports( $k, 'page-attributes' ) ) {
				$this->sorted_post_types[] = $k;
			}
		}
		$this->sorted_post_types = apply_filters( 'sorted_post_types', $this->sorted_post_types );
	}

	/**
	 *	@return array
	 */
	public function get_sortable_post_types( ) {
		return $this->sorted_post_types;
	}

	/**
	 *	Whether a post type is sortable
	 *	@param string $post_type
	 *	@return bool
	 */
	public function is_sortable_post_type( $post_type ) {
		if ( ! did_action('init') ) {
			_doing_it_wrong('Sortable\Core\Core::is_sorted_post_type',__('is_sorted_post_type() must be called after the init hook','wp-sortables'), '0.0.1' );
		}
		return in_array( $post_type, $this->sorted_post_types );
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return wp_enqueue_editor
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, SORTABLES_FILE );
	}



}
