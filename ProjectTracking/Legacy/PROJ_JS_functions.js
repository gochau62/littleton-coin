function saveProjChanges() {
//	alert("Saving Changes");
//	validate form
	if (whichEditor.length == 1) {
		alert("Please ACCEPT text box entries before clicking SAVE button.");
		return;
	}
	for (var i = 0; i < whichEditor.length; i++) {
		if (whichParms[i] != null && whichParms[i] != ' ') { 
				WN_saveNote(whichEditor[i], whichParms[i]); 
				logProjComment("save", whichParms[i]);
		}
		
		whichEditor[i] = ' ';
		whichParms[i] = ' ';
	}
//	submit form
	document.forms.projForm.submit();
//	alert("Changes Saved");
}
function activateSave() {
	document.getElementById('saveButton').disabled = false;
	document.getElementById('cancelButton').disabled = false;
}

function cancelProjChanges(proj) {
	alert("Changes discarded");
	
	window.location = 'PROJ_ctl.php?projnum='+proj;
}

function projSubmitProjList() {
	document.forms["projList"].submit();
}

function flipShowHide(action, editorDiv, aDiv, parms) {
	// execute selected action
	var reloadPage = false;
	
	switch(action) {
	case "edit":
		needToConfirm = true;
		whichEditor.push(editorDiv);
		whichParms.push(parms);
		WN_createEditor(editorDiv);
		break;
	case "save":
		var modePos = parms.indexOf("mode=") + 5;
		var modeText = parms.substr(modePos, 3);
		needToConfirm = true;
		whichEditor.push(editorDiv);
		whichParms.push(parms);
		saveProjChanges();
		// if (modeText == "add" && editorDiv != 'projDesc') {
			// reloadPage = true;
		// }
		
		break;
	case "delete":
		WN_deleteNote(editorDiv, parms);
		logProjComment(action, parms);
		reloadPage = true;
		break;
	case "cancel":
		WN_cancelEdit(editorDiv);
		break;
	}
	
	// get 'a' children of aDiv and change their class
	var linkDiv = document.getElementById(aDiv);
	
	var linkDiva = linkDiv.getElementsByTagName("a");
	
	for (var i = 0; i < linkDiva.length; i++) {
		
		if (linkDiva[i].className == "hidden") {
			linkDiva[i].className = "show";
		} else {
			linkDiva[i].className = "hidden";
		}
	}
	
	if (reloadPage) {
		window.location.reload();
	}
}

// In Queue shows its scheduled start date beside the status
function wrkStsChanged() {
	var sel = document.getElementById('projWrkSts');
	var wrap = document.getElementById('inqDateWrap');
	if (!sel || !wrap) { return; }
	wrap.style.display = (sel.value == 'INQ') ? 'inline' : 'none';
}

// the queue date is the project's scheduled start date
function queueDateChanged() {
	var q = document.getElementById('projQueueDate');
	var s = document.forms.projForm ? document.forms.projForm.projSchdStart : null;
	if (!q || !s || q.value == '') { return; }
	var p = q.value.split('-');
	if (p.length == 3) { s.value = p[1] + '/' + p[2] + '/' + p[0]; }
	if (typeof activateSave == 'function') { activateSave(); }
}

// programmers panel: post a change, swap in the panel that comes back
function pgmrPost(data) {
	data.projNum = document.getElementById('projectNumber').value.trim();
	jQuery.post('PROJ_ajax_request_post.php', data, function (html) {
		if (String(html).indexOf('ERROR:') === 0) {
			swal('Programmer update failed', String(html).substr(6), 'error');
			return;
		}
		jQuery('#pgmrPanel').replaceWith(html);
	}).fail(function () {
		swal('Programmer update failed', 'Server error, refresh the page and try again', 'error');
	});
}

function pgmrAdd() {
	var p = jQuery('#pgmrAdd').val();
	if (!p) { return; }
	pgmrPost({ action: 'pgmrSave', pgmr: p, sts: '', date: '' });
}

function pgmrSave(p) {
	var row = jQuery('#pgmrPanel tr[data-pgmr="' + p + '"]');
	pgmrPost({ action: 'pgmrSave', pgmr: p,
	           sts: row.find('.pgmrSts').val() || '',
	           date: row.find('.pgmrDate').val() || '' });
}

