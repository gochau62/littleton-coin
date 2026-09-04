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

// the stylesheet shared by both screens
function prjStyles() {
?>
<!-- PT build 2026-09-03-F - deploy marker, check via view-source -->
<script>
// where the legacy project screens answer from
var PT_LEGACY = '<?php echo function_exists('prjLegacyBase') ? prjLegacyBase() : ''; ?>';
function projUrl(num) { return PT_LEGACY + 'PROJ_ctl.php?projnum=' + num; }
// a project opens in its own tab so the dashboard stays put
function openProj(num) { window.open(projUrl(num), '_blank', 'noopener'); }

// the lookup: filters the page as you type and lists matching projects
function ptLookup(o) {
    var box = jQuery('#txtSearch');
    var list = jQuery('<div class="pt-sug-list"></div>').insertAfter(box).hide();
    var timer = null, active = -1, hits = [];
    function h(s) { return jQuery('<span>').text(s == null ? '' : String(s)).html(); }
    function close() { list.hide(); active = -1; }
    function open(num) { openProj(num); close(); }
    function mark() { list.children('.pt-sug').removeClass('on').eq(active).addClass('on'); }
    function show() {
        var q = box.val().trim().toLowerCase();
        hits = []; active = -1;
        if (q === '') { close(); return; }
        var seen = {};
        jQuery.each(o.rows() || [], function (i, p) {
            if (seen[p.num]) { return; }
            if (String(p.num).indexOf(q) === 0 ||
                String(p.desc).toLowerCase().indexOf(q) >= 0) {
                seen[p.num] = true; hits.push(p);
            }
        });
        // newest project numbers first, eight at most
        hits.sort(function (a, b) { return b.num - a.num; });
        hits = hits.slice(0, 8);
        var html = '';
        jQuery.each(hits, function (i, p) {
            html += '<div class="pt-sug" data-i="' + i + '"><b>' + p.num + '</b>' +
                    '<span>' + h(p.desc) + '</span></div>';
        });
        list.html(html || '<div class="pt-sug pt-sug-none">No matching project</div>').show();
    }
    box.on('input', function () {
        show();
        clearTimeout(timer);
        timer = setTimeout(o.after, 250);
    });
    box.on('keydown', function (e) {
        if (e.key === 'ArrowDown' && hits.length) {
            e.preventDefault(); active = (active + 1) % hits.length; mark(); return;
        }
        if (e.key === 'ArrowUp' && hits.length) {
            e.preventDefault(); active = (active - 1 + hits.length) % hits.length; mark(); return;
        }
        if (e.key === 'Escape') { close(); return; }
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        // a picked row, a full number, or the only match opens
        if (active >= 0) { open(hits[active].num); return; }
        var v = box.val().trim();
        if (/^\d{6}$/.test(v)) { open(v); return; }
        if (hits.length === 1) { open(hits[0].num); return; }
        close();
        o.after();
        var t = document.querySelector(o.scroll);
        if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
    // mousedown lands before the blur that closes the list
    list.on('mousedown', '.pt-sug[data-i]', function (e) {
        e.preventDefault();
        open(hits[jQuery(this).data('i')].num);
    });
    box.on('blur', function () { setTimeout(close, 150); });
    box.on('focus', function () { if (box.val().trim() !== '') { show(); } });
}
</script>
<style>
/* one blue working color, red for attention, 4px rhythm */
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

/* house rule: take the given width, never more. Both carry the page grey
   so the framework's blue never shows behind the content. No width here -
   the app sits beside the floated menu and fills what is left */
#stdPage { min-width: 0; max-width: 100%; box-sizing: border-box;
           background: var(--pt-bg); }
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

/* small uppercase labels, numbers carry the weight */
.pt-card h2 { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
              text-transform: uppercase; color: var(--pt-muted);
              margin: 0 0 .85rem; }

/* title card: name and meta line left, switch button right */
.pt-head { display: flex; align-items: center; gap: 1rem;
           padding: .9rem 1.15rem; }
.pt-head h1 { font-size: 1.25rem; font-weight: 680; letter-spacing: -.02em;
              margin: 0; line-height: 1.15; }
.pt-head .pt-sub { display: flex; align-items: center; gap: .55rem;
                   margin-top: .35rem; font-size: .75rem;
                   color: var(--pt-faint); }
.pt-head .pt-sub .pt-when { display: inline-flex; align-items: center;
           gap: .3rem; padding: .12rem .45rem; border-radius: 999px;
           background: var(--pt-line-soft); color: var(--pt-muted);
           font-weight: 500; }
.pt-head .pt-sub .pt-when::before { content: ""; width: 6px; height: 6px;
           border-radius: 50%; background: var(--pt-green); }
.pt-refresh { color: var(--pt-muted) !important; text-decoration: none;
           font-weight: 600; padding: .12rem .3rem; border-radius: 6px; }
.pt-refresh:hover { color: var(--pt-blue) !important;
           background: var(--pt-line-soft); text-decoration: none; }
/* lookup and switch button travel together, right-aligned, and wrap
   under the title as one unit when the frame is narrow */
.pt-head { flex-wrap: wrap; }
.pt-head .pt-tools { display: flex; align-items: center; gap: .6rem;
           margin-left: auto; flex: 0 1 auto; }
.pt-head .pt-lookup { position: relative; flex: 1 1 140px;
           min-width: 120px; max-width: 240px; }
.pt-head .pt-goto { width: 100%; box-sizing: border-box;
           padding: .42rem .6rem; border: 1px solid var(--pt-field);
           border-radius: 8px; font-size: .84rem; color: var(--pt-text);
           background: var(--pt-card); }
.pt-head .pt-goto::placeholder { color: var(--pt-faint); }
.pt-head .pt-goto:focus { outline: none; border-color: var(--pt-blue);
           box-shadow: 0 0 0 3px rgba(42,120,214,.15); }
/* matching projects drop down under the box, anchored to its right edge */
.pt-sug-list { position: absolute; top: calc(100% + 4px); right: 0;
           width: 340px; max-width: 80vw; max-height: 280px; overflow-y: auto;
           background: var(--pt-card); border: 1px solid var(--pt-line);
           border-radius: 8px; box-shadow: var(--pt-shadow); z-index: 50;
           text-align: left; }
.pt-sug { display: flex; gap: .55rem; align-items: baseline;
           padding: .4rem .65rem; font-size: .82rem; cursor: pointer;
           border-bottom: 1px solid var(--pt-line-soft); }
.pt-sug:last-child { border-bottom: 0; }
.pt-sug b { font-family: var(--pt-mono); font-weight: 600; color: var(--pt-blue);
           flex: 0 0 auto; }
.pt-sug span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pt-sug.on, .pt-sug:hover { background: var(--pt-line-soft); }
.pt-sug-none { color: var(--pt-muted); cursor: default; }
.pt-head .pt-nav { white-space: nowrap; }
.pt-head .pt-nav a.pt-btn { text-decoration: none;
           color: var(--pt-text) !important; }
.pt-head .pt-nav a.pt-btn:hover { color: var(--pt-blue) !important;
           border-color: var(--pt-blue); }

/* the stat strip: four cells with a colored top rule, inside one card */
.pt-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem; }
.pt-stat { background: var(--pt-card); border: 1px solid var(--pt-line);
           border-top-width: 3px; border-top-color: var(--pt-gray);
           border-radius: 8px; padding: .6rem .85rem .7rem; }
.pt-stat:hover { border-color: var(--pt-blue); }
.pt-stat[data-tile=open]       { border-top-color: var(--pt-blue); }
.pt-stat[data-tile=new]        { border-top-color: var(--pt-blue); }
.pt-stat[data-tile=review]     { border-top-color: var(--pt-yellow); }
.pt-stat[data-tile=unassigned] { border-top-color: var(--pt-red); }
.pt-stat .pt-lbl { font-size: .7rem; font-weight: 600; letter-spacing: .06em;
                   text-transform: uppercase; color: var(--pt-muted); }
.pt-stat .pt-val { font-size: 1.75rem; font-weight: 650; letter-spacing: -.02em;
                   margin-top: .3rem; font-variant-numeric: tabular-nums; }
.pt-stat.pt-warn .pt-val { color: var(--pt-red); }

/* everything that navigates or filters shows a pointer */
.pt-seg, .pt-legrow, .pt-rowlink, .pt-bar, .pt-arc, .pt-stat { cursor: pointer; }
.pt-seg:hover { border-color: var(--pt-blue); }
.pt-legrow { padding: .1rem .25rem; border-radius: 6px; }
.pt-legrow:hover { background: var(--pt-line-soft); }
.pt-arc { transition: opacity .12s ease; }
.pt-arc:hover { opacity: .7; }
/* the load chart highlights the whole row on hover, no tooltip */
.pt-barbg { fill: transparent; }
.pt-barname { fill: var(--pt-muted); }
.pt-bar:hover .pt-barbg { fill: var(--pt-line-soft); }
.pt-bar:hover .pt-barname { fill: var(--pt-text); font-weight: 600; }

/* pipeline cells with a colored rule per stage */
.pt-pipe { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; }

/* review queue, inside the pipeline card under the cells */
.pt-queue { margin-top: 1rem; padding-top: .9rem; border-top: 1px solid var(--pt-line); }
.pt-queue-head { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
           margin-bottom: .6rem; }
.pt-queue-head h2 { margin: 0; }
.pt-queue-counts { font-size: .78rem; color: var(--pt-muted); }
.pt-queue-head select { margin-left: auto; padding: .3rem .5rem; font-size: .8rem;
           border: 1px solid var(--pt-field); border-radius: 6px; background: var(--pt-card); }
/* the number keeps its badge beside it, and the chips wrap onto lines */
#tblQueue td:first-child, #tblQueue td:last-child { white-space: normal;
           overflow: visible; text-overflow: clip; }
