<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectDetail_dsp.php            *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/10/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */
?>
<style>
/* the ProjectTracking look, applied to this screen's own markup */
/* the same palette the ProjectTracking screens use */
#stdPage {
    --pt-blue: #2a78d6; --pt-blue-dk: #1c5cab; --pt-red: #d03b3b;
    --pt-green: #008300; --pt-bg: #f6f7f9; --pt-card: #ffffff;
    --pt-line: #e4e7ec; --pt-line-soft: #eef0f4;
    --pt-text: #101828; --pt-muted: #667085; --pt-faint: #98a2b3;
    --pt-field: #d0d5dd; --pt-chip-gray: #f0f2f5;
    --pt-shadow: 0 1px 2px rgba(16, 24, 40, .05);

    font-family: "Segoe UI", -apple-system, system-ui, Roboto,
                 "Helvetica Neue", Arial, sans-serif;
    font-size: 14px; line-height: 1.45; color: var(--pt-text);
    background: var(--pt-bg); padding: 1rem; box-sizing: border-box;
}
#stdPage * { box-sizing: border-box; }

/* the title row: project number left, the buttons right */
#stdPage h1, #stdPage h2 { font-weight: 700; color: var(--pt-text); }
#stdPage h1 { font-size: 1.3rem; margin: 0 0 .6rem; }
#stdPage h2 { font-size: 1.05rem; margin: 0; display: inline-flex;
    align-items: center; gap: .5rem; }
#stdPage h2 u, #stdPage label u, #stdPage u { text-decoration: none; }
#stdPage #projectNumber { font-size: 1.15rem; font-weight: 700; width: 104px;
    text-align: center !important; letter-spacing: .02em; }

/* Print, Save Project, Discard Project */
#stdPage a { color: var(--pt-blue); text-decoration: none; font-weight: 600; }
#stdPage a:hover { text-decoration: underline; }
#stdPage input[type=button], #stdPage input[type=submit], #stdPage button {
    font: inherit; font-size: .82rem; font-weight: 600; cursor: pointer;
    padding: .42rem .8rem; border-radius: 8px;
    border: 1px solid var(--pt-line); background: var(--pt-card);
    color: var(--pt-text); margin-left: .35rem; }
#stdPage input[type=button]:hover, #stdPage button:hover {
    border-color: var(--pt-blue); color: var(--pt-blue); }
/* the save is the one action that carries the accent */
#stdPage input[value*="Save"], #stdPage input[value*="save"] {
    background: var(--pt-blue); border-color: var(--pt-blue); color: #fff; }
#stdPage input[value*="Save"]:hover { background: var(--pt-blue-dk);
    border-color: var(--pt-blue-dk); color: #fff; }
#stdPage input[value*="Discard"], #stdPage input[value*="Cancel"] {
    color: var(--pt-red); }
#stdPage input[value*="Discard"]:hover { border-color: var(--pt-red);
    color: var(--pt-red); background: #fceaea; }
#stdPage input[disabled], #stdPage button[disabled] {
    opacity: .5; cursor: default; }

/* the header's right-hand block keeps its buttons on one line */
#stdPage > div[style*="text-align: right"] { white-space: nowrap; }

/* the tab strip, the live tab reading as a raised card edge */
#stdPage .tabArea { border-bottom: 1px solid var(--pt-line);
    padding: .5rem 0 0; margin: .5rem 0 0; clear: both; }
#stdPage .tabArea br { display: none; }
#stdPage .tabArea a { display: inline-block; font-size: .86rem; font-weight: 600;
    color: var(--pt-muted); padding: .45rem .8rem; margin: 0 .1rem -1px 0;
    border: 1px solid transparent; border-bottom: 0;
    border-radius: 8px 8px 0 0; text-decoration: none; }
#stdPage .tabArea a:hover { color: var(--pt-text);
    background: var(--pt-line-soft); text-decoration: none; }
