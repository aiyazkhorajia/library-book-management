jQuery(document).ready(function($) 
{
	// price range slider input
	$( "#price-slider" ).slider({
		range:true,
		min: 1,
		max: 1000,
		values: [ 1, 1000 ],
		slide: function( event, ui ) {
			$( "#price" ).val( "Price between: "+ ui.values[ 0 ] + " - " + ui.values[ 1 ] );
			$( "#min-price" ).val( ui.values[ 0 ]);
			$( "#max-price" ).val( ui.values[ 1 ]);
		}
	}); 
	$( "#price" ).val( "Price between: " + $( "#price-slider" ).slider( "values", 0 ) +
               " - " + $( "#price-slider" ).slider( "values", 1 ) );
	$( "#min-price" ).val( $( "#price-slider" ).slider( "values", 0 ));
	$( "#max-price" ).val( $( "#price-slider" ).slider( "values", 1 ));
	
	$('.custom-search').click(function(e){
		
		e.preventDefault();
		var form_data = $('form[name="search_filter_form"]').serialize();
		var data = {
				action: 'lbm_load_posts',
				security: libfront.security,
				form_data: form_data,
			};
		
		$.post( libfront.ajax_url, data, function( response ) 
		{
			$(".posts-table-container").html(response);
			//console.log(response);
		})
	});
	
	
	/* $('.custom-search').click(function(e)
    {
        e.preventDefault();
		var search_text = $("input[name='search_term']").val();
		var search_string = $("input[name='search_string']").val();
		var publishers = [];
		var authors = [];
		
		$.each($("input[name='publisher[]']:checked"), function(){
			publishers.push($(this).val());
		});
		
		$.each($("input[name='author[]']:checked"), function(){
			authors.push($(this).val());
		});
		
		if(search_string){
			var data = {
				action: 'load_posts',
				security: libfront.security,
				search_text: search_text,
				search_string: search_string,
				publishers: publishers,
				authors: authors
			};

			$.post( libfront.ajax_url, data, function( response ) 
			{
				$(".posts-table-container").html(response);
				//console.log(response);
			});
		}
	});
	
			
	$('.form-tag').on('change','.checkbox-filter', function(e) {
		e.preventDefault();
		var taxonomy = $(this).attr('name');
		var term_id = $(this).val();
		
		var favorite = [];
		$.each($("input[name='"+taxonomy+"']:checked"), function(){            
			favorite.push($(this).val());
			//alert($(this).val());
		});
	}); */

});