(function($){


	function getLevel( el ) {
		var cls = getLevelClass( el );
		if ( 'undefined' === typeof cls ) {
			return;
		}
		return cls.split('-').pop();
	}

	function getLevelClass(el) {
		var cls = $(el).attr('class');

		if ( 'undefined' === typeof cls ) {
			return;
		}
		return cls.split(' ') // array
			.find( function(v){ return v.match(/level-/) } );
	}



	var opt = sortables_admin.options,
		l10n = sortables_admin.l10n,
		sortable = {
			update: function( event, ui ) {
				var $table = $('#the-list').closest('table'),
					current_page, per_page, counter,
					get_data, get_order,
					requests, batch,
					level_class = getLevelClass( ui.item ),
					item_selector = 'undefined' !== typeof level_class ? '#the-list tr.' + level_class : '#the-list tr';

				if ( $table.hasClass('tags') ) {
					get_data = function(counter) {
						return { meta: { menu_order: counter } };
					}
					get_order = function( object ) {
						return object.meta.menu_order;
					}
				} else if ( $table.hasClass('posts') || $table.hasClass('pages') ) {
					get_data = function(counter) {
						return { menu_order: counter };
					}
					get_order = function( object ) {
						return object.menu_order;
					}
				} else {
					return;
				}

				current_page = $('[name="paged"]').val() - 1;
				per_page = $('#edit_'+opt.object_type+'_per_page').val();
				counter = current_page * per_page;
				requests = [];

				$( item_selector ).each(function( i, el ){
					var obj_id = $(el).find('.check-column [type="checkbox"]').val();
					counter++;

					// only save changed posts
					if ( counter == parseInt( $(el).find('.sort-handle').text() ) ) {
						return;
					}
					requests.push({
						path: wpApiSettings.versionString + opt.rest_base + '/' + obj_id,
						data: get_data( counter ),
						method:'POST',
					});
				});
				console.log(requests);
				batch = function() {
					if ( ! requests.length ) {
						return;
					}
					var data = requests.shift();
					console.log(data)
					wp.apiRequest( data ).done(function(response, status, xhr ){

						$('[id$="-'+response.id+'"] .sort-handle').text( get_order( response ) );
						batch();
					});
				};

				batch(requests);

			},
			handle: '.sort-handle',
			start:function( event, ui ) {
				// declare children
				var children = [],
					$next = $(ui.placeholder).next(),
					levelClass = getLevelClass( ui.item ), // str
					level; // faster!

				if ( 'undefined' === typeof levelClass ) {
					return;
				}
//				if ( getLevel( ui.item ) )

				$(ui.placeholder).siblings(':not()')

				while ( $next.length && ! $next.hasClass( levelClass ) ) {
					children.push( $next );
					$next = $next.next('tr');
				}
				$( ui.item ).data( 'children', children );
			},
			stop: function( event, ui ) {

				$( $(ui.item).data('children') ).each(function(i,$el){
					$el.insertAfter(ui.item);
				});

			}
		};

	$(document).ready( function() {
		//$('#the-list').sortable(sortable);
		$('#the-list').sortable( sortable );
	});
	//
	$(document).on('mouseover','#the-list .sort-handle',function(e){
		var level = getLevel( $( e.target ).closest('tr')[0] ),
			include = true,
			$items,
			has_levels = 'undefined' !== typeof level;

		if ( has_levels ) { // has levels
			$items = $( '#the-list' ).children( '.level-' + level )
				.filter(function( idx ){
					ret = include;
					if ( !idx ) {
						return ret;
					}

					if ( include && getLevel( $(this).prev() ) < level ) {
						include = false;
					}
					return ret;
				}); // same level
		} else {
			$items = $( '#the-list' ).children();
		}
		//console.log($items);
		$( '#the-list' ).sortable( 'option', 'items', $items );
	});
})(jQuery)
