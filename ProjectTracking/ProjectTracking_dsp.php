<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_dsp.php          *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 08/11/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// the stylesheet is shared by the dashboard and the developers page so the
// two screens read as one tool
function prjStyles() {
?>
<!-- PT build 2026-08-27-L - view-source and search "PT build" to confirm
     the deployed copy is current -->
<style>
/* All the styling for the Project Tracking screens lives here, not in a
   shared stylesheet.

   The design system: near-black ink on white cards over a cool gray page,
   one blue as the working color, red reserved for things needing attention,
   small uppercase labels for structure, tabular numerals for every figure.
   Spacing runs on a 4px rhythm. */
:root { --pt-blue: #2a78d6; --pt-blue-dk: #1c5cab; --pt-red: #d03b3b;
        --pt-orange: #eb6834; --pt-green: #008300; --pt-amber: #b07b0e;
        --pt-yellow: #eda100; --pt-gray: #98a2b3;
        --pt-bg: #f6f7f9; --pt-card: #ffffff;
        --pt-line: #e4e7ec; --pt-line-soft: #eef0f4;
        --pt-text: #101828; --pt-muted: #667085; --pt-faint: #98a2b3;
        --pt-field: #d0d5dd;
        --pt-chip-blue: #eaf2fc; --pt-chip-amber: #faf1dc;
        --pt-chip-green: #e7f4e7; --pt-chip-red: #fceaea;
        --pt-chip-gray: #f0f2f5;
        --pt-shadow: 0 1px 2px rgba(16, 24, 40, .05);
        --pt-mono: "Cascadia Mono", Consolas, "Courier New", monospace; }

/* the house rule the other tools follow (see the Requisitions screen): the
   page sits inside the framework page beside the LCC sidebar, so it never
   assumes the whole window - it takes the width it is given (roughly 700px
   beside the menu) and everything inside stays within it */
#stdPage { min-width: 0; max-width: 100%; }
.pt-app { min-width: 0; max-width: 100%; box-sizing: border-box;
          container-type: inline-size;
          font-family: "Segoe UI", -apple-system, system-ui, Roboto,
          "Helvetica Neue", Arial, sans-serif;
          color: var(--pt-text); background: var(--pt-bg);
          padding: 1.25rem 1.5rem 2.5rem;
          font-size: .875rem; line-height: 1.45;
          -webkit-font-smoothing: antialiased; }
.pt-app > * { max-width: 100%; }

/* every section sits in a white card on the gray page */
.pt-card { background: var(--pt-card); border: 1px solid var(--pt-line);
           border-radius: 10px; box-shadow: var(--pt-shadow);
           padding: 1.1rem 1.25rem; margin: 0 0 1rem; }

/* card headings are small uppercase labels, so the numbers carry the weight */
.pt-card h2 { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
              text-transform: uppercase; color: var(--pt-muted);
              margin: 0 0 .85rem; }

/* the title card: app mark, page title, updated line, section nav */
.pt-head { display: flex; align-items: center; gap: .85rem;
           padding: .95rem 1.25rem; }
.pt-mark { width: 38px; height: 38px; border-radius: 9px; flex: 0 0 auto;
           background: linear-gradient(135deg, var(--pt-blue), var(--pt-blue-dk));
           color: #fff; display: flex; align-items: center;
           justify-content: center; font-weight: 700; font-size: 1rem;
           letter-spacing: .02em; }
.pt-head h1 { font-size: 1.2rem; font-weight: 650; letter-spacing: -.01em;
              margin: 0; }
.pt-head .pt-sub { color: var(--pt-muted); font-size: .78rem;
                   margin-top: .15rem; }
.pt-head .pt-sub a { color: var(--pt-blue); text-decoration: none;
                     font-weight: 600; }
.pt-head .pt-sub a:hover { text-decoration: underline; }
.pt-head .pt-nav { margin-left: auto; font-size: .82rem; white-space: nowrap;
                   color: var(--pt-faint); }
.pt-head .pt-nav a { color: var(--pt-muted); text-decoration: none;
                     font-weight: 500; padding: .35rem 0; }
.pt-head .pt-nav a:hover { color: var(--pt-blue); }
.pt-head .pt-nav .pt-here { color: var(--pt-text); font-weight: 600;
                   padding: .35rem 0; border-bottom: 2px solid var(--pt-blue); }

/* the stat strip: one card, four figures separated by hairlines */
.pt-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.pt-stat { padding: .3rem 1.5rem; border-left: 1px solid var(--pt-line-soft); }
.pt-stat:first-child { border-left: 0; padding-left: .3rem; }
.pt-stat .pt-lbl { font-size: .72rem; font-weight: 600; letter-spacing: .06em;
                   text-transform: uppercase; color: var(--pt-muted); }
.pt-stat .pt-val { font-size: 1.9rem; font-weight: 650; letter-spacing: -.02em;
                   margin-top: .35rem; font-variant-numeric: tabular-nums; }
.pt-stat.pt-warn .pt-val { color: var(--pt-red); }
.pt-statnote { font-size: .72rem; color: var(--pt-faint); margin-top: .1rem; }

/* steering committee pipeline: quiet white cells, a colored rule per stage */
.pt-pipe { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; }
.pt-seg { border: 1px solid var(--pt-line); border-top-width: 3px;
          border-top-color: var(--pt-gray); border-radius: 8px;
          padding: .6rem .85rem .65rem; background: var(--pt-card); }
.pt-seg .pt-val { font-size: 1.35rem; font-weight: 650;
                  font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
.pt-seg .pt-lbl { font-size: .74rem; color: var(--pt-muted); margin-top: .1rem; }
.pt-seg-new       { border-top-color: var(--pt-blue); }
.pt-seg-awaiting  { border-top-color: var(--pt-gray); }
.pt-seg-needsinfo { border-top-color: var(--pt-yellow); }
.pt-seg-parked    { border-top-color: var(--pt-faint); }
.pt-seg-approved  { border-top-color: var(--pt-green); }
.pt-seg-rejected  { border-top-color: var(--pt-red); }

/* the two chart cards side by side */
/* minmax(0,*) lets the tracks shrink below their content, so the page
   never demands more width than the space beside the sidebar offers */
.pt-charts { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 1rem;
             margin: 0 0 1rem; align-items: stretch; }
.pt-charts .pt-card { margin: 0; display: flex; flex-direction: column; }
.pt-chartbox { width: 100%; overflow-x: auto; }
/* the donut centers in whatever height the taller bar chart card sets */
.pt-donutrow { display: flex; align-items: center; justify-content: center;
               gap: 2rem; flex-wrap: wrap; flex: 1; padding: .25rem 0; }
.pt-legend { font-size: .82rem; }
.pt-legend div { display: flex; align-items: center; gap: .5rem;
                 margin: .3rem 0; }
.pt-dot { width: 9px; height: 9px; border-radius: 3px; flex: 0 0 auto; }
.pt-legend .pt-cnt { color: var(--pt-muted); margin-left: .25rem;
                     font-variant-numeric: tabular-nums; }

/* chart hover tooltip, positioned by JS */
#ptTip { position: fixed; display: none; pointer-events: none; z-index: 30;
         background: var(--pt-text); color: #fff; font-size: .76rem;
         padding: .35rem .6rem; border-radius: 7px; max-width: 320px;
         box-shadow: 0 4px 12px rgba(16, 24, 40, .18); }

/* filters over the table */
.pt-toolbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
              margin: 0 0 .8rem; }
