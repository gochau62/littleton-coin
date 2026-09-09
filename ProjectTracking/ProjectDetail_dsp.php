<?php 
	/*************************************************  
	* Page Name - PROJ_dsp.php                       *
	* Narrative - Project tracking entry and         *
	*             mainenance                         *
	* Author    - D Whitehead                        *
	*             Littleton Coin Company             *
	*             Littleton NH                       *
	* Date Written 02/25/2011                        *
	**************************************************/
?>
<!--  Begin Content Here -->
<?php 
function showProjPrompt() {
?>
	<div id='stdPage'>
	<h1><i>Know which project you want?</i></h1>
	<h2><u>Go to project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6'/></h2>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjNotFound() {
?>
	<div id='stdPage'>
	<h1 style='color:red'><i>Project not found</i></h1>
	<h2><u>Project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6'/></h2>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjectDetailScreen(&$screenData) {
?>
	<div id='stdPage'>
	
	<div style='text-align: left; width: 65%; float: left; vertical-align:bottom;'>
	<h2 style='float: left'><u>Project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6' value='<?php echo $screenData['PR#']?>'/></h2>
	<div style='padding-top: 8px'>&nbsp;&nbsp;<?php echo $screenData['PRDESC']?></div>
	<br/>
	<br/>
	</div>
	<div style='text-align: right; width: 30%; float: left;'>
	<a href="PROJ_print_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">Print  </a>
	<?php echo $screenData['saveButton']?>
	<?php echo " " . $screenData['cancelButton']?>
<!--	<input type='button' value='Save Changes' onclick='saveProjChanges()'/>-->
	</div>
	
	<br/>
	<br/>
	<br/>
	
	<div style='text-align: left;' class='tabArea' id='PROJ_mainTabs'>
	<br/>
	<?php
			echo "<a id='tabGeneral' href=\"javascript:switchTab('PROJ_mainTabs', 'tabGeneral', 'pageSection', 'general');\">General</a>";
			echo " <a id='tabIt' href=\"javascript:switchTab('PROJ_mainTabs', 'tabIt', 'pageSection', 'itStuff');\">IT Stuff</a>";
			echo " <a id='tabPayBack' href=\"javascript:switchTab('PROJ_mainTabs', 'tabPayBack', 'pageSection', 'payBack')\">Payback</a>";
			echo " <a id='tabStrComm' href=\"javascript:switchTab('PROJ_mainTabs', 'tabStrComm', 'pageSection', 'strComm')\">Steering Committee</a>";
	?>
	</div>

<div id='general' class='pageSection' style="display:block">
<form id='projForm' name='projForm' action='PROJ_save.php' method="post">
	<input type='hidden' name='projnum' value='<?php echo $screenData['PR#']?>' />
	<input type='hidden' id='hiddenUser' value='<?php echo strtoupper($_SESSION['username'])?>' /> 
	<input type='hidden' id='hiddenUserType' value='<?php echo strtoupper($_SESSION['username'])?>' />
	<input type='hidden' id='hiddenUserClass' value='<?php echo strtoupper(trim($_SESSION['usrclass']))?>' />
	 
	<br/>

	<span onmouseover="tooltip.show('Descriptive, accurate, clear and short. 50 Characters max.');" onmouseout="tooltip.hide();">
		<img src='images/Info_icon_20px.png' height='15' width='15' />
	</span>	

	<label>Project Name:</label> 

	<input type="text" id="projName" name="projName" size="60" maxlength="50"
		value="<?php echo $screenData['PRDESC']?>"/>

	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRDescrip']?>
	<label>Description:</label> <?php echo $screenData['projDesc'];?>
	
	
	<?php 
	If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) {
	   echo '<br/>';
	   echo '<font size="+1">';
	   echo 'Parent Project:';
	}
	?>
	&nbsp;
	<?php
	If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) { 
	   echo $screenData['PRRELPRJ#'];
	}
	?>
	&nbsp;
    <?php 
    If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) {
       echo $screenData['parentProjDesc']; 
       echo '<br/>';
    }
    ?>
    </font>
    
	<font size="+1">
	<?php
    If ($screenData['childrenProjects'] != ' ' and $screenData['childrenProjects'] != null) {
        
        echo "<br/>";
        echo 'Children Projects:';
        
    }
    ?>
    </font>
    <?php 
    If ($screenData['childrenProjects'] != ' ' and $screenData['childrenProjects'] != null) {
        echo '<font size="+1">';
        echo $screenData['childrenProjects'];
        echo '</font>';
    }
    ?>
	<br/>
	<?php echo $screenData['toolTip']['LNKDOC']?>
	<label>Attached Documents:</label><br/>
	<div id="linkedDocs"><?php echo $screenData['linkedDocs'];?></div>
	
	<br/>
	<input type='button' onclick="javascript:popupWindow('LNKDOC_upload_ctl.php?prefix=PROJ_&idval=<?php echo $screenData['PR#']?>', 
		'Upload Doc', 400)" value="Attach a Document" />
	
	<hr/>
	
	<br/>
	<br/>
		
	<?php echo $screenData['tstType']?>
	
	<br/>	
	<br/>	
	
	<?php echo $screenData['toolTip']['PRRQST']?>
	<label>Requestor: </label> 
		<?php echo " " . $screenData['PRRQST']?>
	
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		
	<label>Created Date:</label>
	<?php echo $screenData['html']['PRSUBD']?>
	<br/>
	<br/>
		
	<?php echo $screenData['toolTip']['PRSPONSR']?>
	<label>Sponsor:</label> 
	<?php echo $screenData['PRSPONSR'] ?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRSPAPVDTE']?>
	<label>Sponsor Approval Date:</label>
	<?php echo $screenData['html']['PRSPAPVDTE']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRNEED']?>
	<label>Need By Date:</label> 
	<?php echo $screenData['html']['PRNEED']?>
	<!-- kjr -->
	&nbsp;&nbsp;&nbsp;
	
	<label>Justification Type:</label>
	<?php echo $screenData['html']['PJDESC'] ?>
	<br/>
	<br/>
	<!-- kjr -->	
		
	<label>Requesting Department:</label> 
	<?php echo $screenData['html']['PRDEPT']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Sub Dept:</label> 
	<?php echo $screenData['html']['PRSUBDEPT']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRUPTY']?>
	<span onmouseover="tooltip.show(&apos; 1 - Needs to be completed in 1 to 3 months <br> 2 - Needs to be completed in 3 to 6 months <br> 3 - Needs to be completed in 6 months to a year <br> 4 - On Hold <br> 5 -> 8 - Not Used <br> 9 - Default (has not been changed since project creation) &apos;)" onmouseout="tooltip.hide();">
		<img src="images/Info_icon_20px.png" width="15" height="15">
		</span>
	<label>Department Priority:</label> 
	<input size='2' maxlength='1' type='text' name='projUsrPrty' onchange='activateSave()' value='<?php echo $screenData['PRUPTY']?>' />
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRUSRACPT']?>
	<label>Project Acceptance:</label> 
	<?php echo $screenData['html']['PRUSRACPT']?> By checking this box the user agrees the project is complete and is ready for implementation.
	<br/>
	<div id='acceptDiv'><?php echo $screenData['acceptText']?></div>
