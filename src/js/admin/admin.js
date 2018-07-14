(function($){
	var opt = sortables_admin.options,
		l10n = sortables_admin.l10n;

	function updateSort(e) {
		var $table = $(e.target).closest('table'),
			current_page, per_page, counter,
			request, batch, get_data, get_order;

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
		batch = function() {
			if ( ! requests.length ) {
				return;
			}
			var data = requests.shift();
			wp.apiRequest( data ).done(function(response, status, xhr ){

				$('[id$="-'+response.id+'"] .sort-handle').text( get_order( response ) );
				batch();
			});
		};

		$('#the-list tr').each(function( i, el ){
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
		batch();
	}

	$(document).ready(function(){
		$('#the-list').sortable({
			update: updateSort,
			handle: '.sort-handle',
		});
	});

})(jQuery)