.pt-need { display: inline-block; margin: .1rem .25rem .1rem 0; padding: .1rem .4rem;
           border-radius: 5px; font-size: .7rem; font-weight: 600; line-height: 1.25;
           background: var(--pt-chip-amber); color: var(--pt-amber); }
.pt-need-ready { background: var(--pt-chip-green); color: var(--pt-green); }
.pt-need-sc    { background: var(--pt-chip-red);   color: var(--pt-red); }
.pt-fresh { display: inline-block; margin-left: .3rem; padding: 0 .35rem; border-radius: 999px;
           font-size: .62rem; font-weight: 700; letter-spacing: .04em; vertical-align: middle;
           background: var(--pt-chip-blue); color: var(--pt-blue-dk); }
.pt-fire { color: var(--pt-orange); font-size: .7rem; font-weight: 600; margin-left: .3rem;
           white-space: nowrap; }
.pt-queue-more { display: inline-block; margin-top: .5rem; font-size: .8rem;
           color: var(--pt-blue) !important; text-decoration: none; }
.pt-queue-more:hover { text-decoration: underline; }
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

/* chart cards side by side; tracks can shrink */
.pt-charts { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 1rem;
             margin: 0 0 1rem; align-items: stretch; }
.pt-charts .pt-card { margin: 0; display: flex; flex-direction: column; }
.pt-chartbox { width: 100%; overflow-x: auto; }
/* donut centers in the bar chart's height */
.pt-donutrow { display: flex; align-items: center; justify-content: center;
               gap: 2rem; flex-wrap: wrap; flex: 1; padding: .25rem 0; }
