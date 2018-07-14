(function($){
	var opt = sortables_admin.options,
		l10n = sortables_admin.l10n;

	function updateSort() {
		var current_page = $('[name="paged"]').val() - 1,
			per_page = $('#edit_'+opt.post_type+'_per_page').val(),//$('#the-list tr').length,
			counter = current_page * per_page,
			requests = [],
			post_type = $('[name="post_type"]').val(),
			batch = function() {
				if ( ! requests.length ) {
					return;
				}
				var data = requests.shift();
				wp.apiRequest( data ).done(function(response, status, xhr ){
					$('#post-'+response.id+' .sort-handle').text(response.menu_order);
					batch();
				});
			};

		$('#the-list tr').each(function( i, el ){
			var post_id = $(el).find('[name="post[]"]').val();
			counter++;

			// only save changed posts
			if ( counter == parseInt( $(el).find('.sort-handle').text() ) ) {
				return;
			}
			requests.push({
				path: wpApiSettings.versionString + opt.rest_base + '/' + post_id,
				data: { menu_order: counter },
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