<!--	<br/>-->
	<br/>
	
	<a href="PROJ_allComntView_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">View All Comments</a>
	<br/>
	
	<?php echo $screenData['toolTip']['GenCommnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntGen'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	
<!--</form> -->
</div><!-- general -->	

<div class='pageSection' id='itStuff' style="display:none">
<!--<form id='itStuffForm'>-->
	<br/>
	<?php echo $screenData['estLink']?>
	<br/>
	<label>Assigned Estimator:</label> 
	<?php echo $screenData['html']['PRESTMTR']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<label>Dev Group</label>
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['html']['PRITDEVGRP']?>
	<br/>
	<br/>
	<label>Original estimate:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo "<span><div class='data' id='origHiEst'>" . $screenData['origHiEst'] . "</div>"?>
				<small>Originaly estimated on <?php echo " " . $screenData['origEstDate'] . 
				" by " . $screenData['origEstimator'] .
				"</small></span>"?>
	<br/>
	<br/>


	<label>Current low estimate:</label> 
	<?php echo "<div class='data' id='CurLowEst'>" . $screenData['curLowEst'] . "</div>"?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['toolTip']['CurHiEst']?>
	<label>Current hi estimate:</label> 
	<?php echo "<div class='data' id='curHiEst'>" . $screenData['curHiEst'] . "</div>"?>
	<br/>
<!--	<br/>-->
	<small>
	current estimate was done on <?php echo " " . $screenData['curEstDate'] . " by " . $screenData['curEstimator']?>
	</small>
	<br/>
	<br/>
	
	<label>Brand:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['PRBRAND']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Parent Project:</label> 
	&nbsp;
	<input size='6' maxlength='6' type='text' name='parentProj' value='<?php echo $screenData['PRRELPRJ#']?>' />
	&nbsp;
	<font size="+1">
	<?php echo $screenData['parentProjDesc'] //wrap key in quotes to prevent undefined constant warning - 06-28-22 - kjr ?>
	</font>
	
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRPGMR']?>
	<label>Programmer assigned:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['PRPGMR']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRWRKSTS']?>
	<label>Programmer work status: </label>
	<?php echo $screenData['html']['PRWRKSTS']?>
	<br/>
	
	<br/>
	<label>Programmer time to date:</label>
	<?php echo " " . $screenData['pgmrTime']?>
	<br/>
	<br/>
			
	<label>Scheduled start date:</label> 
	<?php echo $screenData['html']['PRESTR']?>
<!--	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
	<br/>
	<br/>
	
	<label>Scheduled implementation date:</label> 
	<?php echo $screenData['html']['PRECOM']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Actual implementation date:</label> 
	<?php echo $screenData['html']['PRACOM']?>
	<br/>
	<br/>
	<fieldset id = 'lgndFldSet'>
	<legend id="lgndRetailRange">Action Items:</legend>
	<div id='divRetailRange'>
	<input id="addActInfo" type="button" value="Add Action"> 
	<div id='actDtl' style="width: 700px; padding: 25px; ">
    <ul></ul>
	</div>
	<?php //echo $screenData['instOrd']['RETRANGE']; ?>
	<br />
	</div>
	</fieldset>
	<br/>

<!--	<label>Implemented date:</label> -->
<!--	<php echo $screenData['html']['PRIMPDTE']?>-->
<!--	<br/>-->
<!--	<br/>-->
	
	<?php echo $screenData['toolTip']['ITCommnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntIT'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
<!--</form> -->
</div> <!-- itStuff  -->

<div class='pageSection' id='payBack' style="display:none">
<!--<form id='payBackForm'>-->
	<br/>
	<?php echo $screenData['toolTip']['PRPAYBKTYP']?>
	<label>Payback type: </label>
	<?php echo $screenData['html']['PRPAYBKTYP']?>

	<br/>
	<br/><label>Developer rate: </label>
	<?php echo $screenData['html']['PRDRAT']?>
	
	<br/><br/>
	<table>
		<CAPTION>
  			Payback data
		</CAPTION>
		<tr>
			<th class='blank'></th>
			<th>Original</th>
			<th>Current</th>
		</tr>
		<tr>
			<td>Developer cost</td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='origDevCost' name='origDevCost' disabled value='<?php echo $screenData['origDevCost']?>'></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='developerCost' name='developerCost' disabled value='<?php echo $screenData['developerCost']?>'></td>
			
		</tr>
		<tr>
			<td>One-time cost <?php echo $screenData['toolTip']['1TimeCost']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='orig1TimeCost' name='orig1TimeCost' value='<?php echo $screenData['PROCST1']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5'  id='cur1TimeCost' name='cur1TimeCost' value='<?php echo $screenData['PRCCST1']?>' /></td>
		</tr>
		<tr>
			<td>Annual cost <?php echo $screenData['toolTip']['AnnualCost']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='origAnnualCost' name='origAnnualCost' value='<?php echo $screenData['PROCSTA']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='curAnnualCost' name='curAnnualCost' value='<?php echo $screenData['PRCCSTA']?>' /></td>
		</tr>
		<tr>
			<td>One-time savings <?php echo $screenData['toolTip']['1TimeSavng']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='orig1TimeSav' name='orig1TimeSav' value='<?php echo $screenData['PROSAV1']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='cur1TimeSav' name='cur1TimeSav' value='<?php echo $screenData['PRCSAV1']?>' /></td>
		</tr>
		<tr>
			<td>Annual savings <?php echo $screenData['toolTip']['AnnualSavn']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='origAnnualSav' name='origAnnualSav' value='<?php echo $screenData['PROSAVA']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='curAnnualSav' name='curAnnualSav' value='<?php echo $screenData['PRCSAVA']?>' /></td>
		</tr>
		<tr>
			<td>Payback <?php echo $screenData['toolTip']['PayBack']?></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='origPayback' name='origPayback' readonly value='<?php echo $screenData['PROPYBK']?>' /></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='curPayback' name='curPayback'readonly value='<?php echo $screenData['PRCPYBK']?>' /></td>
		</tr>
	</table>
		
	<br/>
	<br/>
	<?php echo $screenData['toolTip']['PBComnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntPB'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	<br/>
	
<!--</form> -->
</div> <!-- Payback  -->

<div class='pageSection' id='strComm' style="display:none">
<!--<form id='strCommForm'>-->
	<br/>
	<?php echo $screenData['toolTip']['PRSCREVDTE']?>
	<label>Steering committee action date: </label>
	<?php echo $screenData['html']['PRSCREVDTE']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRRESCOD']?>
	<label>Resolution: </label>
	<?php echo $screenData['html']['PRRESCOD']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRAUTH']?>
	<label>Authorized hours: </label>
	<?php echo $screenData['html']['PRAUTH']?>
	<br/>
	<br/>
	
	<label>Project Type: </label>
	<?php echo $screenData['html']['PRTYPE']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRPLAN']?>
	<label>Planned?: </label>
	<?php echo $screenData['html']['PRPLAN']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['toolTip']['PRPRTY']?>
	<label>SC Priority: </label>
	<?php echo $screenData['html']['PRPRTY']?>
	<br/>
	<br/>
	
	<label>Postmortem Date: </label>
	<?php echo $screenData['html']['PRPMDT']?>
	
	<br/>
	<br/>
	<label>Force Steering Committee Review: </label>
	<?php echo $screenData['html']['PRFORCE2SC']?>
	<br/>
	
	<br/><label>Steering Committee Review Checklist: </label>
	<br/>
	<?php echo $screenData['scCheckList']?>
	
	<label> Steering Committee Comments:</label> 
	<?php 
	foreach ($screenData['projComntSC'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	<script type="text/javascript">
	document.getElementById("projName").focus();
	</script>

</form>

<script>
	
  // The following arrays will be used to check for changes. If changes have been made to a project
  // and the PTS user hasn't saved their changes before trying to venture out of the project
  // request, a warning message will pop up.  
  
  var ids = new Array('projEstimator','projSponsor','projBrand','projProgrammer','projDevGrp','projRequester','projRqstDept','projRqstSubDept','PRUSRACPT','projPBType','projtype','projPlan','projResCode','projWrkSts','projDesc','projComntGen','projComntIT','projComntPB','projComntSC','projName','projSponsAprvDate','tstType','projSponsAprvDate','projSchdStart','projSchdComp','projActComp','PRIMPDTE','projAuthHrs','postMortDate','scRevDate','projDevRate','projNeedBy','projPriority','projForce2SC','projUsrPrty','orig1TimeCost','cur1TimeCost','origAnnualCost','curAnnualCost','orig1TimeSav','cur1TimeSav','origAnnualSav','curAnnualSav','origPayback','curPayback','parentProj');
  var values = new Array('','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','');
  
  populateArrays();

  // Populate the values array with the current project data
    
  function populateArrays() {
    	    
    for (var i = 0; i < ids.length; i++) {
    
      var elem = document.getElementById(ids[i]);
      if (elem) {
        if (elem.type == 'checkbox' || elem.type == 'radio') {
           values[i] = elem.checked;
        }
        else {
           values[i] = elem.value;
        }
      }
    }
  }

  // If the user is trying to unload the current project request, check to make sure there aren't any
  // unsaved changes to the project request.  Pop up warning message if there are unsaved changes.
  
  window.onbeforeunload = confirmExit;
  
  function confirmExit()
  {
    if (needToConfirm)
    {
      // check to see if any changes to the data entry fields have been made
      for (var i = 0; i < values.length; i++)
      {
        var elem = document.getElementById(ids[i]);
        if (elem) {
          if ((elem.type == 'checkbox' || elem.type == 'radio') 
               && values[i] != elem.checked) {
            return 'Need to Save or Cancel!';
          }
          else if (!(elem.type == 'checkbox' || elem.type == 'radio') &&
                  elem.value != values[i]) {
            return 'Need to Save or Cancel!';
          }
        }
      }
      if (whichEditor[0] != ' ' && whichEditor[0] != null) {
         return 'Need to Save or Cancel';
      }

      // no changes - return nothing      
    }
  }
</script>	


</div> <!-- Tab4  -->
<?php 
}
?>
</div> <!-- stdPage -->

<!--  End Content Here -->

