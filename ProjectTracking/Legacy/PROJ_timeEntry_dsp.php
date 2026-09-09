<?php
function showTimeEntry(&$screenData) { // Change funcName to something appropriate

	// the ProjectTracking styles, so this screen matches the new ones
	if (file_exists("ProjectTracking/ProjectTracking_dsp.php")) {
		require_once "ProjectTracking/ProjectTracking_model.php";
		require_once "ProjectTracking/ProjectTracking_dsp.php";
		prjStyles();
	}
?>
<style>
/* the week reads as one card: which week, then a row per project */
.pt-wk-bar { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem;
        border-bottom: 1px solid var(--pt-line); flex-wrap: wrap; }
.pt-wk-lbl { font-size: .82rem; color: var(--pt-muted); }
.pt-wk-when { font-size: .95rem; font-weight: 700; }
.pt-wk-step, .pt-wk-bar a { font: inherit; font-size: .82rem; font-weight: 600;
        cursor: pointer; padding: .3rem .6rem; border: 1px solid var(--pt-line);
        border-radius: 8px; background: var(--pt-card); color: var(--pt-text);
        text-decoration: none; }
.pt-wk-step:hover, .pt-wk-bar a:hover { border-color: var(--pt-blue); color: var(--pt-blue); }

/* add a project sits at the right of the same bar */
.pt-addrow { margin-left: auto; display: inline-flex; gap: .4rem; align-items: center; }
.pt-addrow input { font: inherit; font-size: .84rem; padding: .3rem .5rem;
        border: 1px solid var(--pt-field); border-radius: 8px; width: 92px; }
.pt-addrow input:focus { outline: 0; border-color: var(--pt-blue);
        box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
.pt-addrow .pt-hint { font-size: .8rem; color: var(--pt-muted); }

/* the table the controller builds, wearing the new skin */
.pt-timewrap { padding: 0; overflow-x: auto; }
.pt-timewrap table { width: 100%; border-collapse: collapse; }
.pt-timewrap th { font-size: .68rem; font-weight: 600; letter-spacing: .04em;
        text-transform: uppercase; color: var(--pt-muted); text-align: center;
        padding: .5rem .3rem; border-bottom: 1px solid var(--pt-line);
        white-space: nowrap; }
.pt-timewrap td { padding: .35rem .3rem; text-align: center; font-size: .84rem;
        border-bottom: 1px solid var(--pt-line-soft); }
.pt-timewrap th:first-child, .pt-timewrap td:first-child,
.pt-timewrap th:nth-child(2), .pt-timewrap td:nth-child(2) { text-align: left; }
.pt-timewrap tbody tr:hover { background: var(--pt-line-soft); }
.pt-timewrap td:first-child a { font-weight: 600; }

/* one hours box per day, wide enough for 8.25 */
.pt-timewrap input.numData { width: 56px; font: inherit; font-size: .84rem;
        text-align: center; color: var(--pt-text); background: var(--pt-card);
        border: 1px solid var(--pt-field); border-radius: 7px; padding: .3rem .2rem; }
.pt-timewrap input.numData:focus { outline: 0; border-color: var(--pt-blue);
        box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }

/* the TOTAL row the controller appends carries class total */
.pt-timewrap tr.total td { font-weight: 700; padding: .5rem .3rem;
        border-top: 1px solid var(--pt-line); border-bottom: 0; }
.pt-timewrap tr.total td.txtData { text-align: left; }
</style>

	<div id='stdPage'>
	<div class="pt-app">

		<div class="pt-card pt-head">
			<div><h1>Project Time Entry</h1></div>
		</div>

		<div class="pt-card">
			<div class="pt-wk-bar">
				<span class="pt-wk-lbl">For week ending:</span>
				<?php echo $screenData['lnkBack']?>
				<span class="pt-wk-when"><?php echo $screenData['longDate']?></span>
				<?php echo $screenData['lnkForward']?>

				<span class="pt-addrow">
					<span class="pt-hint">Add project</span>
					<input type='text' id='projToAdd' maxlength='6' placeholder='Project #'/>
					<span class="pt-hint">to list.</span>
					<button type='button' class="pt-wk-step" onclick='addProjToTimeList()'>Add</button>
				</span>
			</div>

			<div class="pt-timewrap">
				<?php echo $screenData['timeTable']?>
			</div>
		</div>

	</div>
	</div>
<?php
}
?>
