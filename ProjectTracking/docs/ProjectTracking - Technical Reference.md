# Project Tracking rewrite — Technical Reference

New Project Tracking screens replacing the legacy `PROJ_list` landing page,
built to the dashboard layout template (`docs/Picture1.png`). The legacy
`PROJ_*.php` files stay in `Legacy/` as reference; the project entry screen
(`docs/Picture2.png`) is a later phase and is not part of this work.

## What was built

| Piece | File | What it does |
|---|---|---|
| Overview dashboard | `ProjectTracking_ctl.php` | Stat tiles (open / new / awaiting SC review / unassigned), steering-committee pipeline, programmer-load bar chart, projects-by-status donut, weekly AI summary card, and a sortable/filterable project table |
| Dashboard display | `ProjectTracking_dsp.php` | Markup + the stylesheet shared by both screens |
| Projects by developer | `ProjectDevelopers_ctl.php` / `_dsp.php` | The monthly "Projects by developer" spreadsheet as a live page: grouped per programmer ("CMCBETH — 7 projects"), Unassigned last in red, search/filter, Excel download |
| Data + logic | `ProjectTracking_model.php` | Db2 reads via PRJTRK001S, the SC-stage and status derivations, dashboard rollups, the weekly digest, and the Gemini call |
| JSON/Excel endpoint | `ProjectTracking_ajax.php` | `dashboard`, `assignments`, `weeklygenerate`, `download` actions |
| Db2 procedure | `PRJTRK001S.PROC` | One read-only procedure, `INTYPE` selects the result set: `LIST` (projects + newest estimate + summed hours), `TIME`, `NOTES`, `COMP`, `PGMR`, `CHGLOG` (PRCHGLOGP change history) |

Everything is **read-only** against the project files — the new screens change
no data. Project numbers link back to the existing `PROJ_ctl.php` detail
screen.

The `Legacy/` folder carries the live legacy sources for reference (copied
from the server 08/26/26): `PROJ_model.php`, `PROJ_ctl.php`,
`PROJ_ajax_request.php`, `PROJ_ajax_request_post.php`,
`PROJ_timeEntry_ctl.php`, `PROJ_saveTime.php`, `LCDEPTP_model.php`,
`LCEMPLOYP_model.php` — the last two are the department and employee lookups
for the planned team/sub-department tagging.

## Deploying

1. Copy the six `ProjectTracking_*.php` / `ProjectDevelopers_*.php` files
   from the folder root into the LCCOnline docroot (the same folder
   as `PROJ_list_ctl.php` and `TimePayment_ctl.php` today). They use the
   same `StartBlockScriptA/B` + `EndBlock` frame and root-relative
   includes (`jQuery/jquery.js`) as TimePayment, so they must sit in the root.
