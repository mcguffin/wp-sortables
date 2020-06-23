WP Sortables
============

Add manual drag and drop sorting for posts, pages custom post types and taxonomies.

Current Project status
----------------------
This plugin is still in alpha state. This means it is not yet as functional as I
would wish it to be.

As for now, any help in the implementation is welcome, but please don't expect
it to work properly in a production environment.

Usage:
------

### Making Post types sortable

All post types that support page attributes will be made sortable.  Additionally
you can expliitly specify sorted post types through the `sortable_post_types` filter.

```
add_filter('sortable_post_types',function( $post_types ) {
	return array( 'page', 'thing' );
});
```

Sorted post types are sorted by `menu_order` in loops by default, as well as in `get_previous_post_link()`
and `get_next_post_link()`.

### Taxonomies

As Taxonomy terms don't provide a sorting mechanism by default, the only way to make
them sortable is the `sortable_taxonomies` filter.

```
add_filter('sortable_taxonomies',function( $taxonomies ) {
	return array( 'genre', 'category' );
});
```

ToDo:
-----
 - [ ] Support Hierarchies
 - [ ] Remove AutoUpdate