function pgmrRemove(p) {
	swal({ title: 'Remove ' + p + ' from this project?',
	       text: 'Their comments stay on file.', type: 'warning',
	       showCancelButton: true, confirmButtonText: 'Remove' },
	     function (yes) { if (yes) { pgmrPost({ action: 'pgmrRemove', pgmr: p }); } });
}

function pgmrCommentAdd(p) {
	var t = jQuery('#pgmrCmtTxt_' + p).val().trim();
	if (t == '') { return; }
	pgmrPost({ action: 'pgmrCommentAdd', pgmr: p, text: t });
}

function pgmrCommentRemove(seq) {
	swal({ title: 'Remove this comment?', type: 'warning',
	       showCancelButton: true, confirmButtonText: 'Remove' },
	     function (yes) { if (yes) { pgmrPost({ action: 'pgmrCommentRemove', seq: seq }); } });
}

function chgProgrammer(){

//	alert("NOTICE: Changing programmer also automatically\nchanges developer rate on Pay Back tab.")
	
	var selectedPgmr = document.getElementById("projProgrammer").value;
	
	var url = "PROJ_ajax_request.php";
	var rand = parseInt(Math.random()*9999999999);
	// add random number to url to avoid cache problems
	var modurl = url + "?rand=" + rand + "&action=devgrplist&pgmr=" + selectedPgmr;
	
	var http = getXMLHTTPRequest();	
	http.open("GET", modurl, true);
//	// set up the call back function
	http.onreadystatechange = function() 
		{
			if (http.readyState == 4) {
				if (http.status == 200) {
					var xmlDoc= http.responseXML.documentElement;
					try {
						var rate = xmlDoc.getElementsByTagName("devrate")[0].childNodes[0].nodeValue;
					}
					catch(err) {
						alert("No developer rate was found. Defaulting to $75");
						var rate = 75;
					}
					document.getElementById("projDevRate").value = rate; 
					projCalcPayback();
				}
			}
		}
		
	http.send(null);
	
	
}

function chgEstimator(){

	var selectedPgmr = document.getElementById("projEstimator").value;
	
	var url = "PROJ_ajax_request.php";
	var rand = parseInt(Math.random()*9999999999);
	// add random number to url to avoid cache problems
	var modurl = url + "?rand=" + rand + "&action=devgrplist&pgmr=" + selectedPgmr;
	
	var http = getXMLHTTPRequest();	
	http.open("GET", modurl, true);
//	// set up the call back function
	http.onreadystatechange = function() 
		{
			if (http.readyState == 4) {
				if (http.status == 200) {
					var xmlDoc= http.responseXML.documentElement;
					
					var devGrpSelected = xmlDoc.getElementsByTagName("group")[0].childNodes[0].nodeValue;
					var devGrpSelect = document.getElementById('projDevGrp');
					var devGrpOption = devGrpSelect.getElementsByTagName("option");
					
					for (var i = 0; i < devGrpOption.length; i++) {
						
						if (devGrpOption[i].value == devGrpSelected) {
							devGrpOption[i].selected = "selected";
						} else {
							devGrpOption[i].selected = null;
						}
					}
				}
			}
		}
		
	http.send(null);
	
	
}

function selectFire() {
	
	for (var i=0; i < document.projForm.tstType.length; i++) {
	   if (document.projForm.tstType[i].checked) {
	      var chkRadio = document.projForm.tstType[i].value;
	   }
	}

	
//	var chkRadio = document.getElementById('tstType');
	if (chkRadio == 'fire') {
		var opt = "FR";
		alert("Project type set to 'Fire'");
	} else {
		var opt = "";
	}
	
	if (chkRadio == 'regular') {
		alert("This is now a 'standard' project.");
	}

	if (chkRadio == 'anualPlan') {
		alert("Project flagged for annual planning only.");
	}
	
	var sltType = document.getElementById('projtype');
	var sltTypeOptions = sltType.getElementsByTagName('option');
	
	for (var i = 0; i < sltTypeOptions.length; i++) {
		
		if (sltTypeOptions[i].value == opt) {
			sltTypeOptions[i].selected = "selected";
		} else {
			sltTypeOptions[i].selected = null;
		}
	}

}