2. Compile the procedure:
   `RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC) SRCMBR(PRJTRK001S)`.
   The file and field names are compile-verified (08/25/26) — the PRTIMEP
   columns are `PT#`, `PTPGMR`, `PTDATE`, `PTTIME` per SYSCOLUMNS.
   Recompile after pulling 08/26/26 or later: the LIST cursor gained
   `PRSUBD AS PJSUBDATE` for the Overview's Submitted column and
   `PRWRKSTS AS PJWRKSTS` for the Work Status (until the recompiled
   procedure is on the box the Submitted column shows blank and every
   open project's status reads "Not set"),
   and the NOTES read now locates the WebNotes index itself — the file's
   name and library are site-specific (they live only in the web tree's
   `WebNotes/webNotesModel.php`), so at run time the procedure finds the
   file carrying the five `WN*` columns in `QSYS2/SYSCOLUMNS` (preferring
   a physical file and a non-`*DEV*` library) and reads it directly. No
   library list, on any job, is involved. If the read still fails, the
   weekly summary generates anyway — comment counts are skipped and the
   card's meta line says so.
3. Authority: both screens and the endpoint require `LCCONLINE` level 20,
   the developers group (level 10 is only the minimum to use LCC Online).
4. The Excel download uses the vendored PhpSpreadsheet at
   `/www/seidenphp/htdocs/vendor/autoload.php`, same as the other loaders.

## The stage/status mapping — the one thing to review

The mapping lives in exactly two functions in
`ProjectTracking_model.php` — `prjStage()` and `prjStatus()` — and every
screen, the Excel download and the weekly summary all read from them:

- **Stage** (pipeline): the green screen never carried a single "SC stage"
  column, so the dashboard derives it: `rejected` (PRRESCOD = REJ) →
  `complete` (PRACOM set) → `approved` (SC priority set) → `new` (no
  estimate yet) → `parked` (estimated, dept priority zeroed) → `needsinfo`
  (estimated, no scheduled date) → `awaiting` (estimated + scheduled,
  waiting on the committee). `rejected` and `complete` still come back from
  `prjStage()` for the by-developer page, but they are not pipeline cells —
  the pipeline card shows the live SC pipeline, where neither can appear.
- **Status** comes in two flavors:
  - The **by-developer column and the Excel download** show the green
    screen's own **Work Status** (`PRWRKSTS`, the dropdown on the project
    edit screen), read as-is — it moves the moment someone changes it on
    the edit screen. The dropdown stores short codes, so
    `$GLOBALS['prjWrkLabels']` in the model spells them out (`ACT` →
    Active, `HLD` → Hold, `WUF` → Waiting user feedback); an unlisted
    code still shows under its stored value, and a blank one reads
    `Not set`.
  - The **donut** shows the layout template's four buckets over the
    **assigned working set** (open pipeline projects sitting with a
    tracked developer), derived by `prjStatusBucket()` the way the first
    design did: `Est. not needed` (fire projects, type FR) → `On hold`
    (estimated, dept priority zeroed) → `Active` (scheduled completion
    date on file) → `Waiting on user`. Most projects carry no stored Work
    Status, so a PRWRKSTS donut would be one gray "not set" ring — the
    derived buckets classify every assigned project.

The stage and bucket rules are a best-effort reading of how the legacy
code used the fields. If the steering committee draws a stage or a donut
bucket differently, change `prjStage()` or `prjStatusBucket()` — nothing
else needs touching.

## How "open projects" is counted — matching the monthly spreadsheet

The project file carries every never-closed record back to the 1990s, so a
raw "not complete, not rejected" count lands around 220 — far above the ~89
the monthly *Projects by developer* spreadsheet tracks. The spreadsheet is
built from the four PTS report extracts on the legacy `PROJ_Reports` screen,
so the dashboard scopes itself to that same universe, the **SC pipeline**:

- **SC workload** — `PTS0035S` (reads the `PRWKLDP` work file)
- **Projects submitted** — `PTS0036S(from, to)` for the current meeting
  window (Monday before the previous first-Thursday SC meeting through the
  Sunday before the next — the same calculation `PROJ_Reports_ctl.php` makes)
- **Projects for SC review** — `PTS0038S`
- **Formula Friday projects** — `PTS0039S`

`prjPipelineNums()` in `ProjectTracking_model.php` calls all four and unions
the project numbers; `prjMarkPipeline()` stamps each project row `PIPE` 1/0.
An open project on none of the four reports is **stale** — old work nobody
closed out. Stale records:

- are excluded from every dashboard number and chart (hovering the Open
  stat says how many were left out),
- stay off the by-developer page and the Excel download entirely — the
  screens show the working list, like the monthly spreadsheet. The ajax
  endpoint still honors `complete=Y` / `stale=Y` for ad-hoc pulls.

The developer groups themselves are pinned to the team the monthly
spreadsheet tracks: `$GLOBALS['prjDevelopers']` at the top of
`ProjectTracking_model.php` (CMCBETH, DCOTE, GCHAU, JTAYLOR, KRAINVILLE,
TCONNOLLY). The by-developer page, the programmer filters, the load chart
and the workbook show those profiles plus Unassigned and no one else — a
row assigned to any other profile stays out of those views. Edit that one
list when the team changes.

Two caveats. `PRWKLDP` is rebuilt by the Reports screen's *Submit SC
Reports* button, so the workload slice is only as fresh as the last refresh
before the meeting. And the report procedures return different record
layouts, so the project-number column is found by **validation**, not by
name: for each report, the column whose values line up with the most real
project numbers wins, and a report whose columns match nothing contributes
nothing. If the reads produce no usable numbers at all — or the resulting
set matches not one open project (`prjPipelineCheck`) — the dashboard falls
back to counting every open record and says so under the Open stat, rather
than presenting an empty pipeline as the truth. Each dashboard load logs
what every report proc returned (row count, chosen column, matches) to the
activity log, and the same line rides in the JSON response as `pipeinfo` —
check either one first when a count looks wrong.