.pt-toolbar input[type=text] { flex: 1 1 220px; max-width: 320px;
              padding: .45rem .75rem; border: 1px solid var(--pt-field);
              border-radius: 8px; font: inherit; font-size: .84rem;
              color: var(--pt-text); background: #fff; }
.pt-toolbar input[type=text]::placeholder { color: var(--pt-faint); }
.pt-toolbar select { padding: .42rem .6rem; border: 1px solid var(--pt-field);
              border-radius: 8px; background: #fff; font: inherit;
              font-size: .84rem; color: var(--pt-text); }
.pt-toolbar input:focus, .pt-toolbar select:focus { outline: none;
              border-color: var(--pt-blue);
              box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
.pt-toolbar label { font-size: .82rem; color: var(--pt-muted);
              display: flex; align-items: center; gap: .4rem; }
.pt-count { color: var(--pt-faint); font-size: .78rem; margin-left: auto;
            font-variant-numeric: tabular-nums; }

/* buttons */
.pt-btn { display: inline-flex; align-items: center; gap: 6px;
          padding: .45rem 1rem; border: 1px solid var(--pt-field);
          border-radius: 8px; background: #fff; color: var(--pt-text);
          font: inherit; font-size: .82rem; font-weight: 600;
          cursor: pointer; box-shadow: var(--pt-shadow); }
.pt-btn:hover { border-color: var(--pt-blue); color: var(--pt-blue); }
.pt-btn:disabled { opacity: .5; cursor: default;
                   border-color: var(--pt-field); color: var(--pt-text); }
.pt-btn-primary { background: var(--pt-blue); border-color: var(--pt-blue);
                  color: #fff; }
.pt-btn-primary:hover { background: var(--pt-blue-dk);
                        border-color: var(--pt-blue-dk); color: #fff; }

/* the project table scrolls inside its card with the heading row staying put.
   fixed layout with percentage columns and NO minimum width matters here:
   the LCC Online frame floats the menu on the left, and any hard minimum
   (a min-width, or an auto table of nowrap cells) makes the whole page too
   wide to sit beside it, dropping the content below the menu. Long values
   ellipsize inside their columns instead of forcing the page wider */
.pt-tablewrap { overflow: auto; max-height: 30rem; max-width: 100%;
                contain: inline-size;
                border: 1px solid var(--pt-line); border-radius: 8px; }
.pt-grid { width: 100%; table-layout: fixed; border-collapse: separate;
           border-spacing: 0; font-size: .84rem; }

.pt-grid thead th { position: sticky; top: 0; z-index: 5; background: #fafbfc;
           color: var(--pt-muted); font-size: .7rem; font-weight: 600;
           letter-spacing: .05em; text-transform: uppercase; text-align: left;
           padding: .55rem .45rem; white-space: nowrap; cursor: pointer;
           border-bottom: 1px solid var(--pt-line); user-select: none; }
.pt-grid thead th:hover { color: var(--pt-text); }
.pt-grid thead th.pt-num { text-align: right; }
.pt-grid thead th.pt-sort-asc::after  { content: " \2191"; color: var(--pt-blue); }
.pt-grid thead th.pt-sort-desc::after { content: " \2193"; color: var(--pt-blue); }
.pt-grid tbody td { padding: .5rem .45rem;
           border-bottom: 1px solid var(--pt-line-soft); white-space: nowrap;
           overflow: hidden; text-overflow: ellipsis; max-width: 420px; }
.pt-grid tbody tr:last-child td { border-bottom: 0; }
.pt-grid tbody td.pt-num { text-align: right;
           font-variant-numeric: tabular-nums; }
.pt-grid tbody tr:hover td { background: #f8fafc; }
/* two-line headings for the narrow priority/date columns */
.pt-grid thead th.pt-wrap { white-space: normal; line-height: 1.15; }

/* the LCC framework styles bare table/th/td site-wide (green headers, black
   cell borders, auto layout); these put the tool's own look back with the
   #stdPage weight - fixed layout is what keeps every group's columns equal */
#stdPage table.pt-grid { table-layout: fixed; width: 100%;
                         border-collapse: separate; border-spacing: 0; }
#stdPage .pt-grid th, #stdPage .pt-grid td { border: 0; background: transparent; }
#stdPage .pt-grid thead th { background: #fafbfc;
           border-bottom: 1px solid var(--pt-line); }
#stdPage .pt-grid tbody td { border-bottom: 1px solid var(--pt-line-soft); }
#stdPage .pt-grid tbody tr:last-child td { border-bottom: 0; }
#stdPage .pt-grid tbody tr:hover td { background: #f8fafc; }

/* per-row status, colored to match the dashboard donut */
.pt-st { font-size: .72rem; font-weight: 600; white-space: nowrap; }
.pt-st::before { content: "\25CF\00a0"; font-size: .6rem; }
.pt-st-active     { color: #185fa5; }
.pt-st-waituser   { color: var(--pt-amber); }
.pt-st-onhold     { color: #898781; }
.pt-st-estnotneed { color: var(--pt-green); }
.pt-st-other      { color: var(--pt-blue-dk); }
.pt-st-notset     { color: var(--pt-faint); font-weight: 500; }
.pt-grid a { color: var(--pt-blue); text-decoration: none;
             font-family: var(--pt-mono); font-size: .8rem; font-weight: 600; }
.pt-grid a:hover { text-decoration: underline; }
.pt-empty { color: var(--pt-muted); padding: .7rem .8rem; font-size: .84rem; }
.pt-unassigned { color: var(--pt-red); font-weight: 600; }

/* stage chips in the table */
.pt-chip { display: inline-block; padding: .14rem .4rem; border-radius: 5px;
           font-size: .72rem; font-weight: 600; letter-spacing: .01em; }
.pt-chip-new       { background: var(--pt-chip-blue);  color: var(--pt-blue-dk); }
.pt-chip-awaiting  { background: var(--pt-chip-gray);  color: var(--pt-muted); }
.pt-chip-needsinfo { background: var(--pt-chip-amber); color: var(--pt-amber); }
.pt-chip-parked    { background: var(--pt-chip-gray);  color: var(--pt-muted); }
.pt-chip-approved  { background: var(--pt-chip-green); color: var(--pt-green); }
.pt-chip-rejected  { background: var(--pt-chip-red);   color: var(--pt-red); }
.pt-chip-complete  { background: var(--pt-chip-green); color: var(--pt-green); }

/* weekly summary card */
.pt-weekly-meta { color: var(--pt-faint); font-size: .76rem; margin: 0 0 .6rem; }
.pt-weekly-text { white-space: pre-wrap; font-size: .85rem; line-height: 1.55;
                  max-height: 24rem; overflow: auto; color: var(--pt-text); }
.pt-weekly-note { color: var(--pt-amber); font-size: .78rem; margin-top: .6rem; }

/* per-developer group blocks on the developers page */
.pt-group h3 { font-size: .92rem; font-weight: 650; letter-spacing: -.005em;
               margin: 1.35rem 0 .5rem; }
.pt-group h3 .pt-cnt { color: var(--pt-faint); font-weight: 400;
                       font-size: .78rem; }
.pt-group h3.pt-unassigned { color: var(--pt-red); }
.pt-group .pt-card { padding: .4rem .5rem; }
.pt-group .pt-tablewrap { border: 0; }

#errorMsg { display: none; padding: 1rem; color: var(--pt-red);
            font-weight: bold; }

@media (max-width: 980px) {
    .pt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pt-stat { padding: .3rem 1rem; }
    .pt-stat:nth-child(odd) { border-left: 0; padding-left: .3rem; }
    .pt-pipe { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pt-charts { grid-template-columns: minmax(0, 1fr); }
}

/* the media query above sees the WINDOW, but beside the sidebar the page
   only gets ~700px while the window is far wider - so the same restacking
   also keys off the width the page itself was given */
@container (max-width: 880px) {
    .pt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pt-stat { padding: .3rem 1rem; }
    .pt-stat:nth-child(odd) { border-left: 0; padding-left: .3rem; }
    .pt-pipe { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pt-charts { grid-template-columns: minmax(0, 1fr); }
}
</style>
<div id="ptTip"></div>
<?php
}


// the shared title card; $active names the current page's nav link
function prjHeader($title, $subtitle, $active) {
?>
    <div class="pt-card pt-head">
        <div class="pt-mark">PT</div>
        <div>
            <h1><?php echo $title; ?></h1>
            <div class="pt-sub"><?php echo $subtitle; ?></div>
        </div>
        <div class="pt-nav">
            <?php if ($active === 'dashboard') { ?>
                <span class="pt-here">Overview</span> &nbsp;&nbsp;
                <a href="ProjectDevelopers_ctl.php">Projects by developer</a>
            <?php } else { ?>
                <a href="ProjectTracking_ctl.php">Overview</a> &nbsp;&nbsp;
                <span class="pt-here">Projects by developer</span>
            <?php } ?>
        </div>
    </div>
<?php
}


function dspProjectTracking() {
    prjStyles();
?>
<!-- stdPage is the shared layout hook that seats a page beside the nav menu,
     same as every legacy PROJ_* screen -->
<div id="stdPage">
<div class="pt-app">

    <?php prjHeader('Project Tracking',
                    '<span id="ptUpdated"></span> &nbsp;&middot;&nbsp; ' .
                    '<a href="#" id="lnkRefresh">refresh</a>',
                    'dashboard'); ?>

    <div class="pt-card">
        <div class="pt-stats">
            <div class="pt-stat" id="statOpen">
                <div class="pt-lbl">Open projects</div>
                <div class="pt-val" id="tileOpen">&ndash;</div>
                <div class="pt-statnote" id="tileOpenNote"></div>
            </div>
            <div class="pt-stat">
                <div class="pt-lbl">New requests</div>
                <div class="pt-val" id="tileNew">&ndash;</div>
            </div>
            <div class="pt-stat">
                <div class="pt-lbl">Awaiting SC review</div>
                <div class="pt-val" id="tileReview">&ndash;</div>
            </div>
            <div class="pt-stat pt-warn">
                <div class="pt-lbl">Unassigned</div>
                <div class="pt-val" id="tileUnassigned">&ndash;</div>
            </div>
        </div>
    </div>

    <div class="pt-card">
        <h2>Steering Committee Pipeline</h2>
        <div class="pt-pipe" id="pipeRow"></div>
    </div>

    <div class="pt-charts">
        <div class="pt-card">
            <h2>Assigned Programmer Load</h2>
            <div class="pt-chartbox" id="loadChart"></div>
        </div>
        <div class="pt-card">
            <h2>Projects by Status</h2>
            <div class="pt-donutrow">
                <div id="statusDonut"></div>
                <div class="pt-legend" id="statusLegend"></div>
            </div>
        </div>
    </div>

    <div class="pt-card">
        <h2>Weekly Activity Summary</h2>
        <div class="pt-weekly-meta" id="weeklyMeta"></div>
        <div class="pt-weekly-text" id="weeklyText">No weekly summary has been
            generated yet.</div>
        <div class="pt-weekly-note" id="weeklyNote"></div>
        <p style="margin:.85rem 0 0">
            <button type="button" class="pt-btn" id="btnWeekly">Generate for last week</button>
        </p>
    </div>

    <div class="pt-card">
        <h2>Projects (Assignee &amp; Stage)</h2>
        <div class="pt-toolbar">
            <input type="text" id="txtSearch" placeholder="Search project # or description">
            <select id="selPgmr"><option value="">All assignees</option></select>
            <select id="selStage"><option value="">All stages</option></select>
        </div>
        <div class="pt-tablewrap">
            <table class="pt-grid" id="tblProjects">
                <!-- numbers and dates get fixed pixel columns so they never
                     ellipsize; the text columns absorb any squeeze -->
                <colgroup><col style="width:76px"><col style="width:22%">
                <col style="width:88px"><col style="width:11%">
                <col style="width:11%"><col style="width:6%">
                <col style="width:6%"><col style="width:6%">
                <col style="width:88px"></colgroup>
                <thead><tr>
                    <th data-k="num" class="pt-num">Project</th>
                    <th data-k="desc">Name</th>
                    <th data-k="sub" class="pt-wrap">Submitted</th>
                    <th data-k="pgmr">Assigned</th>
                    <th data-k="stage">SC stage</th>
                    <th data-k="deptpr" class="pt-num pt-wrap">Dept prty</th>
                    <th data-k="scpr" class="pt-num pt-wrap">SC prty</th>
                    <th data-k="hours" class="pt-num">Hours</th>
                    <th data-k="sched" class="pt-wrap">Sched comp</th>
                </tr></thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>
    </div>

</div>
</div>
<?php
}
?>
