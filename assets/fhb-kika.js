jQuery(document).ready(function () {


	function updateSnippet(data, that) {
		var snippets = data.snippets;
		var source = jQuery(that);
		var update = source.data('update');

		for (var key in snippets) {
			var html = snippets[key];
			var el = update ? jQuery(update) : jQuery('#snippet-' + key);
			el.show();

			if (source.is('[data-ajax-append]')) {
				el.append(html);
			} else if (source.is('[data-ajax-prepend]')) {
				el.prepend(html);
			} else if (source.is('[data-ajax-replace]')) {
				el.replaceWith(html);
			} else if(source.is('[data-ajax-html]')) {
				el.html(html);
			} else if (el.is('[data-ajax-append]')) {
				el.append(html);
			} else if (el.is('[data-ajax-prepend]')) {
				el.prepend(html);
			} else if (el.is('[data-ajax-replace]')) {
				el.replaceWith(html);
			} else {
				el.html(html);
			}
		}

	}

	var repeat = false;
	jQuery(document).on('click', 'button.kika-repeat-ajax', function(){

		var url = jQuery(this).data('url');
		var el = jQuery(this);
		var text = el.text();
		var stopText = el.data('stop-text');
		var endText = el.data('end-text');
		var spinner = el.data('spinner');

		function ajax() {

			jQuery.ajax({
				url: url,
				type: 'GET',
				async: true,
				dataType: "json",
				success: function (data) {
					updateSnippet(data, el);
					if (repeat) {
						ajax();
					} else {
						el.prop('disabled', false).text(text);
						jQuery(spinner).hide();
					}
				},
				error: function (data) {
					el.prop('disabled', false).text(text);
					jQuery(spinner).hide();
					repeat = false;
				}
			});
		}

		if (repeat) {
			repeat = false;
			el.prop('disabled', true).text(endText);
		} else {
			repeat = true;
			el.text(stopText);
			jQuery(spinner).show();
			ajax();
		}

		return false;
	});


	jQuery(document).on('click', 'button.kika-ajax', function(){

		var url = jQuery(this).data('url');
		var el = jQuery(this);
		var text = el.text();
		var progressText = el.data('progress-text');
		var spinner = el.data('spinner');

		el.text(progressText);
		jQuery(spinner).show();

		jQuery.ajax({
			url: url,
			type: 'GET',
			async: true,
			dataType: 'json',
			success: function (data) {
				updateSnippet(data, el);
				el.text(text);
				jQuery(spinner).hide();
			}
		});
	});


	jQuery(document).on('click', 'button.kika-order-ajax', function(){

		var url = jQuery(this).data('url');
		var el = jQuery(this);

		var cod = jQuery('#kika-cod').val();
		var service = jQuery('#kika-service').val();

		var text = el.text();
		var progressText = el.data('progress-text');
		var spinner = el.data('spinner');

		el.text(progressText);
		jQuery(spinner).show();

		jQuery.ajax({
			url: url,
			type: 'GET',
			async: true,
			dataType: 'json',
			data: {service: service, cod: cod},
			success: function (data) {
				updateSnippet(data, el);
				el.text(text);
				jQuery(spinner).hide();
			}
		});
	});
        
        
	jQuery(document).on('click', 'button.kika-delivery-mapping-delete', function(e){
		e.preventDefault();
		jQuery(this).parent().parent().remove();
	});


	jQuery(document).on('click', 'button.kika-delivery-mapping-add', function(e){
		e.preventDefault();
		var button = jQuery(this);
		var count = jQuery('.delivery-mapping tr').length;
		var clone = jQuery('.delivery-mapping tr:last').clone();
		clone.find('input').attr('name', 'deliveryMapping['+count+']');
		clone.find('input').attr('id', 'deliveryMapping['+count+']');
		clone.find('select').attr('name', 'deliveryMappingService['+count+']');
		jQuery('.delivery-mapping').append(clone);
	});


});
