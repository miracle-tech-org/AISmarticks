/**
 * Module's JavaScript.
 */

var wc_customer_emails = [];

function initWooCommerce(customer_emails, load)
{
	wc_customer_emails = customer_emails;

	if (!Array.isArray(wc_customer_emails)) {
		wc_customer_emails = [];
	}

	$(document).ready(function(){

		if (load) {
			wcLoadOrders();
		}

		$('.wc-refresh').click(function(e) {
			wcLoadOrders();
			e.preventDefault();
		});
	});
}

function wcLoadOrders()
{
	$('#wc-orders').addClass('wc-loading');

	fsAjax({
			action: 'orders',
			customer_emails: wc_customer_emails,
			mailbox_id: getGlobalAttr('mailbox_id')
		}, 
		laroute.route('woocommerce.ajax'), 
		function(response) {
			if (typeof(response.status) != "undefined" && response.status == 'success'
				&& typeof(response.html) != "undefined" && response.html
			) {
				$('#wc-orders').html(response.html);
				$('#wc-orders').removeClass('wc-loading');

				$('.wc-refresh').click(function(e) {
					wcLoadOrders();
					e.preventDefault();
				});
			} else {
				//showAjaxError(response);
			}
		}, true
	);
}