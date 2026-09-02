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
| Db2 procedure | `PRJTRK001S.PROC` | One read-only procedure, `INTYPE` selects the result set: `LIST` (projects + newest estimate + summed hours), `TIME`, `COMP`, `PGMR`, `CHGLOG` (PRCHGLOGP change history). Comments are not here — the model reads the WebNotes index directly |

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
   open project's status reads "Not set"), and `CHGLOG` for the
   report's change-history section. The comment feed does not go through
   the procedure, so nothing about it changes at compile time.
3. Authority: both screens and the endpoint require `LCCONLINE` level 20,
   the developers group (level 10 is only the minimum to use LCC Online).
   `PRJTRK001S` compiles with `DYNUSRPRF(*OWNER)`, so when it moves to
   `LSCPRDLIB` its owner and `*PUBLIC` authority should match the PTS
   procedures beside it.
4. The Excel download uses the vendored PhpSpreadsheet at
   `/www/seidenphp/htdocs/vendor/autoload.php`, same as the other loaders.

## Libraries — how names are resolved, and what to watch

Procedure calls go out **unqualified first**, so the job's library list
decides which copy answers — the same way the legacy screens work, and the
reason a developer can put a test library ahead of `LSCPRDLIB` and exercise
this code against test data. Only when the plain name fails to prepare does
`prjFetchAll()` retry it library-qualified (`PRJ_PROC_LIB` for
`PRJTRK001S`, `PRJ_LEGACY_LIB` for `PHP0003S`), and it writes a `LIBRARY`
line to the activity log when it does — a fallback means a job's library
list is short, which is worth knowing rather than papering over.

Comments are the exception, and deliberately so. The index read is written
`LSCPRDLIB.WBNOTEIDXP` in `PRJ_NOTES_FILE`, hard-qualified, because there
are 29 copies of that file on the box and only the production one holds
current rows — the dev copy stops at 2019. The comment text is read from
`PRJ_WEBNOTES_DIR` for the same reason, after this instance's own
`WebNotes/` folder. So the dashboard reports on real comments whether it
runs under `seidendev` or `seidenphp`. If the direct read is ever refused,
`prjNotes()` falls back to `CALL PHP0003S` project by project and says so
in `prjNotesNote`.

`PRJ_DATA_DIR` is the opposite case: it is derived from this file's own
location, so each instance caches its weekly summaries beside itself
instead of dev overwriting production's.

Project-number links follow the same rule. `PROJ_ctl.php` only works
against production, so `prjLegacyBase()` returns `PRJ_LEGACY_URL` when the
dashboard is running anywhere else and an empty string when it is running
under `PRJ_PROD_ROOT` — dev links out, production stays relative, and
nothing needs changing when this promotes. The pages call `projUrl(num)`
rather than writing the path themselves. Following such a link from dev
lands on production's sign-on first, which then returns to the project.

Two things to settle before this is fully production:

- **`PRJTRK001S` still lives in `LSCDEVLIBP`**, a development library, while
  the screens run in production. `seidendev`'s library list carries that
  library and `seidenphp`'s does not, which is why the same page can read
  hours on one instance and fail on the other — the fallback covers it for
  now. It belongs in `LSCPRDLIB` next to the `PTS00xxS` procedures; when it
  moves, change `PRJ_PROC_LIB` to `LSCPRDLIB` and the two agree again.
- **The cursors inside the procedure hard-qualify their files**
  (`LSCPRDLIB/PRPROJP`, `LSCPRDLIB/PRTIMEP`, …). That is deliberate but it
  means a job whose library list points at test data still reads
  production through this procedure, and a file-level `OVRDBF` will not
  redirect it the way an unqualified read would. Leave it qualified for a
  production-only tool; drop the `LSCPRDLIB/` prefixes if the procedure
  should follow the library list like the rest of the shop.

## Programmers on a project

Since 09/02 a project can carry more than one programmer, each with a
status and start date of their own, and comments filed under each name.
The data lives in `PRPGMASGP` / `PRPGMCMTP` behind `PRJTRK002S`; the
project screen draws the panel from `PROJ_pgmrs_dsp.php`; the dashboard
adds one row per additional programmer (`prjWithAssignments()`, flagged
`ADDL`) and the weekly digest reads their comments (`CMRANGE`). The
design, the file layouts and the order to install things are in
*Project programmers - design.md* beside this file. The status wording
itself now comes from `PRSTATUSP` through the `STATUS` read, so a code
added to the dropdown shows up here with no code change.

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
- **Status** is the green screen's own **Work Status** (`PRWRKSTS`, the
  dropdown on the project edit screen), read as-is — it moves the moment
  someone changes it there. The by-developer column, the Excel download
  and the donut all count the same value, so the donut and the column can
  never disagree; the donut's total is the **assigned working set** (open
  pipeline projects sitting with a tracked developer).

  The dropdown stores short codes, so `$GLOBALS['prjWrkLabels']` spells
  them out: `ACT` → Active, `HLD` → Hold, `WUF` → Waiting user feedback,
  `INQ` → In Queue. An unlisted code still shows under its stored value.
  A blank one reads `Not set`, except on a fire project (type `FR`) where
  it reads `Est. not needed`.

  Adding a status is three edits: the code and its wording in
  `prjWrkLabels`, the key in `$GLOBALS['prjStatuses']` if it should be
  listed before any project carries it, and a color — `renderDonut()` in
  `ProjectTracking_ctl.php` and a `.pt-st-*` rule in the display file both
  pick their color off the **wording**, not the code, so one word in the
  label is what ties them together. `$GLOBALS['prjWrkAlias']` folds two
  codes onto one status when the file's spelling is not settled.

The stage rules are a best-effort reading of how the legacy code used the
fields. If the steering committee draws a stage differently, change
`prjStage()` — nothing else needs touching.

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

Comments contribute more than counts. The digest reads the period's
comment index rows in one statement — `LSCPRDLIB.WBNOTEIDXP`, prefix
`PROJ_`, `WNDATE` between the period's dates — then reads each comment's
**text** off the IFS (`prefix + project + date + time`), strips the HTML,
and hands the words to the writer so the summary can say what was actually
done or decided — progress, blockers, who is being waited on — not just how
many notes were left. Only `ComntIT` comments feed the per-developer
sections; a comment on a project with no hours that week still counts.
Text is capped (1,200 chars per comment, ~15k per digest) so a heavy week
cannot overrun the prompt. Period matching goes by the comment's posted
date, not dates written inside the text.

The digest also carries the period's **change history** (`CHGLOG` read of
`PRCHGLOGP`, the same audit file the emailed change notices run on): short
one-liners - status moves, new estimates, reassignments, saves - so the
summary can say what moved on a project even when nobody wrote a comment.
The change history needs the current `PRJTRK001S`; on an older compile the
summary still generates and the card's meta note says the feed was skipped.

- **Generate** posts `action=weeklygenerate`. The model builds a JSON digest
  from Db2 and sends it through `prjGeminiJson()` — a copy of the Sellbrite
  loader's `geminiJson()` caller (JSON-mode `generateContent`, thinking
  budget, meta call report, activity-log lines) against `gemini-3.7-flash`.
  The result caches in
  `ProjectTracking_data/` beside the running instance (`/www/seidenphp/…`
  in production, `/www/seidendev/…` on dev; created on first write, the
  web profile needs write access there) as
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
