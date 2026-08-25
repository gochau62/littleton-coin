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

// the stylesheet is shared by the dashboard and the assignments page so the
// two screens read as one tool
function prjStyles() {
?>
<style>
/* all the styling for the Project Tracking screens lives here, not in a
   shared stylesheet. Colors follow the dashboard layout template: light page,
   white cards, blue series color, red only for things needing attention */
:root { --pt-blue: #2a78d6; --pt-blue-dk: #1c5cab; --pt-red: #d03b3b;
        --pt-orange: #eb6834; --pt-green: #008300; --pt-amber: #9a6a14;
        --pt-gray: #898781; --pt-bg: #f4f5f7; --pt-card: #ffffff;
        --pt-line: #e3e5e8; --pt-text: #1c1e21; --pt-muted: #5f6570;
        --pt-chip-blue: #e8f1fc; --pt-chip-amber: #fdf3e1;
        --pt-chip-green: #e6f3e6; --pt-chip-red: #fbe9e9;
        --pt-chip-gray: #eef0f2;
        --pt-mono: "Consolas", "Courier New", monospace; }

.pt-app { font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
          color: var(--pt-text); background: var(--pt-bg);
          padding: 1rem 1.25rem 2rem; }

/* every section sits in a rounded white card on the gray page */
.pt-card { background: var(--pt-card); border: 1px solid var(--pt-line);
           border-radius: 10px; padding: .9rem 1.1rem; margin: 0 0 .9rem; }
.pt-card h2 { font-size: .95rem; font-weight: 600; margin: 0 0 .7rem; }

/* the title card: blue app mark, title, updated date */
.pt-head { display: flex; align-items: center; gap: .8rem; }
.pt-mark { width: 34px; height: 34px; border-radius: 8px; flex: 0 0 auto;
           background: var(--pt-blue); color: #fff; display: flex;
           align-items: center; justify-content: center;
           font-weight: 700; font-size: 1.05rem; }
.pt-head h1 { font-size: 1.15rem; font-weight: 650; margin: 0; }
.pt-head .pt-sub { color: var(--pt-muted); font-size: .82rem; margin-top: .1rem; }
.pt-head .pt-sub a { color: var(--pt-blue); text-decoration: none; }
.pt-head .pt-sub a:hover { text-decoration: underline; }
.pt-head .pt-nav { margin-left: auto; font-size: .85rem; white-space: nowrap; }
.pt-head .pt-nav a { color: var(--pt-blue); text-decoration: none; }
.pt-head .pt-nav a:hover { text-decoration: underline; }

/* stat tile row */
.pt-tiles { display: grid; grid-template-columns: repeat(4, 1fr);
            gap: .9rem; margin: 0 0 .9rem; }
.pt-tile { background: var(--pt-card); border: 1px solid var(--pt-line);
           border-radius: 10px; padding: .7rem .95rem; }
.pt-tile .pt-lbl { color: var(--pt-muted); font-size: .8rem; }
.pt-tile .pt-val { font-size: 1.7rem; font-weight: 650; margin-top: .15rem; }
.pt-tile.pt-warn .pt-val { color: var(--pt-red); }

/* steering committee pipeline: one segment per stage */
.pt-pipe { display: grid; grid-template-columns: repeat(6, 1fr); gap: .6rem; }
.pt-seg { border: 1px solid var(--pt-line); border-radius: 8px;
          padding: .5rem .7rem; background: var(--pt-card); }
.pt-seg .pt-val { font-size: 1.25rem; font-weight: 650; }
.pt-seg .pt-lbl { font-size: .76rem; color: var(--pt-muted); margin-top: .1rem; }
.pt-seg-new       { background: var(--pt-chip-blue);  border-color: #cfe2f8; }
.pt-seg-new       .pt-val { color: var(--pt-blue-dk); }
.pt-seg-needsinfo { background: var(--pt-chip-amber); border-color: #f3e2bb; }
.pt-seg-needsinfo .pt-val { color: var(--pt-amber); }
.pt-seg-approved  { background: var(--pt-chip-green); border-color: #cbe6cb; }
.pt-seg-approved  .pt-val { color: var(--pt-green); }

/* the two chart cards side by side */
.pt-charts { display: grid; grid-template-columns: 3fr 2fr; gap: .9rem;
             margin: 0 0 .9rem; }
.pt-charts .pt-card { margin: 0; }
.pt-chartbox { width: 100%; overflow-x: auto; }
.pt-donutrow { display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }
.pt-legend { font-size: .82rem; }
.pt-legend div { display: flex; align-items: center; gap: .45rem;
                 margin: .22rem 0; }
.pt-dot { width: 10px; height: 10px; border-radius: 3px; flex: 0 0 auto; }
.pt-legend .pt-cnt { color: var(--pt-muted); margin-left: .2rem; }

/* chart hover tooltip, positioned by JS */
#ptTip { position: fixed; display: none; pointer-events: none; z-index: 30;
         background: #26282c; color: #fff; font-size: .78rem;
         padding: .3rem .55rem; border-radius: 6px; max-width: 320px; }

/* filters over the table */
.pt-toolbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
              margin: 0 0 .6rem; }
.pt-toolbar input[type=text] { flex: 1 1 200px; max-width: 300px;
              padding: .4rem .65rem; border: 1px solid var(--pt-line);
              border-radius: 6px; }
.pt-toolbar select { padding: .38rem .5rem; border: 1px solid var(--pt-line);
              border-radius: 6px; background: #fff; }
.pt-toolbar label { font-size: .84rem; color: var(--pt-muted); }
.pt-count { color: var(--pt-muted); font-size: .82rem; margin-left: auto; }

/* white pill buttons, the house style */
.pt-btn { display: inline-flex; align-items: center; gap: 6px;
          padding: .42rem 1.05rem; border: 1px solid #b4b4b4;
          border-radius: 50px; background: #fff; color: var(--pt-text);
          font-size: .88rem; font-weight: 700; cursor: pointer; }
.pt-btn:hover { border-color: var(--pt-blue); color: var(--pt-blue); }
.pt-btn:disabled { opacity: .45; cursor: default; border-color: #b4b4b4;
                   color: var(--pt-text); }
.pt-btn-primary { background: var(--pt-blue); border-color: var(--pt-blue);
                  color: #fff; }
.pt-btn-primary:hover { background: var(--pt-blue-dk);
                        border-color: var(--pt-blue-dk); color: #fff; }

/* the project table scrolls inside its card with the heading row staying put */
.pt-tablewrap { overflow: auto; max-height: 30rem; }
.pt-grid { width: 100%; min-width: 760px; border-collapse: separate;
           border-spacing: 0; font-size: .86rem; }
.pt-grid thead th { position: sticky; top: 0; z-index: 5; background: #f7f8fa;
           color: var(--pt-muted); font-weight: 600; text-align: left;
           padding: .45rem .7rem; white-space: nowrap; cursor: pointer;
           border-bottom: 2px solid var(--pt-line); user-select: none; }
.pt-grid thead th.pt-num { text-align: right; }
.pt-grid tbody td { padding: .4rem .7rem;
           border-bottom: 1px solid var(--pt-line); white-space: nowrap;
           overflow: hidden; text-overflow: ellipsis; max-width: 420px; }
.pt-grid tbody td.pt-num { text-align: right;
           font-variant-numeric: tabular-nums; }
.pt-grid tbody tr:hover { background: #f7f9fc; }
.pt-grid a { color: var(--pt-blue); text-decoration: none;
             font-family: var(--pt-mono); }
.pt-grid a:hover { text-decoration: underline; }
.pt-empty { color: var(--pt-muted); padding: .6rem .7rem; }
.pt-unassigned { color: var(--pt-red); font-weight: 600; }

/* stage chips in the table */
.pt-chip { display: inline-block; padding: .12rem .55rem; border-radius: 20px;
           font-size: .76rem; font-weight: 600; }
.pt-chip-new       { background: var(--pt-chip-blue);  color: var(--pt-blue-dk); }
.pt-chip-awaiting  { background: var(--pt-chip-gray);  color: var(--pt-muted); }
.pt-chip-needsinfo { background: var(--pt-chip-amber); color: var(--pt-amber); }
.pt-chip-parked    { background: var(--pt-chip-gray);  color: var(--pt-muted); }
.pt-chip-approved  { background: var(--pt-chip-green); color: var(--pt-green); }
.pt-chip-rejected  { background: var(--pt-chip-red);   color: var(--pt-red); }
.pt-chip-complete  { background: var(--pt-chip-green); color: var(--pt-green); }

/* weekly summary card */
.pt-weekly-meta { color: var(--pt-muted); font-size: .8rem; margin: 0 0 .5rem; }
.pt-weekly-text { white-space: pre-wrap; font-size: .88rem; line-height: 1.45;
                  max-height: 24rem; overflow: auto; }
.pt-weekly-note { color: var(--pt-amber); font-size: .8rem; margin-top: .5rem; }

/* per-programmer group blocks on the assignments page */
.pt-group h3 { font-size: .95rem; font-weight: 650; margin: 1.1rem 0 .4rem; }
.pt-group h3 .pt-cnt { color: var(--pt-muted); font-weight: 400;
                       font-size: .82rem; }
.pt-group h3.pt-unassigned { color: var(--pt-red); }

#errorMsg { display: none; padding: 1rem; color: var(--pt-red);
            font-weight: bold; }

@media (max-width: 900px) {
    .pt-tiles { grid-template-columns: repeat(2, 1fr); }
    .pt-pipe { grid-template-columns: repeat(3, 1fr); }
    .pt-charts { grid-template-columns: 1fr; }
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
                Overview &nbsp;&middot;&nbsp;
                <a href="ProjectDevelopers_ctl.php">Projects by developer</a>
            <?php } else { ?>
                <a href="ProjectTracking_ctl.php">Overview</a>
                &nbsp;&middot;&nbsp; Projects by developer
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

    <div class="pt-tiles">
        <div class="pt-tile">
            <div class="pt-lbl">Open projects</div>
            <div class="pt-val" id="tileOpen">&ndash;</div>
        </div>
        <div class="pt-tile">
            <div class="pt-lbl">New requests</div>
            <div class="pt-val" id="tileNew">&ndash;</div>
        </div>
        <div class="pt-tile">
            <div class="pt-lbl">Awaiting SC review</div>
            <div class="pt-val" id="tileReview">&ndash;</div>
        </div>
        <div class="pt-tile pt-warn">
            <div class="pt-lbl">Unassigned</div>
            <div class="pt-val" id="tileUnassigned">&ndash;</div>
        </div>
    </div>

    <div class="pt-card">
        <h2>Steering committee pipeline</h2>
        <div class="pt-pipe" id="pipeRow"></div>
    </div>

    <div class="pt-charts">
        <div class="pt-card">
            <h2>Assigned programmer load</h2>
            <div class="pt-chartbox" id="loadChart"></div>
        </div>
        <div class="pt-card">
            <h2>Projects by status</h2>
            <div class="pt-donutrow">
                <div id="statusDonut"></div>
                <div class="pt-legend" id="statusLegend"></div>
            </div>
        </div>
    </div>

    <div class="pt-card">
        <h2>Weekly activity summary</h2>
        <div class="pt-weekly-meta" id="weeklyMeta"></div>
        <div class="pt-weekly-text" id="weeklyText">No weekly summary has been
            generated yet.</div>
        <div class="pt-weekly-note" id="weeklyNote"></div>
        <p style="margin:.7rem 0 0">
            <button type="button" class="pt-btn" id="btnWeekly">Generate for last week</button>
        </p>
    </div>

    <div class="pt-card">
        <h2>Projects (Assignee &amp; Stage)</h2>
        <div class="pt-toolbar">
            <input type="text" id="txtSearch" placeholder="Search project # or description">
            <select id="selPgmr"><option value="">All assignees</option></select>
            <select id="selStage"><option value="">All stages</option></select>
            <span class="pt-count" id="lblCount"></span>
        </div>
        <div class="pt-tablewrap">
            <table class="pt-grid" id="tblProjects">
                <thead><tr>
                    <th data-k="num" class="pt-num">Project</th>
                    <th data-k="desc">Name</th>
                    <th data-k="pgmr">Assigned</th>
                    <th data-k="stage">SC stage</th>
                    <th data-k="deptpr" class="pt-num">Dept prty</th>
                    <th data-k="scpr" class="pt-num">SC prty</th>
                    <th data-k="hours" class="pt-num">Hours</th>
                    <th data-k="sched">Sched comp</th>
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