#stdPage .tabArea a.selected, #stdPage .tabArea a.active,
#stdPage .tabArea a[class*="sel"] { color: var(--pt-text);
    background: var(--pt-card); border-color: var(--pt-line); }

/* each tab's pane is a card */
#stdPage .pageSection { background: var(--pt-card);
    border: 1px solid var(--pt-line); border-top: 0;
    border-radius: 0 0 10px 10px; box-shadow: var(--pt-shadow);
    padding: 1rem 1.15rem 1.25rem; }

/* labels and fields */
#stdPage label { font-size: .82rem; font-weight: 600; color: var(--pt-muted);
    margin-right: .35rem; }
#stdPage input[type=text], #stdPage input[type=date], #stdPage input[type=number],
#stdPage select, #stdPage textarea {
    font: inherit; font-size: .86rem; color: var(--pt-text);
    background: var(--pt-card); border: 1px solid var(--pt-field);
    border-radius: 8px; padding: .38rem .55rem; }
#stdPage input[type=text]:focus, #stdPage input[type=date]:focus,
#stdPage select:focus, #stdPage textarea:focus {
    outline: 0; border-color: var(--pt-blue);
    box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
#stdPage input[readonly], #stdPage input[disabled], #stdPage select[disabled] {
    background: var(--pt-bg); color: var(--pt-muted); }
#stdPage input[type=checkbox], #stdPage input[type=radio] {
    vertical-align: middle; margin: 0 .3rem 0 0; }
#stdPage input.date { width: 130px; }
#stdPage input.userID { width: 130px; }
#stdPage input.numData, #stdPage .numData input { text-align: right; }

/* the tooltip icons the screen sprinkles before labels */
#stdPage img[src*="Info_icon"] { vertical-align: middle; opacity: .5;
    margin-right: .15rem; }
#stdPage img[src*="Info_icon"]:hover { opacity: 1; }

/* the breathing room the markup asks for with <br/> pairs */
#stdPage hr { border: 0; border-top: 1px solid var(--pt-line); margin: 1rem 0; }
#stdPage small { color: var(--pt-muted); font-size: .78rem; }
#stdPage .data { display: inline-block; font-weight: 600;
    padding: .1rem .45rem; border-radius: 6px;
    background: var(--pt-chip-gray); }

/* the payback grid and any other table the screen prints */
#stdPage table { border-collapse: collapse; margin: .3rem 0 .6rem;
    background: var(--pt-card); }
#stdPage caption, #stdPage CAPTION { text-align: left; font-size: .72rem;
    font-weight: 600; letter-spacing: .07em; text-transform: uppercase;
    color: var(--pt-muted); padding: 0 0 .4rem; }
#stdPage th { font-size: .68rem; font-weight: 600; letter-spacing: .04em;
    text-transform: uppercase; color: var(--pt-muted); text-align: left;
    padding: .45rem .55rem; border-bottom: 1px solid var(--pt-line);
    white-space: nowrap; }
#stdPage th.blank { border-bottom-color: transparent; }
#stdPage td { padding: .35rem .55rem; font-size: .84rem;
    border-bottom: 1px solid var(--pt-line-soft); }
#stdPage tbody tr:hover { background: var(--pt-line-soft); }
#stdPage td.numData { text-align: right; }
#stdPage tr.total td { font-weight: 700; border-top: 1px solid var(--pt-line);
    border-bottom: 0; }

/* the steering committee checklist, ticked or outstanding */
#stdPage ul { list-style: none; margin: .3rem 0 .8rem; padding: 0; }
#stdPage ul li { font-size: .86rem; padding: .14rem 0; color: var(--pt-text); }
#stdPage ul li img { vertical-align: middle; margin-right: .35rem; }

/* the action items box */
#stdPage fieldset { border: 1px solid var(--pt-line); border-radius: 10px;
    padding: .6rem .9rem 1rem; margin: .6rem 0; }
#stdPage legend { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
    text-transform: uppercase; color: var(--pt-muted); padding: 0 .3rem; }

