function GenerateReports(reportOptions)
{
	var opts = reportOptions;

	var elements = {
		indicator: $('#indicator'),
		customReportForm: $('#customReportInput'),
		saveDialog: $('#saveDialog'),
		saveReportForm: $('#saveReportForm'),
		resultsDiv: $('#resultsDiv')
	};

	GenerateReports.prototype.init = function ()
	{
		$('#selectDiv input').click(function ()
		{
			$('div.select-toggle').hide();

			if ($(this).attr('id') == 'results_list')
			{
				$('#listOfDiv').show();
			}
			else
			{
				$('#aggregateDiv').show();
			}
		});

		wireUpAutocompleteFilters();

		$('.dateinput').click(function ()
		{
			$('#range_within').attr('checked', 'checked');
		});

		$('#btnCustomReport').click(function (e)
		{
			e.preventDefault();

			var before = function ()
			{
				elements.indicator.show().insertBefore(elements.resultsDiv);
				elements.resultsDiv.html('');
			};

			var after = function (data)
			{
				elements.indicator.hide();
				elements.resultsDiv.html(data);
			};

			ajaxPost(elements.customReportForm, opts.customReportUrl, before, after);
		});

		$('#showHideCustom').click(function (e)
		{
			e.preventDefault();
			$('#customReportInput-container').toggle();
		});

		$(document).on('click', '#btnPrint', function (e)
		{
			e.preventDefault();

			var url = opts.printUrl + elements.customReportForm.serialize();
			window.open(url);
		});

		$(document).on('click', '#btnCsv', function (e)
		{
			e.preventDefault();

			var url = opts.csvUrl + elements.customReportForm.serialize();
			window.open(url);
		});

		$(document).on('click', '#btnSaveReportPrompt', function (e)
		{
			e.preventDefault();

			elements.saveDialog.find(':text').val('');
			elements.saveDialog.modal('show');
		});

		$(document).on('click', '#btnChart', function (e)
		{
			e.preventDefault();

			var chart = new Chart();
			chart.generate();
			$('#report-results').hide();
		});

		$('.save').on('click', function ()
		{
			$(this).closest('form').submit();
		});

		ConfigureAsyncForm($('#saveReportForm'), function ()
		{
			return opts.saveUrl;
		}, function (data)
		{
			elements.saveDialog.modal('hide');
			$('#saveMessage').show().delay(3000).fadeOut(1000);
		});

		//$('#saveReportForm').submit(function (e) {
		//	handleSave(e);
		//});
		//
		//$('#btnSaveReport').click(function (e) {
		//	handleSave(e);
		//});
	};

	function wireUpAutocompleteFilters()
	{

		var selectFilterItem = function (filterDiv, selectedId, selectedName)
		{
			filterDiv.find('.filter-id').val(selectedId);
			filterDiv.find('.selected').text(selectedName).show();
			filterDiv.find('.filter-input').hide();
		};

		$("#user-filter").userAutoComplete(opts.userAutocompleteUrl, function (ui)
		{
			selectFilterItem($('#user-filter-div'), ui.item.value, ui.item.label);
		});

		$("#participant-filter").userAutoComplete(opts.userAutocompleteUrl, function (ui)
		{
			selectFilterItem($('#participant-filter-div'), ui.item.value, ui.item.label);
		});
	}

	//
	//var handleSave = function (e) {
	//	e.preventDefault();
	//	var before = function () {
	//	};
	//
	//	var after = function (data) {
	//		elements.saveDialog.modal('hide');
	//		$('#saveMessage').show().delay(3000).fadeOut(1000);
	//	};
	//
	//	ajaxPost($('#customReportInput, #saveReportForm'), opts.saveUrl, before, after);
	//};

}