function reloadSubDept(deptId, subDeptId, all) {
	// "all" is optional. 
	// If "all" is passed in, an "all" option will be added to the list
	if (!all) {
		var all = "";
	}
	
	var selectedDept = document.getElementById(deptId).value;
	
	var url = "PROJ_ajax_request.php";
	var rand = parseInt(Math.random()*9999999999);
	// add random number to url to avoid cache problems
	var modurl = url + "?rand=" + rand + "&action=subdeptlist&dept=" + selectedDept;
	

	var http = getXMLHTTPRequest();	
	http.open("GET", modurl, true);
//	// set up the call back function
	http.onreadystatechange = function() 
		{
			if (http.readyState == 4) {
				if (http.status == 200) {
					var optString = decodeURIComponent(http.responseText);
					optString = optString.replace(/\+/g, " ");
					if (all != "") {
						optString = "<option value=\"All\" selected>All</option>" + optString;
					}
					document.getElementById(subDeptId).innerHTML = optString;
				}
			}
		}
		
	http.send(null);
}

function logProjComment(action, parms) {
	if (parms.charAt(0) != "&") {
		parms = "&" + parms;
	}
	
	var url = "PROJ_ajax_request.php";
	var rand = parseInt(Math.random()*9999999999);
	// add random number to url to avoid cache problems
	var modurl = url + "?rand=" + rand + "&action=logcomment" + parms;
	

	var loghttp = getXMLHTTPRequest();	
	loghttp.open("GET", modurl, false); 
//	// set up the call back function
//	loghttp.onreadystatechange = function() 
//		{
//			if (loghttp.readyState == 4) {
//				if (loghttp.status == 200) {
//					alert("Your change has been logged");
////					var optString = decodeURIComponent(http.responseText)
////					optString = optString.replace(/\+/g, " ");
////					document.getElementById("projRqstSubDept").innerHTML = optString;
//				}
//			}
//		}
		
	loghttp.send(null);
}

function goToProject()  {
	var proj = document.getElementById('projectNumber').value;
	if (isNaN(proj)) {
		alert('Please enter a numeric value.')
	} else {
		window.location = 'PROJ_ctl.php?projnum='+proj;
	}
}

function addProjToTimeList() {
	var toAdd = document.getElementById('projToAdd').value;
	if (!isNaN(toAdd) && (toAdd > 0)) {
		window.location = "PROJ_timeEntry_ctl.php?addproj="+toAdd;
	} else {
		alert("Please enter a valid projectnumber.");
	}
}

function projEnterTime(postString) {
	var http = getXMLHTTPRequest();
	var url = "PROJ_saveTime.php";

	// use encodeURIComponent to encode special characters
	// var html = encodeURIComponent(document.getElementById( editorVar ).innerHTML);

	//var postParms = "proj="+timePrNum+"&date="+timeDate+"&time="+timeTime;
	myRand = parseInt(Math.random()*999999999);
	var postString = postString +"&rand="+myRand;
	http.open("POST", url, true);

//	Send the proper header information along with the request
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.setRequestHeader("Content-length", postString.length);
	http.setRequestHeader("Connection", "close");

	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 && http.status != 200) {
			alert("An error occurred while updating your\ntime. Message: "+http.responseText);
			return;
//			location.reload(true);
		}
	}
	http.send(postString);
}

