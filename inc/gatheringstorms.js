var loadingWidget = "<img src=\"/img/loading.gif\" vspace=\"10\">"
var savingWidget = "<img src=\"/img/loading.gif\" vspace=\"10\">"

// variable javascript collection for all pages
var UniversalHFH = {
	newCounter: 0,

	// calculate bonus of given ability score
	addNewCounter: function ()
	{
		this.newCounter--
		return this.newCounter
	},
}

function swapSection(sName)
{
	temp = document.getElementById(sName + "Read").style.display
	document.getElementById(sName + "Read").style.display = document.getElementById(sName + "Edit").style.display
	document.getElementById(sName + "Edit").style.display = temp
}

function saveSection(sName, params)
{
	document.getElementById(sName + "Read").innerHTML = savingWidget
	document.getElementById(sName + "Edit").style.display = "none"
	document.getElementById(sName + "Read").style.display = "block"
	buildSection(sName, params)
}

function reloadSection(sName)
{
	document.getElementById(sName + "Read").innerHTML = loadingWidget
	document.getElementById(sName + "Edit").style.display = "none"
	document.getElementById(sName + "Read").style.display = "block"
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
//JS							document.getElementById(sectionName).innerHTML = this.responseText
					$('#'+sectionName).html(this.responseText)
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
	var x = document.getElementById(sName + "Form")
//			var x = $("#" + sName + "Form")
	for(var i = 0, text = ""; i < x.length; i++) {
		if(x.elements[i].id > "") {
	    	if(x.elements[i].multiple) {
					for(var j = 0, tmpValue = ""; j < x.elements[i].selectedOptions.length; j++) {
						tmpValue += x.elements[i].selectedOptions[j].value + ","
					}
		    	text += x.elements[i].id + "=" + tmpValue + "&"
	    	}
	    	else {
		    	text += x.elements[i].id + "=" + x.elements[i].value + "&"
	    	}

// Find out if more than one option in a drop-down list can be selected:
// var x = document.getElementById("mySelect").multiple;

// HTMLSelectElement.selectedOptions Read only
// Returns a live HTMLCollection containing the set of options that are selected.
	    }
	}
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
