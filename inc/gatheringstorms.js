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
	if (!params) {
		params = ""
	}
	sectionName = sName.toLowerCase() + "Section"
	sectionFilename = location.pathname.substring(0, location.pathname.indexOf(".")) + sName.toLowerCase() + "/?id=" + pkid
	request = Array();
	request[sName] = new ajaxRequest()
	request[sName].open("POST", sectionFilename, true)
	request[sName].setRequestHeader("Content-type", "application/x-www-form-urlencoded")
//	request[sName].setRequestHeader("Content-length", params.length)
//	request[sName].setRequestHeader("Connection", "close")
	request[sName].onreadystatechange = function()
	{
		if (this.readyState == 4) {
			if (this.status == 200) {
				if (this.responseText != null) {
					$('#' + sectionName).html(this.responseText)
				} else alert("AJAX Response: NULL\r\n" + sectionFilename)
			} else alert("AJAX Error Status: " + this.status + "\r\n" + sectionFilename)
		}
	}
	request[sName].send(params)
}

function ajaxRequest()
{
	try {
		var request = new XMLHttpRequest()
	}
	catch(e1) {
		try {
			request = new ActiveXObject("Msxml2.XMLHTTP")
		}
		catch(e2) {
			try {
				request = new ActionXObject("Microsoft.XMLHTTP")
			}
			catch(e3) {
				request = false
			}
		}
	}
	return request
}

function ajaxRequestNEW()
{
	var XMLHttpRequestObjects = new Array()

	try {
		XMLHttpRequestObjects.push(new XMLHttpRequest())
	}
	catch(e1) {
		try {
			XMLHttpRequestObjects.push(new ActiveXObject("Msxml2.XMLHTTP"))
		}
		catch(e2) {
			try {
				XMLHttpRequestObjects.push(new ActionXObject("Microsoft.XMLHTTP"))
			}
			catch(e3) {
				XMLHttpRequestObjects.push(false)
			}
		}
	}
	return XMLHttpRequestObjects.pop()
}

function serializeParams(sName)
{
	var text = ""
	$('#' + sName + 'Edit > #' + sName + 'Form *:input').each(function() {
			if($(this).prop('id')) {
	    	text += $(this).prop('id') + '=' + $(this).val() + '&'
	    }
		})
	return text
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
