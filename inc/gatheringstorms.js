var loadingWidget = "<img src=\"/img/loading.gif\" vspace=\"10\">"
var savingWidget = "<img src=\"/img/loading.gif\" vspace=\"10\">"

// variable javascript collection for all pages
var universalHFH = {
	newCounter: 0,
	varEditing: 0,
	varWarning: 0,

	// calculate bonus of given ability score
	addNewCounter: function ()
	{
		this.newCounter--
		return this.newCounter
	},

	// track number of editting sections and warn if too many are open
	addEdit: function ()
	{
		this.varEditing++
		if(this.varEditing > 1 && !this.varWarning) {
			alert('You are editing multiple sections.\nPlease save any previous edits to avoid losing them.')
			this.varWarning = 1
		}
	},
	removeEdit: function ()
	{
		this.varEditing--
	},
}

function editSection(sName)
{
	$('#' + sName + 'Edit').clone().appendTo($('#' + sName + 'Edit').parent())
		.attr('id', sName + 'EditBackup')
	$('#' + sName + 'Read').hide()
	$('#' + sName + 'Edit').show()
	universalHFH.addEdit()
}

function abortSection(sName)
{
	$('#' + sName + 'Edit').hide()
	$('#' + sName + 'Read').show()
	if($('#' + sName + 'EditBackup')) {
		$('#' + sName + 'Edit').remove()
		$('#' + sName + 'EditBackup').attr('id', sName + 'Edit')
	}
	universalHFH.removeEdit()
}

function saveSection(sName, params)
{
	$('#' + sName + 'Read').html(savingWidget)
	$('#' + sName + 'Edit').hide()
	$('#' + sName + 'Read').show()
	buildSection(sName, params)
	universalHFH.removeEdit()
}

function reloadSection(sName)
{
	$('#' + sName + 'Read').html(loadingWidget)
	$('#' + sName + 'Edit').hide()
	$('#' + sName + 'Read').show()
	buildSection(sName)
}

function buildSection(sName, params)
{
	$.ajax({
		url: location.pathname.substring(0, location.pathname.indexOf(".")) + sName.toLowerCase() + "/?id=" + pkid,
		type: "POST",
		data: params,
		contentType: 'application/x-www-form-urlencoded',
		cache: false,
		processData: true,
		success: function(e)
		{
			$('#' + sName.toLowerCase() + "Section").html(e)
		},
		error: function(e)
		{
			alert('ERROR\n' + printobj(e) + '\nSection Name: ' + sName + '\nParams: ' + printobj(params));
		},
	});
}

function serializeParams(sName)
{
	// var text = ""
	var params = {}
	$('#' + sName + 'Edit > #' + sName + 'Form *:input').each(function() {
			if($(this).prop('id')) {
				// text += $(this).prop('id') + '=' + $(this).val() + '&'
				params[$(this).prop('id')] = $(this).val()
			}
		})
	// return text
	return params
}

function printobj(obj)
{
	var map = ""
	for(var p in obj) {
		if(typeof obj[p] == 'string') {
			map += p + " = " + obj[p] + "\r\n"
		}
		if(typeof obj[p] == 'array') {
			map += p + " = array(\r\n" + printobj(obj[p]) + ")\r\n"
		}
		if(typeof obj[p] == 'array') {
			map += p + " = array(\r\n" + printobj(obj[p]) + ")\r\n"
		}
	}
    return map
}

function copyFromTemplate(paramColumnName)
{
	// create unique ID number for new record
	var varNewID = universalHFH.addNewCounter()

	// clone template for new record, change ID, make visible, and append after add button
	$('#div' + paramColumnName + '0').clone()
		.attr('id', 'div' + paramColumnName + '0' + varNewID)
		.appendTo($('#div' + paramColumnName + 'addnew'))
		.css('display', 'inline')

	// same ID change to all child elements
	var varTemplateClone = $('#div' + paramColumnName + '0' + varNewID + ' > *')
	for(var i = 0; i < varTemplateClone.length; i++){
		if(varTemplateClone[i].id) {
			varTemplateClone[i].id += varNewID
		}
	}

	// this spacer is added between the clones to keep them from spanning multiple lines
	$('#spacer').clone().appendTo($('#div' + paramColumnName + 'addnew'))
}
