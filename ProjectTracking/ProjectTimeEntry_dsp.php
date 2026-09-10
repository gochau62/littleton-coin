<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTimeEntry_dsp.php          *  -->
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

/* the week bar the time screen prints above its table */
#stdPage > div[style*="width: 50%"] { padding: .2rem 0; }
#stdPage table input.numData { width: 56px; text-align: center; }
#stdPage table td:first-child, #stdPage table td:nth-child(2) { text-align: left; }
#stdPage table th:first-child, #stdPage table th:nth-child(2) { text-align: left; }
#stdPage #projToAdd { width: 92px; }
</style>

<?php
// the display half, inlined
function showTimeEntry(&$screenData) { // Change funcName to something appropriate
?>
	<div id='stdPage'>
		<h1>Project Time Entry</h1>
	
		For week ending: <br/>

		<div style='text-align: left; width: 50%; float: left;'>
			<?php echo $screenData['lnkBack']?>
			&nbsp;&nbsp;
			<?php echo $screenData['longDate']?>
			&nbsp;&nbsp;
			<?php echo $screenData['lnkForward']?>
		</div>
	
		<div style='text-align: right; width: 50%; float: left;'>
			Add project 
			<input type='text' id='projToAdd' maxlength='6' size='2'/> 
			to list.
			&nbsp;&nbsp;
			<button onclick='addProjToTimeList()'>Add</button>
		</div>
	
		<?php echo $screenData['timeTable']?>

	</div>
<?php 	
}
