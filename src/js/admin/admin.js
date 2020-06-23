(function($){


	function $getItems( parent ) {
		return $( '#the-list tr' )
			.filter( function(){
				return !! $(this).find('[data-parent-id="'+parent+'"]').length;
			});
	}


	var inited = false,
		opt = sortables_admin.options,
		l10n = sortables_admin.l10n,
		sortable = {
			update: function( event, ui ) {
				var $table = $('#the-list').closest('table'),
					current_page, per_page, counter,
					get_data, // data to send to rest api
					get_order,// menu_order received from rest api
					requests, batch,
					parent = $( ui.item ).find('[data-parent-id]').attr('data-parent-id');

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

				$getItems( parent )
					.each(function( i, el ){
						/*
						var id = $(el).find('.check-column [type="checkbox"]').val();
						/*/
						var id = parseInt($(el).attr('id').replace(/[^\d]+/,''))
						//*/

						counter++;

						// only save changed posts
						if ( counter == parseInt( $(el).find('.sort-handle').text() ) ) {
							return;
						}
						$('[id$="-'+id+'"] .sort-handle').addClass( 'sync' );

						requests.push({
							path: wpApiSettings.versionString + opt.rest_base + '/' + id,
							data: get_data( counter ),
							method:'POST',
						});
					});
				batch = function() {
					if ( ! requests.length ) {
						return;
					}
					var data = requests.shift();
					wp.apiRequest( data ).done(function(response, status, xhr ){

						$('[id$="-'+response.id+'"] .sort-handle').removeClass( 'sync' ).text( get_order( response ) );
						batch();
					});
				};

				batch(requests);

			},
			handle: '.sort-handle',
			stop: function( event, ui ) {
				// get children
				$getItems( $(ui.item).find('.check-column [type="checkbox"]').val() )
					.each(function(i,el){
						$(el).insertAfter(ui.item);
					});

			}
		};

	$(document).ready( function() {
		//$('#the-list').sortable(sortable);
		inited = true;
		$('#the-list').sortable( sortable );
	});
	//
	$(document).on('mouseover','#the-list .sort-handle',function(e){
		if ( ! inited ) {
			return;
		}

		var parent = $( e.target ).attr('data-parent-id'),
			$items;

		//console.log($items);
		$( '#the-list' ).sortable( 'option', 'items', $getItems( parent ) );
	});
})(jQuery)
