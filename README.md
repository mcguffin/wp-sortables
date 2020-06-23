WP Sortables
============

Add manual drag and drop sorting for posts, pages custom post types and taxonomies.

Current Project status
----------------------
This plugin is still in alpha state. This means it is not yet as functional as I
would wish it to be.

As for now, any help in the implementation is welcome, but please don't expect
it to work properly in a production environment.

Installation
------------
### Production (Stand-Alone)
 - Head over to [releases](../../releases)
 - Download 'wp-sortables.zip'
 - Upload and activate it like any other WordPress plugin
 - AutoUpdate will run as long as the plugin is active

### Production (using Github Updater – recommended for Multisite)
 - Install [Andy Fragen's GitHub Updater](https://github.com/afragen/github-updater) first.
 - In WP Admin go to Settings / GitHub Updater / Install Plugin. Enter `mcguffin/wp-sortables` as a Plugin-URI.

### Development
 - cd into your plugin directory
 - $ `git clone git@github.com:mcguffin/wp-sortables.git`
 - $ `cd wp-sortables`
 - $ `npm install`
 - $ `npm run dev`
 - Happy developing!

Usage:
------

### Making Post types sortable

All post types that support page attributes will be made sortable.  Additionally
you can explicitly specify sorted post types through the `sortable_post_types` filter.

```
add_filter('sortable_post_types',function( $post_types ) {
	return array( 'page', 'thing' );
});
```

Sorted post types are sorted by `menu_order` in loops by default, as well as in `get_previous_post_link()`
and `get_next_post_link()`.

### Taxonomies

As Taxonomy terms don't provide a sorting mechanism by default, the only way to make them sortable is the `sortable_taxonomies` filter.

```
add_filter('sortable_taxonomies',function( $taxonomies ) {
	return array( 'genre', 'category' );
});
```

ToDo:
-----
 - [ ] Support Hierarchies

