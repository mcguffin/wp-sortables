<?php

namespace Sortables\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class Core extends Plugin {

	private $sorted_post_types = array();
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

		// custom terms sorting
		add_action('parse_term_query', array( $this , 'parse_term_query' ), 10, 1 );

		parent::__construct();
	}

	/**
	 *	@action parse_term_query
	 */
	function parse_term_query( $query ) {

		if ( 1 !== count( $query->query_vars['taxonomy'] ) ) {
			return;
		}

		if ( ! $this->is_sortable_taxonomy( $query->query_vars['taxonomy'][0] ) ) {
			return;
		}

		if ( isset( $_REQUEST['orderby'] ) && $_REQUEST['orderby'] !== 'menu_order' ) {
			return;
		}

		$qv = $query->query_vars;
		$qv['meta_query'] = array(
			'relation'	=> 'OR',
			array(
				'key' => 'menu_order',
				'compare' => 'EXISTS',
				'type'	=> 'NUMERIC',
			),
			array(
				'key' => 'menu_order',
				'compare' => 'NOT EXISTS',
				'type'	=> 'NUMERIC',
			),
		);
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

		// gather sorted post types
		$sorted_post_types = array();

		foreach ( $wp_post_types as $k => $pt ) {
			if ( post_type_supports( $k, 'page-attributes' ) ) {

				$sorted_post_types[] = $k;

			}
		}
		$this->sorted_post_types = apply_filters( 'sortable_post_types', $sorted_post_types );

		// make sure the rest api works
		foreach ( $this->sorted_post_types as $post_type ) {
			if ( isset( $wp_post_types[$post_type] ) ) {
				$pt = $wp_post_types[ $post_type ];

				if ( ! $pt->show_in_rest ) {
					$pt->show_in_rest = true;
				}
				if ( ! $pt->rest_base ) {
					$pt->rest_base = $pt->query_var . 's';
				}
				if ( ! $pt->rest_controller_class ) {
					$pt->rest_controller_class = 'WP_REST_Posts_Controller';
				}
			}
		}

		$this->sorted_taxonomies = apply_filters( 'sortable_taxonomies', array() );

		if ( ! empty( $this->sorted_taxonomies ) ) {
			register_meta( 'term', 'menu_order', [
				'type'			=> 'integer',
				'description'	=> __('Sort Key for Terms', 'wp-sortables'),
				'single' 		=> true,
				'show_in_rest'	=> true,
			]);

		}
		// make sure the rest api works
		foreach ( $this->sorted_taxonomies as $taxonomy ) {
			$tx = get_taxonomy( $taxonomy );
			if ( ! $tx->show_in_rest ) {
				$tx->show_in_rest = true;
			}
			if ( ! $tx->rest_base ) {
				$tx->rest_base = $tx->name . 's';
			}
			if ( ! $tx->rest_controller_class ) {
				$tx->rest_controller_class = 'WP_REST_Terms_Controller';
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
		if ( ! did_action('init') ) {
			_doing_it_wrong('Sortable\Core\Core::is_sorted_post_type',__('is_sorted_post_type() must be called after the init hook','wp-sortables'), '0.0.1' );
		}
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
		if ( ! did_action('init') ) {
			_doing_it_wrong('Sortable\Core\Core::is_sorted_post_type',__('is_sorted_post_type() must be called after the init hook','wp-sortables'), '0.0.1' );
		}
		return in_array( $taxonomy, $this->sorted_taxonomies );
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

		$where = $wpdb->prepare( "WHERE p.menu_order $op %d $where", $post->menu_order );
		return $where;
	}

	/**
	 *	@filter get_{$adjacent}_post_sort
	 */
	public function get_adjacent_post_sort( $sort, $post, $order ) {

		if ( ! $this->is_sortable_post_type($post->post_type) ) {
			return;
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
		$query->set('order', isset($_REQUEST['order']) ? $_REQUEST['order'] : 'ASC' );

		return $query;
	}



}