/* comments and the editor blocks the WebNotes code drops in */
#stdPage .comment, #stdPage .webNote { background: var(--pt-bg);
    border: 1px solid var(--pt-line); border-radius: 8px;
    padding: .5rem .65rem; margin: .35rem 0; font-size: .85rem; }


/* ---- the card layout, carried over from the design ---- */

#stdPage .pt-app { max-width: 1000px; }

/* the screen is one card: title row, tabs, then the fields */
#stdPage .pt-card { background: var(--pt-card); border: 1px solid var(--pt-line);
    border-radius: 12px; box-shadow: var(--pt-shadow); overflow: hidden; }

#stdPage .pt-scr-head { display: flex; align-items: flex-start;
    justify-content: space-between; gap: 1rem;
    padding: .95rem 1.15rem .8rem; border-bottom: 1px solid var(--pt-line); }
#stdPage .pt-scr-what { font-size: .68rem; font-weight: 600; letter-spacing: .07em;
    text-transform: uppercase; color: var(--pt-muted); }
#stdPage .pt-scr-num { font-size: 1.32rem; font-weight: 700; margin-top: .1rem; }
#stdPage .pt-scr-num #projectNumber { font-size: 1.15rem; font-weight: 700;
    width: 104px; text-align: center !important; letter-spacing: .02em; }
#stdPage .pt-scr-desc { font-size: .9rem; color: var(--pt-muted); margin-top: .3rem; }
#stdPage .pt-scr-btns { display: flex; align-items: center; gap: .5rem;
    flex-shrink: 0; }

/* the Print and comment links read as quiet buttons beside Save */
#stdPage .pt-slink { font-size: .82rem; font-weight: 600; white-space: nowrap; }
#stdPage .pt-sbtn-quiet { background: var(--pt-chip-gray) !important;
    border: 1px solid var(--pt-line) !important; color: var(--pt-text) !important; }

/* tab strip: the live tab reads as a raised card edge */
#stdPage .pt-tabs { display: flex; gap: .25rem; padding: .55rem 1.15rem 0;
    border-bottom: 1px solid var(--pt-line); background: var(--pt-bg); }
#stdPage .pt-tabs br { display: none; }
#stdPage .pt-tab { font-size: .84rem; font-weight: 600; color: var(--pt-muted);
    padding: .45rem .75rem; border: 1px solid transparent; border-bottom: 0;
    border-radius: 8px 8px 0 0; margin-bottom: -1px; text-decoration: none;
    display: inline-block; }
#stdPage .pt-tab:hover { color: var(--pt-text); background: var(--pt-line-soft);
    text-decoration: none; }
#stdPage .pt-tab.pt-on { color: var(--pt-text); background: var(--pt-card);
    border-color: var(--pt-line); }

/* a pane sits inside the card, so it carries no card of its own */
#stdPage .pt-pane { background: none; border: 0; border-radius: 0;
    box-shadow: none; padding: 1.05rem 1.15rem 1.25rem; margin: 0; }

/* two fields to a row, one when the field wants the width */
#stdPage .pt-row { display: flex; flex-wrap: wrap; gap: .9rem 1.1rem;
    margin-bottom: .35rem; }
#stdPage .pt-fld { flex: 1 1 calc(50% - .55rem); min-width: 210px; }
#stdPage .pt-fld-wide { flex-basis: 100%; }
#stdPage .pt-fld > label { display: block; font-size: .76rem; font-weight: 600;
    color: var(--pt-muted); margin-bottom: .28rem; }
#stdPage .pt-fld label.pt-inline { display: block; font-size: .84rem;
    font-weight: 400; color: var(--pt-text); margin: .1rem 0 .3rem; }

/* the legacy fragments fill the field, except the ones sized to a few chars */
#stdPage .pt-fld input[type=text], #stdPage .pt-fld input[type=date],
#stdPage .pt-fld select, #stdPage .pt-fld textarea { width: 100%; }
#stdPage .pt-fld input[size="1"], #stdPage .pt-fld input[size="2"],
#stdPage .pt-fld input[size="3"], #stdPage .pt-fld input[size="5"],
#stdPage .pt-fld input[size="6"] { width: auto; }
#stdPage .pt-fld input[type=checkbox], #stdPage .pt-fld input[type=radio] {
    width: auto; }

