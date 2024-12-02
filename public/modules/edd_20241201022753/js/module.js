/**
 * Module's JavaScript.
 */

var edd_customer_email = '';

function initEdd(customer_email, load)
{
	edd_customer_email = customer_email;

	$(document).ready(function(){

		if (load) {
			eddLoadOrders();
		}

		$('.edd-refresh').click(function(e) {
			eddLoadOrders();
			e.preventDefault();
		});
	});
}

function eddLoadOrders()
{
	$('#edd-orders').addClass('edd-loading');

	fsAjax({
			action: 'orders',
			customer_email: edd_customer_email,
			mailbox_id: getGlobalAttr('mailbox_id')
		}, 
		laroute.route('edd.ajax'), 
		function(response) {
			if (typeof(response.status) != "undefined" && response.status == 'success'
				&& typeof(response.html) != "undefined" && response.html
			) {
				$('#edd-orders').html(response.html);
				$('#edd-orders').removeClass('edd-loading');

				$('.edd-refresh').click(function(e) {
					eddLoadOrders();
					e.preventDefault();
				});
			} else {
				//showAjaxError(response);
			}
		}, true
	);
}