## Weekly AI summary

The dashboard's "Weekly activity summary" card shows a cached, per-developer
write-up of the last finished week (Mon–Sun): where each developer's time
went (`PRTIMEP` hours by project), their comment activity, and completions —
the same ground the hand-written "Project by Dev" spreadsheet covered.

Comments contribute more than counts: the digest reads each week's comment
**text** off the IFS the same way the legacy project screen does (the index
row names the folder, the file is `prefix + project + date + time`), strips
the HTML, and hands the words to the writer so the summary can say what was
actually done or decided — progress, blockers, who is being waited on — not
just how many notes were left. Text is capped (1,200 chars per comment,
~15k per digest) so a heavy week cannot overrun the prompt; the counts
always cover every comment. Period matching goes by the comment's posted
date, not dates written inside the text.

The digest also carries the period's **change history** (`CHGLOG` read of
`PRCHGLOGP`, the same audit file the emailed change notices run on): short
one-liners per developer - status moves, new estimates, reassignments,
saves - so the summary can say what moved on a project even when nobody
wrote a comment. Both the comment text and the change history need the
current `PRJTRK001S`; on an older compile the summary still generates and
the card's meta note says which feed was skipped. This needs the current `PRJTRK001S`, whose NOTES set
returns the path/time columns that name each file — on an older compile the
summary still generates from counts alone.

- **Generate** posts `action=weeklygenerate`. The model builds a JSON digest
  from Db2 and sends it through `prjGeminiJson()` — a copy of the Sellbrite
  loader's `geminiJson()` caller (JSON-mode `generateContent`, thinking
  budget, meta call report, activity-log lines) against `gemini-3.7-flash`.
  The result caches in
  `/www/seidenphp/ProjectTracking_data/` (created on first write; the web
  profile needs write access there) as
  `projecttracking_weekly_<weekend>.json` plus a `_latest` copy the
  dashboard reads. One run per week is the intended cadence; a week's
  digest is a few thousand tokens, so a run costs pennies.
- **The cache deliberately lives outside the htdocs tree.** The digest is
  per-developer activity data, and `LCCOnline_logs` is both web-served and
  emptied monthly by the Clario purge job — so neither the cache nor the
  key belongs there. Only the activity log stays in `LCCOnline_logs`, like
  every other tool's.
- **The summary only restates the digest.** The prompt forbids inventing
  projects or numbers, and the digest rides along in the cache file so a
  summary can always be checked against its data.
- **API key:** the `GEMINI_*` define block at the top of
  `ProjectTracking_model.php` carries the key, model, base URL and
  timeout — the same block, written the same way, as
  `SellbriteBulkLoader_agent.php`. Nothing extra to configure at deploy.
- **No key / API failure:** the card still works — it falls back to a plain
  deterministic rollup of the same digest and says so in the meta line.
- **Scheduling:** the button is the v1 workflow (like the monthly PTS
  reports submit). To automate, schedule a job that hits
  `ProjectTracking_ajax.php?action=weeklygenerate` as an authorized profile
  each Monday morning.

## Charts

The bar chart and donut are hand-drawn inline SVG — no charting library, so
nothing new to vendor and nothing that breaks without internet access. Blue
is the working color; red is reserved for the Unassigned bucket, matching
the layout template. Both charts have hover tooltips, and the table below
the charts doubles as the accessible/tabular view of the same data.

The Documentation folder's BI-tools write-up (Power BI etc.) still applies
for heavier analysis: the `dashboard`/`assignments` JSON actions are clean
feeds a BI tool can pull from later without touching these screens.

## Removed legacy files

Removed as duplicates or dead weight (kept in git history):

- `PROJ_allAllComntView_ctl/dsp.php` — near-duplicate of `PROJ_allComntView`
- `PROJ_EmlMsgMaint.php` — copy-paste of the tooltip maintenance page for
  email templates; retired `i5_*` API; not project-tracking specific
- `PROJ_sendChgNotif.php` — dev harness on the retired `i5_*` API with a
  hardcoded profile password in source
