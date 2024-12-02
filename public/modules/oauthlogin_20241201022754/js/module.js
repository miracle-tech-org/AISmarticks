/**
 * Module's JavaScript.
 */

function olInit()
{
	$(document).ready(function(){
		olRenderProviders(ol_providers);
	});
}

function olApplyListeners()
{
	$('.ol-add-provider').click(function(e) {
		ol_providers.push({
			id: olGenerateProviderId(),
			active: 1
		});
		olRenderProviders(ol_providers);

		return false;
	});

	$('.ol-delete-provider').click(function(e) {
		var index = parseInt($(this).attr('data-ol-provider-index'));
		if (typeof(ol_providers[index]) != "undefined") {
			var deleted_provider = ol_providers[index];
			ol_providers.splice(index, 1);
			if (typeof(deleted_provider.default) != "undefined" && deleted_provider.default) {
				if (typeof(ol_providers[0]) != "undefined") {
					ol_providers[0].default = 1;
				}
			}
			olRenderProviders(ol_providers);
		}

		return false;
	});

	$('.ol-provider-field-default').click(function(e) {
		$('.ol-provider-field-default[name!="'+$(this).attr('name')+'"]').prop('checked', false);
	});

	$('.ol-provider-field-provider').change(function(e) {
		var provider_code = $(this).val();
		
		var provider_block = $(this).parents('.ol-provider:first');
		provider_block.children().find(':input[data-ol-no-clear!="1"]').val('');
		for (var field in ol_providers_config[provider_code]) {
			var value = ol_providers_config[provider_code][field];
			provider_block.children().find('.ol-provider-field-'+field+':first').val(value);
		}
	});

	$('[data-toggle="popover"]').popover({
	    container: 'body'
	});
}

function olRenderProviders(providers)
{
	if (!Array.isArray(providers)) {
		providers = [];
	}
	if (providers.length == 0) {
		providers.push({
			id: olGenerateProviderId(),
			active: 1,
			default: 1,
			provider: 'oauth'
		});
	}
	var pattern_html = $('#ol-provider-pattern').html();

	$('.ol-provider').remove();

	var index = 0;
	for (var i in providers) {
		var provider = providers[i];
		var html = replaceAll(pattern_html, 'provider_index', index);

		var provider_block = $('<div class="ol-provider">'+html+'</div>');
		provider_block.children().find(':input').removeAttr('disabled');
		for (var field in provider) {
			var input = provider_block.children().find('.ol-provider-field-'+field+':first');
			if (input.is(':checkbox') || input.is(':radio')) {
				if (provider[field] == 1) {
					input.prop('checked', true);
				} else {
					input.prop('checked', false);
				}
			} else {
				input.val(provider[field]);
			}
		}
		if (index != providers.length-1) {
			provider_block.children().find('.ol-add-provider:first').remove();
		}
		if (providers.length == 1) {
			provider_block.children().find('.ol-delete-provider:first').remove();
		}
		provider_block.children().find('.ol-provider-redirect-uri:first').val(laroute.route('oauthlogin.callback', {provider_id: provider.id}));
		var logout_input = provider_block.children().find('.ol-provider-logout-uri:first');
		logout_input.val(laroute.route('oauthlogin.logout', {provider_id: provider.id, logout_secret: logout_input.attr('data-ol-logout-secret')}));

		provider_block.insertBefore('#ol-provider-pattern');
		index++;
	}
	olApplyListeners();
}

function olGenerateProviderId()
{
	return Math.random().toString(36).substr(2, 9);
}