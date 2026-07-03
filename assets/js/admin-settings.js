jQuery(function($){
	var container = $('#meiofw-repeater-container');
	var templates = MEIOFW_Settings.templates || [];
	var statuses = MEIOFW_Settings.statuses || {};
	
	function renderRow(index, data) {
		var label = data.label || '';
		var html = data.html || '';
		var currentStatus = data.status || 'wc-completed';
		if (!currentStatus.startsWith('wc-')) {
			currentStatus = 'wc-' + currentStatus;
		}
		
		var statusOptions = '';
		$.each(statuses, function(slug, text) {
			var selected = (slug === currentStatus) ? 'selected' : '';
			statusOptions += '<option value="' + slug + '" ' + selected + '>' + text + '</option>';
		});
		
		var rowHtml = `
			<div class="meiofw-repeater-row" style="display: none;">
				<button type="button" class="button meiofw-remove-row">Remove</button>
				<div class="meiofw-form-group">
					<label>Template Label</label>
					<input type="text" name="meiofw_templates[${index}][label]" value="${label}" class="meiofw-input-text" required />
				</div>
				<div class="meiofw-form-group">
					<label>Order Status on Send</label>
					<select name="meiofw_templates[${index}][status]" class="meiofw-select">
						${statusOptions}
					</select>
					<p class="description">Select the status the order will transition to after this custom email is sent.</p>
				</div>
				<div class="meiofw-form-group">
					<label>HTML Template Content</label>
					<textarea name="meiofw_templates[${index}][html]" class="meiofw-code-editor" style="width: 100%; height: 200px;">${html}</textarea>
				</div>
			</div>
		`;
		
		var $row = $(rowHtml);
		container.append($row);
		$row.slideDown(250); // Delightful row slide-in animation
		
		// Initialize CodeMirror if settings are available
		if (MEIOFW_Settings.codeEditorSettings) {
			wp.codeEditor.initialize($row.find('.meiofw-code-editor'), MEIOFW_Settings.codeEditorSettings);
		}
	}
	
	// Render existing templates
	$.each(templates, function(index, tpl) {
		renderRow(index, tpl);
	});
	
	// Add new template row
	$('#meiofw-add-template').on('click', function() {
		var index = container.find('.meiofw-repeater-row').length;
		renderRow(index, {
			label: 'New Template ' + (index + 1),
			html: '',
			status: 'wc-completed'
		});
	});
	
	// Remove row
	container.on('click', '.meiofw-remove-row', function() {
		var $row = $(this).closest('.meiofw-repeater-row');
		if (confirm('Are you sure you want to remove this template?')) {
			$row.slideUp(200, function() { // Delightful row slide-out animation
				$row.remove();
				reindexRows();
			});
		}
	});
	
	function reindexRows() {
		container.find('.meiofw-repeater-row').each(function(index) {
			var $row = $(this);
			$row.find('input[name^="meiofw_templates"]').attr('name', 'meiofw_templates[' + index + '][label]');
			$row.find('select[name^="meiofw_templates"]').attr('name', 'meiofw_templates[' + index + '][status]');
			$row.find('textarea[name^="meiofw_templates"]').attr('name', 'meiofw_templates[' + index + '][html]');
		});
	}

	// Click-to-copy placeholders micro-interaction
	$(document).on('click', '.meiofw-placeholder-badge', function() {
		var $badge = $(this);
		var code = $badge.find('code').text();
		
		navigator.clipboard.writeText(code).then(function() {
			$badge.addClass('copied');
			setTimeout(function() {
				$badge.removeClass('copied');
			}, 1000);
		});
	});
});
