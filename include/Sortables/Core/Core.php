<?php

namespace Sortables\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class Core extends Plugin {

	private $sorted_post_types = [];
	private $sorted_taxonomies = [];

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
//		add_action( 'after_setup_theme' , array( $this , 'init_sortables' ), 15 );
		add_action( 'init', [ $this, 'init' ], 15 );
		add_action( 'wp_enqueue_scripts', [ $this , 'wp_enqueue_style' ] );

		// custom post sorting
		add_filter( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
		add_filter( 'get_next_post_sort', [ $this, 'get_adjacent_post_sort' ], 10, 3 );
		add_filter( 'get_previous_post_sort', [ $this, 'get_adjacent_post_sort' ], 10, 3 );
		add_filter( 'get_previous_post_where', [ $this, 'get_adjacent_post_where' ], 10, 5 );
		add_filter( 'get_next_post_where', [ $this, 'get_adjacent_post_where' ], 10, 5 );

		// custom terms sorting
		add_action('parse_term_query', [ $this , 'parse_term_query' ], 10, 1 );

		$args = func_get_args();
		parent::__construct( ...$args );
	}

	/**
	 *	@action parse_term_query
	 */
	public function parse_term_query( $query ) {

		if ( ! isset( $query->query_vars['taxonomy'] ) ||  ! is_array( $query->query_vars['taxonomy'] ) || 1 !== count( $query->query_vars['taxonomy'] ) ) {
			return;
		}

		if ( ! $this->is_sortable_taxonomy( $query->query_vars['taxonomy'][0] ) ) {
			return;
		}

		if ( isset( $_REQUEST['orderby'] ) && $_REQUEST['orderby'] !== 'menu_order' ) {
			return;
		}

		$qv = $query->query_vars;
		$qv['meta_query'] = [
			'relation'	=> 'OR',
			[
				'key' => 'menu_order',
				'compare' => 'EXISTS',
				'type'	=> 'NUMERIC',
			],
			[
				'key' => 'menu_order',
				'compare' => 'NOT EXISTS',
				'type'	=> 'NUMERIC',
			],
		];
		$qv['orderby'] = 'meta_value';
		//$qv['order'] = 'ASC';
		$query->query_vars = $qv;
	}



	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}



	/**
	 *	Init hook.
	 *
	 *  @action after_setup_theme
	 */
	public function init() {
		$this->init_sortables();
	}


	/**
	 *	gather sortable post types and taxonomies
	 */
	public function init_sortables() {
		global $wp_post_types;

		// gather sorted post types
		$sorted_post_types = [];
		//*
		$this->sorted_post_types = get_option( 'sortable_post_types' );

		$this->sorted_taxonomies = get_option( 'sortable_taxonomies' );

		if ( ! is_array( $this->sorted_post_types ) ) {
			$this->sorted_post_types = [];
		}

		if ( ! is_array( $this->sorted_taxonomies ) ) {
			$this->sorted_taxonomies = [];
		}

		foreach ( $this->sorted_post_types as $post_type ) {
			if ( isset( $wp_post_types[$post_type] ) ) {
				// make sure pt has `menu_order` property
				add_post_type_support($post_type,'page-attributes');

				$pt = $wp_post_types[ $post_type ];

				if ( ! $pt->show_in_rest ) {
					$pt->show_in_rest = true;
				}
				if ( ! $pt->rest_base ) {
					$pt->rest_base = $pt->query_var . 's';
				}
				// make sure the rest api works
				if ( ! $pt->rest_controller_class ) {
					$pt->rest_controller_class = 'WP_REST_Posts_Controller';
				}
			}
		}

		if ( ! empty( $this->sorted_taxonomies ) ) {
			register_meta( 'term', 'menu_order', [
				'type'			=> 'integer',
				'description'	=> __('Sort Key for Terms', 'wp-sortables'),
				'single' 		=> true,
				'show_in_rest'	=> true,
			]);
			// make sure the rest api works
			foreach ( $this->sorted_taxonomies as $taxonomy ) {
				$tx = get_taxonomy( $taxonomy );

				if ( ! is_object( $tx ) ) {
					continue;
				}

				if ( ! $tx->show_in_rest ) {
					$tx->show_in_rest = true;
				}
				if ( ! $tx->rest_base ) {
					$tx->rest_base = $tx->name . 's';
				}
				// make sure the rest api works
				if ( ! $tx->rest_controller_class ) {
					$tx->rest_controller_class = 'WP_REST_Terms_Controller';
				}
				register_meta( 'term', 'menu_order', [
					'object_subtype'	=> $taxonomy,
					'type'				=> 'integer',
					'description'		=> '',
					'single'			=> true,
					'show_in_rest'		=> true,
				]);
			}

		}

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
		return in_array( $post_type, $this->sorted_post_types );
	}

	/**
	 *	@return array
	 */
	public function get_sortable_taxonomies( ) {
		return $this->sorted_taxonomies;
	}

	/**
	 *	Whether a post type is sortable
	 *	@param string $post_type
	 *	@return bool
	 */
	public function is_sortable_taxonomy( $taxonomy ) {
		return in_array( $taxonomy, $this->sorted_taxonomies );
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, $this->get_plugin_file() );
	}


	/**
	 *	@filter get_{$adjacent}_post_where
	 */
	public function get_adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		if ( ! $this->is_sortable_post_type($post->post_type) ) {
			return $where;
		}

		global $wpdb;

		if ( get_post_type_object( $post->post_type )->hierarchical && $post->post_parent != 0 ) {
			$where .= $wpdb->prepare( " AND p.post_parent = %d", $post->post_parent );
		}

		$op = current_action() === 'get_previous_post_where' ? '<' : '>';
		// Urgh. Regex. feels like back in the days ...
		$where = preg_replace( '/WHERE\sp\.post_date\s[<>]\s\'([0-9-\s:]+)\'/', '', $where );

		$where = $wpdb->prepare( "WHERE p.menu_order $op %d $where", $post->menu_order ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $where;
	}

	/**
	 *	@filter get_{$adjacent}_post_sort
	 */
	public function get_adjacent_post_sort( $sort, $post, $order ) {

		if ( ! $this->is_sortable_post_type($post->post_type) ) {
			return $sort;
		}

		$sort = "ORDER BY p.menu_order $order LIMIT 1";

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
		$query->set('order', 
			isset( $_REQUEST['order'] ) && 'DESC' === wp_unslash( $_REQUEST['order'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			? 'DESC' : 'ASC' 
		);

		return $query;
	}



}