/* a value nobody can change here reads as plain text on the page */
#stdPage .pt-fld .pt-ro { font-size: .86rem; padding: .5rem .1rem;
    min-height: 1.2rem; border-bottom: 1px solid var(--pt-line-soft); }
#stdPage .pt-fld .pt-ro:empty::after { content: '\2014'; color: var(--pt-faint); }
#stdPage .pt-fld small { display: block; margin-top: .25rem; }

/* the two current estimate figures sit side by side */
#stdPage .pt-estpair { display: flex; gap: .5rem; align-items: center; }
#stdPage .pt-estpair .data { margin: 0; }

/* the payback grid keeps its table, inside a scrolling frame */
#stdPage .pt-tablewrap { overflow-x: auto; margin: .2rem 0 .8rem; }
#stdPage .pt-scr-bad { color: var(--pt-red); }

/* the go to project card, and the same card when nothing was found */
#stdPage .pt-ask { max-width: 560px; padding: 1.4rem 1.5rem; }
#stdPage .pt-ask-title { font-size: 1.22rem; font-weight: 700; margin: 0 0 1.05rem; }
#stdPage .pt-ask-bad { color: var(--pt-red); }
#stdPage .pt-ask-row { display: flex; align-items: center; gap: .65rem; }
#stdPage .pt-ask-row label { font-size: .92rem; font-weight: 600;
    color: var(--pt-text); margin: 0; }
#stdPage .pt-ask-row #projectNumber { width: 128px; font-size: 1.08rem;
    font-weight: 700; text-align: center !important; letter-spacing: .02em; }
#stdPage .pt-ask-note { font-size: .84rem; color: var(--pt-muted); margin-top: 1rem; }

</style>

<?php
// the display half, inlined
 