.pt-legend { font-size: .82rem; }
.pt-legend div { display: flex; align-items: center; gap: .5rem;
                 margin: .3rem 0; }
.pt-dot { width: 9px; height: 9px; border-radius: 3px; flex: 0 0 auto; }
.pt-legend .pt-cnt { color: var(--pt-muted); margin-left: .25rem;
                     font-variant-numeric: tabular-nums; }

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

/* fixed layout, no minimum widths, values ellipsize */
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

/* override the framework's site-wide table styling */
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
.pt-st-onhold     { color: #7a5af8; }
.pt-st-inqueue    { color: #db2777; }
.pt-st-estnotneed { color: var(--pt-green); }
.pt-st-other      { color: var(--pt-blue-dk); }
.pt-st-notset     { color: #06b6d4; }
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

/* weekly summary: one section per developer */
.pt-weekly-meta { color: var(--pt-faint); font-size: .76rem; margin: 0 0 .6rem; }
.pt-weekly-text { font-size: .85rem; line-height: 1.55;
                  max-height: 24rem; overflow: auto; color: var(--pt-text); }
.pt-wk-dev { padding: .55rem 0 .6rem; border-top: 1px solid var(--pt-line-soft); }
.pt-wk-dev:first-child { border-top: 0; padding-top: .1rem; }
.pt-wk-dev h3 { margin: 0 0 .15rem; font-size: .78rem; font-weight: 700;
                letter-spacing: .05em; color: var(--pt-blue-dk); }
.pt-wk-dev p { margin: 0; }
.pt-wk-num { color: var(--pt-blue); text-decoration: none; font-weight: 600;
             font-variant-numeric: tabular-nums; }
.pt-wk-num:hover { text-decoration: underline; }
.pt-wk-total { margin-top: .65rem; padding: .55rem .8rem; background: #f8fafc;
               border: 1px solid var(--pt-line-soft); border-radius: 8px;
               color: var(--pt-muted); }
.pt-weekly-note { color: var(--pt-amber); font-size: .78rem; margin-top: .6rem; }
/* period picker: mode, calendar field, Generate */
.pt-wkbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            margin: .85rem 0 0; }
.pt-wkbar select {
            padding: .42rem .6rem; border: 1px solid var(--pt-field);
            border-radius: 8px; font: inherit; font-size: .84rem;
            background: var(--pt-card); color: var(--pt-text); }
.pt-cal-wrap { position: relative; }
.pt-cal-field { display: flex; align-items: center; gap: .5rem;
            padding: .42rem .6rem; border: 1px solid var(--pt-field);
            border-radius: 8px; font: inherit; font-size: .84rem;
            background: var(--pt-card); color: var(--pt-text); cursor: pointer; }
.pt-cal-field:hover { border-color: var(--pt-blue); }
.pt-cal-caret { color: var(--pt-faint); font-size: .7rem; }
.pt-cal { position: absolute; left: 0; top: calc(100% + 6px); z-index: 40;
          background: var(--pt-card); border: 1px solid var(--pt-line);
          border-radius: 10px; box-shadow: 0 8px 24px rgba(16, 24, 40, .14);
          padding: .6rem .65rem .65rem; width: 238px; }
.pt-cal-head { display: flex; align-items: center; margin: 0 0 .4rem; }
.pt-cal-title { flex: 1; text-align: center; font-weight: 650; font-size: .84rem; }
.pt-cal-nav { border: 0; background: transparent; cursor: pointer;
              font-size: 1rem; color: var(--pt-muted); padding: .1rem .45rem;
              border-radius: 6px; }
.pt-cal-nav:hover { background: var(--pt-line-soft); color: var(--pt-text); }
.pt-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.pt-cal-dow { text-align: center; font-size: .68rem; font-weight: 600;
              color: var(--pt-muted); padding: .15rem 0 .25rem; }
.pt-cal-we  { color: #c56a6a; }
.pt-cal-day { text-align: center; font-size: .78rem; padding: .32rem 0;
              border-radius: 6px; cursor: pointer;
              font-variant-numeric: tabular-nums; }
.pt-cal-day:hover { background: var(--pt-line-soft); }
.pt-cal-out { color: var(--pt-faint); }
.pt-cal-sel { background: var(--pt-chip-blue); color: var(--pt-blue-dk);
              font-weight: 600; }
.pt-cal-sel:hover { background: var(--pt-chip-blue); }

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

/* container query restacks beside the sidebar too */
@container (max-width: 880px) {
    .pt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pt-stat { padding: .3rem 1rem; }
    .pt-stat:nth-child(odd) { border-left: 0; padding-left: .3rem; }
    .pt-pipe { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pt-charts { grid-template-columns: minmax(0, 1fr); }
}
</style><?php
}


// shared title card; $active names the current page
function prjHeader($title, $subtitle, $active) {
?>
    <div class="pt-card pt-head">
        <div>
            <h1><?php echo $title; ?></h1>
            <div class="pt-sub"><?php echo $subtitle; ?></div>
        </div>
        <div class="pt-tools">
            <span class="pt-lookup">
                <input type="text" id="txtSearch" class="pt-goto" autocomplete="off"
                       placeholder="Project # or Name"
                       title="Type to filter. Pick a project from the list, or press Enter on its number, to open it.">
            </span>
            <div class="pt-nav">
                <?php // one button across to the other page
                      if ($active === 'dashboard') { ?>
                    <a class="pt-btn" href="ProjectDevelopers_ctl.php">Projects by developer &rsaquo;</a>
                <?php } else { ?>
                    <a class="pt-btn" href="ProjectTracking_ctl.php">&lsaquo; Overview</a>
                <?php } ?>
            </div>
        </div>
    </div>
<?php
}


function dspProjectTracking() {
    prjStyles();
?>
<!-- stdPage seats the page beside the nav menu -->
<div id="stdPage">
<div class="pt-app">

    <?php prjHeader('Project Tracking',
                    '<span class="pt-when" id="ptUpdated"></span>' .
                    '<a href="#" id="lnkRefresh" class="pt-refresh">&#8635; Refresh</a>',
                    'dashboard'); ?>

    <!-- the project list sits first, right under the lookup -->
    <div class="pt-card">
        <h2>Projects (Assignee &amp; Stage)</h2>
        <div class="pt-toolbar">
            <select id="selPgmr"><option value="">All assignees</option></select>
            <select id="selStage"><option value="">All stages</option></select>
        </div>
        <div class="pt-tablewrap">
            <table class="pt-grid" id="tblProjects">
                <!-- fixed pixel columns for numbers and dates -->
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

    <div class="pt-card">
    <div class="pt-stats">
        <div class="pt-stat" id="statOpen" data-tile="open"
             title="Every project on the SC reports - click to clear filters">
            <div class="pt-lbl">Open projects</div>
            <div class="pt-val" id="tileOpen">&ndash;</div>
        </div>
        <div class="pt-stat" data-tile="new"
             title="Submitted in the last three SC cycles, not yet ruled on - click to list them">
            <div class="pt-lbl">New requests</div>
            <div class="pt-val" id="tileNew">&ndash;</div>
        </div>
        <div class="pt-stat" data-tile="review" title="Click to list what the committee is reviewing">
            <div class="pt-lbl">Awaiting SC review</div>
            <div class="pt-val" id="tileReview">&ndash;</div>
        </div>
        <div class="pt-stat pt-warn" data-tile="unassigned" title="Click to list the unassigned projects">
            <div class="pt-lbl">Unassigned</div>
            <div class="pt-val" id="tileUnassigned">&ndash;</div>
        </div>
    </div>
    </div>

    <div class="pt-card">
        <h2>Steering Committee Pipeline</h2>
        <div class="pt-pipe" id="pipeRow"></div>

        <!-- the review queue: uncoded projects, closest to ready first -->
        <div class="pt-queue">
            <div class="pt-queue-head">
                <h2>Review queue</h2>
                <span class="pt-queue-counts" id="queueCounts"></span>
                <select id="selQueueDept"><option value="">All departments</option></select>
            </div>
            <div class="pt-tablewrap">
                <table class="pt-grid" id="tblQueue">
                    <colgroup><col style="width:116px"><col style="width:24%">
                    <col style="width:12%"><col style="width:7%">
                    <col style="width:84px"><col style="width:52px"><col></colgroup>
                    <thead><tr>
                        <th class="pt-num">Project</th>
                        <th>Name</th>
                        <th>Requester</th>
                        <th>Dept</th>
                        <th class="pt-wrap">Submitted</th>
                        <th class="pt-num pt-wrap">Dept prty</th>
                        <th>Still needs</th>
                    </tr></thead>
                    <tbody id="queueBody"></tbody>
                </table>
            </div>
            <a href="#" id="queueMore" class="pt-queue-more"></a>
        </div>
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
        <div class="pt-wkbar">
            <select id="selWkMode">
                <option value="week">Week</option>
                <option value="month">Month</option>
            </select>
            <div class="pt-cal-wrap">
                <button type="button" class="pt-cal-field" id="btnCalField">
                    <span id="calLabel">Select date</span>
                    <span class="pt-cal-caret">&#9662;</span>
                </button>
                <div class="pt-cal" id="ptCal" style="display:none">
                    <div class="pt-cal-head">
                        <button type="button" class="pt-cal-nav" id="calPrev">&#8249;</button>
                        <div class="pt-cal-title" id="calTitle"></div>
                        <button type="button" class="pt-cal-nav" id="calNext">&#8250;</button>
                    </div>
                    <div class="pt-cal-grid" id="calGrid"></div>
                </div>
            </div>
            <button type="button" class="pt-btn" id="btnWeekly">Generate</button>
        </div>
    </div>

</div>
</div>
<?php
}
?>
