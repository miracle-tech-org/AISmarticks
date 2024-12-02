/**
 * Module's JavaScript.
 */

function mailsigningInit()
{
	$(document).ready(function(){
		$(':input[name="settings\[protocol\]"]').change(function(e) {
			$('.ms-protocol').addClass('hidden');
			$('.ms-protocol-'+$(':input[name="settings\[protocol\]"]:checked').val()).removeClass('hidden');
		});
		$(':input[name="settings\[mode\]"]').change(function(e) {
			$('.ms-mode').addClass('hidden');
			$('.ms-mode-'+$(':input[name="settings\[mode\]"]:checked').val()).removeClass('hidden');
		});
		$('.content-2col :input[id!="send_test"]').on("change keyup", function(e) {
			$('#send_test').attr('disabled', 'disabled');
		});

		$('#send-test-trigger').click(function(event) {
	    	var button = $(this);
	    	button.button('loading');
	    	fsAjax(
				{
					action: 'send_test',
					mailbox_id: getGlobalAttr('mailbox_id'),
					to: $('#send_test').val()
				},
				laroute.route('mailboxes.ajax'),
				function(response) {
					if (isAjaxSuccess(response)) {
						showFloatingAlert('success', Lang.get("messages.email_sent"));
					} else {
						showAjaxError(response, true);
					}
					button.button('reset');
				},
				true
			);
		});
	});
}
