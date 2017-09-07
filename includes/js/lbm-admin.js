jQuery(document).ready(function($) 
{
	
    $('body').on('change', '#post-types', function(e)
    {
        e.preventDefault();
		
		// Reset and disable
		$('#post-taxonomies').prop('disabled', 'disabled');
		$('#post-taxonomies')
			.find('option')
			.remove()
			.end()
			.append($('<option>',{ 
						value: "",
						text : "Select Taxonomy",
					}));
			
		// Reset and disable
		$('#taxonomy-terms').prop('disabled', 'disabled');
		$('#taxonomy-terms')
			.find('option')
			.remove()
			.end()
			.append($('<option>',{ 
						value: "",
						text : "Select Term",
					}));
					
	
		if ( $(this).val() )
		{
			var data = {
				action: 'lbm_generate_taxonomy_list',
				security: libadmin.security,
				post_type: $(this).val()
			};

			$.post( libadmin.ajax_url, data, function( response ) 
			{
				$.each(JSON.parse(response), function (i, item) {
					$('#post-taxonomies').append($('<option>', { 
						value: item.name,
						text : item.label 
					}));
					$('#post-taxonomies').prop('disabled', false);
					//console.log(item);
				});
			});
		}
    });

    $('#post-taxonomies').on('change', function(e)
    {
        e.preventDefault();
		$('#taxonomy-terms')
		.find('option')
		.remove()
		.end()
		.append($('<option>',{ 
					value: "",
					text : "Select Term",
				}));
				

		
		if ($(this).val())
		{
			var data = {
				action: 'lbm_generate_terms_list',
				security: libadmin.security,
				taxonomy: $(this).val()
			};

			$.post( libadmin.ajax_url, data, function( response ) 
			{
				$.each(JSON.parse(response), function (i, item) {
					$('#taxonomy-terms').append($('<option>', { 
						value: item.slug,
						text : item.name 
					}));
					$('#taxonomy-terms').prop('disabled', false);
					//console.log(item);
				});
			});
		}
    });
});