function projCalcPayback() {
	devRate = document.getElementById('projDevRate').value;
	origHiEst = document.getElementById('origHiEst').innerHTML;
	curHiEst = document.getElementById('curHiEst').innerHTML;
	
	origDevCost = devRate * origHiEst;
	curDevCost = devRate * curHiEst;

	orig1TimeCost = document.getElementById('orig1TimeCost').value;
	orig1TimeSav = document.getElementById('orig1TimeSav').value;
	origAnnualCost = document.getElementById('origAnnualCost').value;
	origAnnualSav = document.getElementById('origAnnualSav').value;
	
	orig1TimeValue = orig1TimeCost - orig1TimeSav;
	origAnnualValue = origAnnualSav - origAnnualCost;
	
//	document.getElementById('origPayback').innerHTML = ((origDevCost + orig1TimeValue) / origAnnualValue).toFixed(0);
	var original = Math.ceil(((origDevCost + orig1TimeValue) / origAnnualValue) * 12);
	if (isNaN(original) || original.length > 5) {
//		document.getElementById('origPayback').innerHTML = "?????";
		document.getElementById('origPayback').value = "?????";
	} else {
//		document.getElementById('origPayback').innerHTML = original;
		document.getElementById('origPayback').value = original;
	}
	
	cur1TimeCost = document.getElementById('cur1TimeCost').value;
	cur1TimeSav = document.getElementById('cur1TimeSav').value;
	curAnnualCost = document.getElementById('curAnnualCost').value;
	curAnnualSav = document.getElementById('curAnnualSav').value;
	
	cur1TimeValue = cur1TimeCost - cur1TimeSav;
	curAnnualValue = curAnnualSav - curAnnualCost;
	
//	document.getElementById('curPayback').innerHTML = ((curDevCost + cur1TimeValue) / curAnnualValue).toFixed(0);
	var current = Math.ceil(((curDevCost + cur1TimeValue) / curAnnualValue) * 12);
	if (isNaN(current)  || current.length > 5) {
//		document.getElementById('curPayback').innerHTML = "?????";
		document.getElementById('curPayback').value = "?????";
	} else {
//		document.getElementById('curPayback').innerHTML = current;
		document.getElementById('curPayback').value = current;
	}
	
}

function projUpdCurr() {
	document.getElementById('cur1TimeCost').value = document.getElementById('orig1TimeCost').value;
	document.getElementById('cur1TimeSav').value = document.getElementById('orig1TimeSav').value;
	document.getElementById('curAnnualCost').value = document.getElementById('origAnnualCost').value;
	document.getElementById('curAnnualSav').value = document.getElementById('origAnnualSav').value;
}

function showHideUsrAcpt(user) {
	if (document.getElementById('PRUSRACPT').checked == true) {
		var currentTime = new Date()
		var month = currentTime.getMonth() + 1
		var day = currentTime.getDate()
		var year = currentTime.getFullYear()

		document.getElementById('acceptDiv').innerHTML = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Accepted by " + user + " on " + month + "/" + day + "/" + year;
	//	document.getElementById('PRUSRACPT').checked = false;
	} else {
		document.getElementById('acceptDiv').innerHTML = "";
	//	document.getElementById('PRUSRACPT').checked = true;
	}
}

function sbmtSCReview() {
	document.getElementById('scReviewConf').style.visibility = "visible";
	
	var url = "PROJ_ajax_request.php";
	var rand = parseInt(Math.random()*9999999999);
	// add random number to url to avoid cache problems
	var modurl = url + "?rand=" + rand + "&action=updatescreview";
	
	var http = getXMLHTTPRequest();	
	http.open("GET", modurl, true);
//	// set up the call back function
	http.onreadystatechange = function() 
		{
			if (http.readyState == 4) {
				if (http.status == 200) {
					alert("All is well");
				}
			}
		}
		
	http.send(null);
	
}

function reloadLnkDocList(prefix, idval) {
	
	var http = getXMLHTTPRequest();
	var url = "Utils/common_ajax_request.php";
	var postString = "action=buildLnkDocList&prefix=" + prefix + "&idval=" + idval;
	
	myRand = parseInt(Math.random()*999999999);
	postString = postString + "&rand=" + myRand;
	http.open("POST", url, false);

//	Send the proper header information along with the request
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.setRequestHeader("Content-length", postString.length);
	http.setRequestHeader("Connection", "close");

	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 && http.status == 200) {
			var xmlDoc= http.responseXML.documentElement;
			var rtnMsg = xmlDoc.getElementsByTagName("rtnmsg")[0].textContent;
//			alert (rtnMsg);
			rtnMsg = decodeURIComponent(rtnMsg);
//			alert (rtnMsg);
			
			rtnMsg = rtnMsg.replace(/\+/g, " ");
//			alert (rtnMsg);
			
			if (rtnMsg != "Delete Failed") {
				// remove element from html document
			}
			
			document.getElementById("linkedDocs").innerHTML = rtnMsg;
			
		}
	}
	http.send(postString);
}