function showProjPrompt() {
?>
	<div id='stdPage'>
	<div class='pt-app'>
	<div class='pt-card pt-ask'>
		<h1 class='pt-ask-title'>Know which project you want?</h1>
		<div class='pt-ask-row'>
			<label for='projectNumber'>Go to project:</label>
			<input onchange='goToProject()' id='projectNumber' type='text' size='5' maxlength='6'/>
		</div>
		<div class='pt-ask-note'>Or <a href='ProjectDetail_ctl.php?projnum=newproj'>start a new project</a>.</div>
	</div>
	</div>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjNotFound() {
?>
	<div id='stdPage'>
	<div class='pt-app'>
	<div class='pt-card pt-ask'>
		<h1 class='pt-ask-title pt-ask-bad'>That project was not found.</h1>
		<div class='pt-ask-row'>
			<label for='projectNumber'>Go to project:</label>
			<input onchange='goToProject()' id='projectNumber' type='text' size='5' maxlength='6'/>
		</div>
		<div class='pt-ask-note'>Try another number, or <a href='ProjectDetail_ctl.php?projnum=newproj'>start a new project</a>.</div>
	</div>
	</div>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjectDetailScreen(&$screenData) {
?>
	<div id='stdPage'>
	<div class='pt-app'>

	<div class='pt-card pt-scr'>

	<div class='pt-scr-head'>
		<div class='pt-scr-id'>
			<div class='pt-scr-what'>Project</div>
			<div class='pt-scr-num'>
				<input onchange='goToProject()' id='projectNumber' type='text' size='5' maxlength='6' value='<?php echo $screenData['PR#']?>'/>
			</div>
			<div class='pt-scr-desc'><?php echo $screenData['PRDESC']?></div>
		</div>
		<div class='pt-scr-btns'>
			<a class='pt-slink' href="PROJ_print_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">Print</a>
			<?php echo $screenData['saveButton']?>
			<?php echo $screenData['cancelButton']?>
		</div>
	</div>

	<div class='pt-tabs' id='PROJ_mainTabs'>
		<a id='tabGeneral' class='pt-tab pt-on' href="javascript:ptTab('general')">General</a>
		<a id='tabIt' class='pt-tab' href="javascript:ptTab('itStuff')">IT Stuff</a>
		<a id='tabPayBack' class='pt-tab' href="javascript:ptTab('payBack')">Payback</a>
		<a id='tabStrComm' class='pt-tab' href="javascript:ptTab('strComm')">Steering Committee</a>
	</div>

<div id='general' class='pageSection pt-pane' style="display:block">
<form id='projForm' name='projForm' action='PROJ_save.php' method="post">
	<input type='hidden' name='projnum' value='<?php echo $screenData['PR#']?>' />
	<input type='hidden' id='hiddenUser' value='<?php echo strtoupper($_SESSION['username'])?>' />
	<input type='hidden' id='hiddenUserType' value='<?php echo strtoupper($_SESSION['username'])?>' />
	<input type='hidden' id='hiddenUserClass' value='<?php echo strtoupper(trim($_SESSION['usrclass']))?>' />

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Project Name
			<span onmouseover="tooltip.show('Descriptive, accurate, clear and short. 50 Characters max.');" onmouseout="tooltip.hide();"><img src='images/Info_icon_20px.png' height='15' width='15' /></span>
			</label>
			<input type="text" id="projName" name="projName" size="60" maxlength="50" value="<?php echo $screenData['PRDESC']?>"/>
		</div>

		<div class='pt-fld pt-fld-wide'>
			<label>Description <?php echo $screenData['toolTip']['PRDescrip']?></label>
			<?php echo $screenData['projDesc'];?>
		</div>
	</div>

	<?php If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) { ?>
	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Parent Project</label>
			<div class='pt-ro'><?php echo $screenData['PRRELPRJ#'] . ' ' . $screenData['parentProjDesc']?></div>
		</div>
	</div>
	<?php } ?>

	<?php If ($screenData['childrenProjects'] != ' ' and $screenData['childrenProjects'] != null) { ?>
	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Children Projects</label>
			<div class='pt-ro'><?php echo $screenData['childrenProjects']?></div>
		</div>
	</div>
	<?php } ?>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Attached Documents <?php echo $screenData['toolTip']['LNKDOC']?></label>
			<div id="linkedDocs"><?php echo $screenData['linkedDocs'];?></div>
			<input class='pt-sbtn pt-sbtn-quiet' type='button' onclick="javascript:popupWindow('LNKDOC_upload_ctl.php?prefix=PROJ_&idval=<?php echo $screenData['PR#']?>', 'Upload Doc', 400)" value="Attach a Document" />
		</div>
	</div>

	<hr/>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<?php echo $screenData['tstType']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Requestor <?php echo $screenData['toolTip']['PRRQST']?></label>
			<div class='pt-ro'><?php echo $screenData['PRRQST']?></div>
		</div>
		<div class='pt-fld'>
			<label>Created Date</label>
			<?php echo $screenData['html']['PRSUBD']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Sponsor <?php echo $screenData['toolTip']['PRSPONSR']?></label>
			<div class='pt-ro'><?php echo $screenData['PRSPONSR'] ?></div>
		</div>
		<div class='pt-fld'>
			<label>Sponsor Approval Date <?php echo $screenData['toolTip']['PRSPAPVDTE']?></label>
			<?php echo $screenData['html']['PRSPAPVDTE']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Need By Date <?php echo $screenData['toolTip']['PRNEED']?></label>
			<?php echo $screenData['html']['PRNEED']?>
		</div>
		<div class='pt-fld'>
			<label>Justification Type</label>
			<?php echo $screenData['html']['PJDESC'] ?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Requesting Department</label>
			<?php echo $screenData['html']['PRDEPT']?>
		</div>
		<div class='pt-fld'>
			<label>Sub Dept</label>
			<?php echo $screenData['html']['PRSUBDEPT']?>
		</div>
		<div class='pt-fld'>
			<label>Department Priority <?php echo $screenData['toolTip']['PRUPTY']?>
			<span onmouseover="tooltip.show(&apos; 1 - Needs to be completed in 1 to 3 months <br> 2 - Needs to be completed in 3 to 6 months <br> 3 - Needs to be completed in 6 months to a year <br> 4 - On Hold <br> 5 -> 8 - Not Used <br> 9 - Default (has not been changed since project creation) &apos;)" onmouseout="tooltip.hide();"><img src="images/Info_icon_20px.png" width="15" height="15"></span>
			</label>
			<input size='2' maxlength='1' type='text' name='projUsrPrty' onchange='activateSave()' value='<?php echo $screenData['PRUPTY']?>' />
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Project Acceptance <?php echo $screenData['toolTip']['PRUSRACPT']?></label>
			<label class='pt-inline'><?php echo $screenData['html']['PRUSRACPT']?> By checking this box the user agrees the project is complete and is ready for implementation.</label>
			<div id='acceptDiv'><?php echo $screenData['acceptText']?></div>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Comments <?php echo $screenData['toolTip']['GenCommnts']?>
			<a class='pt-slink' href="PROJ_allComntView_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">View All Comments</a>
			</label>
			<?php
			foreach ($screenData['projComntGen'] as $comment) {
				echo $comment;
			}
			?>
		</div>
	</div>

</div><!-- general -->

<div class='pageSection pt-pane' id='itStuff' style="display:none">

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'><?php echo $screenData['estLink']?></div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Assigned Estimator</label>
			<?php echo $screenData['html']['PRESTMTR']?>
		</div>
		<div class='pt-fld'>
			<label>Dev Group</label>
			<?php echo $screenData['html']['PRITDEVGRP']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Original estimate</label>
			<div class='data' id='origHiEst'><?php echo $screenData['origHiEst']?></div>
			<small>Originaly estimated on <?php echo $screenData['origEstDate'] . " by " . $screenData['origEstimator']?></small>
		</div>
		<div class='pt-fld'>
			<label>Current estimate <?php echo $screenData['toolTip']['CurHiEst']?></label>
			<div class='pt-estpair'>
				<div class='data' id='CurLowEst'><?php echo $screenData['curLowEst']?></div>
				<div class='data' id='curHiEst'><?php echo $screenData['curHiEst']?></div>
			</div>
			<small>current estimate was done on <?php echo $screenData['curEstDate'] . " by " . $screenData['curEstimator']?></small>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Brand</label>
			<div class='pt-ro'><?php echo $screenData['PRBRAND']?></div>
		</div>
		<div class='pt-fld'>
			<label>Parent Project</label>
			<input size='6' maxlength='6' type='text' name='parentProj' value='<?php echo $screenData['PRRELPRJ#']?>' />
			<small><?php echo $screenData['parentProjDesc'] //wrap key in quotes to prevent undefined constant warning - 06-28-22 - kjr ?></small>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Programmer assigned <?php echo $screenData['toolTip']['PRPGMR']?></label>
			<div class='pt-ro'><?php echo $screenData['PRPGMR']?></div>
		</div>
		<div class='pt-fld'>
			<label>Programmer work status <?php echo $screenData['toolTip']['PRWRKSTS']?></label>
			<?php echo $screenData['html']['PRWRKSTS']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Programmer time to date</label>
			<?php echo $screenData['pgmrTime']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Scheduled start date</label>
			<?php echo $screenData['html']['PRESTR']?>
		</div>
		<div class='pt-fld'>
			<label>Scheduled implementation date</label>
			<?php echo $screenData['html']['PRECOM']?>
		</div>
		<div class='pt-fld'>
			<label>Actual implementation date</label>
			<?php echo $screenData['html']['PRACOM']?>
		</div>
	</div>

	<fieldset id = 'lgndFldSet'>
	<legend id="lgndRetailRange">Action Items:</legend>
	<div id='divRetailRange'>
	<input class='pt-sbtn pt-sbtn-quiet' id="addActInfo" type="button" value="Add Action">
	<div id='actDtl'>
    <ul></ul>
	</div>
	<?php //echo $screenData['instOrd']['RETRANGE']; ?>
	</div>
	</fieldset>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Comments <?php echo $screenData['toolTip']['ITCommnts']?></label>
			<?php
			foreach ($screenData['projComntIT'] as $comment) {
				echo $comment;
			}
			?>
		</div>
	</div>

</div> <!-- itStuff  -->

<div class='pageSection pt-pane' id='payBack' style="display:none">

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Payback type <?php echo $screenData['toolTip']['PRPAYBKTYP']?></label>
			<?php echo $screenData['html']['PRPAYBKTYP']?>
		</div>
		<div class='pt-fld'>
			<label>Developer rate</label>
			<?php echo $screenData['html']['PRDRAT']?>
		</div>
	</div>

	<div class='pt-tablewrap'>
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
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Comments <?php echo $screenData['toolTip']['PBComnts']?></label>
			<?php
			foreach ($screenData['projComntPB'] as $comment) {
				echo $comment;
			}
			?>
		</div>
	</div>

</div> <!-- Payback  -->

<div class='pageSection pt-pane' id='strComm' style="display:none">

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Steering committee action date <?php echo $screenData['toolTip']['PRSCREVDTE']?></label>
			<?php echo $screenData['html']['PRSCREVDTE']?>
		</div>
		<div class='pt-fld'>
			<label>Resolution <?php echo $screenData['toolTip']['PRRESCOD']?></label>
			<?php echo $screenData['html']['PRRESCOD']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Authorized hours <?php echo $screenData['toolTip']['PRAUTH']?></label>
			<?php echo $screenData['html']['PRAUTH']?>
		</div>
		<div class='pt-fld'>
			<label>Project Type</label>
			<?php echo $screenData['html']['PRTYPE']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld'>
			<label>Planned? <?php echo $screenData['toolTip']['PRPLAN']?></label>
			<?php echo $screenData['html']['PRPLAN']?>
		</div>
		<div class='pt-fld'>
			<label>SC Priority <?php echo $screenData['toolTip']['PRPRTY']?></label>
			<?php echo $screenData['html']['PRPRTY']?>
		</div>
		<div class='pt-fld'>
			<label>Postmortem Date</label>
			<?php echo $screenData['html']['PRPMDT']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Force Steering Committee Review</label>
			<?php echo $screenData['html']['PRFORCE2SC']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Steering Committee Review Checklist</label>
			<?php echo $screenData['scCheckList']?>
		</div>
	</div>

	<div class='pt-row'>
		<div class='pt-fld pt-fld-wide'>
			<label>Steering Committee Comments</label>
			<?php
			foreach ($screenData['projComntSC'] as $comment) {
				echo $comment;
			}
			?>
		</div>
	</div>

	<script type="text/javascript">
	document.getElementById("projName").focus();
	</script>

</form>

<script>
// the tabs are handled here, not by the framework
function ptTab(pane) {
	var tabs = { general: 'tabGeneral', itStuff: 'tabIt',
	             payBack: 'tabPayBack', strComm: 'tabStrComm' };
	for (var p in tabs) {
		var sec = document.getElementById(p);
		var tab = document.getElementById(tabs[p]);
		if (sec) { sec.style.display = (p === pane) ? 'block' : 'none'; }
		if (tab) { tab.className = 'pt-tab' + ((p === pane) ? ' pt-on' : ''); }
	}
}
</script>

<script>

  // these arrays spot unsaved changes before leaving

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

  // warn before leaving a project with unsaved edits
  
  window.onbeforeunload = confirmExit;
  
  function confirmExit()
  {
    if (needToConfirm)
    {
      // look for edits in the entry fields
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


</div> <!-- strComm  -->
</div> <!-- pt-scr -->
</div> <!-- pt-app -->
</div> <!-- stdPage -->
<?php 
}
