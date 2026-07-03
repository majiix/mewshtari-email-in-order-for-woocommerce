jQuery(function($){
	function getEditorHtml(){
		if (window.tinyMCE && tinyMCE.get('meiofw_note')) { return tinyMCE.get('meiofw_note').getContent(); }
		return $('textarea#meiofw_note').val();
	}
	function setEditorHtml(html){
		if (window.tinyMCE && tinyMCE.get('meiofw_note')) { tinyMCE.get('meiofw_note').setContent(html || ''); }
		$('textarea#meiofw_note').val(html || '');
	}
	$(document).on('change', '#meiofw-template-select', function(){
		var id = String($(this).val());
		var map = window.MEIOFW_TEMPLATES || {};
		setEditorHtml(map[id] || '');
	});
	$(document).on('click','.meiofw-send-now',function(){
		var $btn=$(this);
		var orderId=$btn.data('order');
		var html=getEditorHtml();
		var subject=$('input[name="meiofw_subject"]').val() || 'Donation Confirmation - Islamic Donate Charity';
		var templateId=$('#meiofw-template-select').val() || '0';
		$btn.prop('disabled',true);
		var $status=$('.meiofw-status');
		$status.text(MEIOFW.i18n.sending);
		$.post(MEIOFW.ajaxUrl,{action:'meiofw_send_email',nonce:MEIOFW.nonce,order_id:orderId,content:html,subject:subject,template_id:templateId})
		 .done(function(resp){
			if(resp && resp.success){ location.reload(); }
			else{ $status.text(resp && resp.data && resp.data.message ? resp.data.message : MEIOFW.i18n.failed); }
		 })
		 .fail(function(){ $status.text(MEIOFW.i18n.request); })
		 .always(function(){ $btn.prop('disabled',false); });
	});
});
