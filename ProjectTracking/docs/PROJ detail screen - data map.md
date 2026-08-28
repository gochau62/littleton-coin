# PROJ detail screen — data map and rewrite plan

The screen at `PROJ_ctl.php?projnum=...` is the legacy project detail page.
Its source is in this repo at `Legacy/PROJ_ctl.php` (display + inline JS),
with `Legacy/PROJ_model.php` doing every database call,
`Legacy/PROJ_ajax_request.php` / `PROJ_ajax_request_post.php` handling the
in-page updates, and `Legacy/PROJ_saveTime.php` +
`PROJ_timeEntry_ctl.php` handling time. On the server they sit in the
LCCOnline docroot. Nothing on the screen talks to the database directly —
everything goes through `PTS00xxS` stored procedures.

## Where every piece of data lives

| What you see on the screen | Stored in | Read by | Written by |
|---|---|---|---|
| Project master — description, requestor, dept/sub-dept, sponsor, submitted/need dates, estimator, **programmer assigned**, dev group, **work status**, brand, parent project, priorities, scheduled start / sched impl / actual impl dates, resolution code | `LSCPRDLIB/PRPROJP` (one row per project, `PR#` key) | `PTS0001S`-era read (`getRecordPRPROJP`) | `PTS0027S` — one 48-parameter insert/update proc behind Save Changes (`instupdtRecPRPROJP`) |
| Estimates (original + current low/hi, who, when) | `LSCPRDLIB/PRESTMTP` — insert-only history; newest row wins | `getRecsPRESTMTP` | `PTS0006S` (`insertRecPRESTMTP`) — estimates are never edited, a new row is added |
| Programmer time to date (per developer and total) | `LSCPRDLIB/PRTIMEP` — one row per person/project/day (`PT#`, `PTPGMR`, `PTDATE`, `PTTIME`) | `getProjUserTime` | `PT0029S` via `PROJ_saveTime.php` (the Project time entry screen) |
| **Comments** (General / IT / SC / Description) | Two places: the text lives in **IFS files** under `WebNotes/PTS/` in the docroot, one file per comment named `PROJ_` + project + date + time; the **index** (who, when, type, path) is a WebNotes Db2 file (`WN*` columns — its name and library live in `WebNotes/webNotesModel.php`) | `getRecordsWebNotes` (WebNotes model) | The WebNotes add/edit/delete handlers write the file + index row; the screen's `logcomment` ajax then stamps an audit row |
| Change history / audit ("General comment added", field changes) | `LSCPRDLIB/PRCHGLOGP` (project, date, time, user, source, text) | `getRecsPRCHGLOGP` | `insertPRCHGLOGP` — fed by `logcomment` and by save actions; `applyRules` + `PRNTFPRFP` prefs turn it into the emailed change notices |
| Work status dropdown choices (Active, Hold, Waiting user feedback...) | `LSCPRDLIB/PRSTATUSP` | `PTS0024S`-era read (`getRecsPRSTATUSP`) | maintained on the green screen |
| Estimator / programmer dropdown | `LSCPRDLIB/PRIDTRANSP` | `getPgmrListPRIDTRANSP` | maintained on the green screen |
| Dev Group dropdown | `LSCPRDLIB/PRGROUPP` | `getRecsPRGROUPP` | maintained elsewhere |
| Sponsor dropdown (by dept) | `LSCPRDLIB/PRSPNSRP` | `getSpnsrByDeptPRSPNSRP` | maintained elsewhere |
| Project type / resolution code lists | `PRTYPEP` / `PRRESCODEP` | `getRecsPRTYPEP` / `getRecsPRRESCODEP` | maintained elsewhere |
| Payback tab | `PRPAYBCKP` (+ recalc in `recalcProjPayBack`) | `getRecsPRPAYBCKP` | save actions |
| Who may edit what (PM authority etc.) | `PRAUTHP` | `getRecPRAUTHP` | maintained elsewhere |
| Field tooltips (the dotted labels) | `PRTOOLTIPP` | `getRecsPRTOOLTIPP` | the screen's own tooltip editor |
| Change-notice preferences | `PRNTCTYPP` / `PRPLNDEFP` / `PRNTFPRFP` | `getRecs*` | `saveRecPRNTFPRFP` |

The dashboard's `PRJTRK001S` already reads four of these read-only:
`PRPROJP`, `PRESTMTP`, `PRTIMEP`, and the WebNotes index.

## Items worth pulling into Project Tracking first

Before any rewrite of the detail screen, these give the dashboard the most
for the least:

1. **`PRSTATUSP` for status labels.** The dashboard hardcodes
   `ACT/HLD/WUF` spellings in `prjWrkLabels`; reading `PRSTATUSP` (a
   `STATUS` type in `PRJTRK001S`) would show exactly what the dropdown
   shows, and new codes would appear with no code change.
2. **`PRCHGLOGP` for the weekly report.** One file already records every
   comment, save, and change with user/date/time — a `CHGLOG` read for the
   period would let the summary say what changed on each project (status
   moves, new estimates, reassignments), not just hours and comments.
3. **Dev Group (`PRITDEVGRP` + `PRGROUPP`).** The ERP/WEB/NET-style team
   tagging planned earlier is already a field on the master — a by-group
   view needs only the column added to the `LIST` read.
4. **Per-developer time on a project (`getProjUserTime` shape).** The
   by-developer page shows total hours; the same `PRTIMEP` data can split
   hours by person for shared projects, like the screen's "Programmer time
   to date" box.
5. **Scheduled start date and brand** — already in `PRPROJP`, one column
   each on the `LIST` cursor if a view needs them.

## For the eventual rewrite of the detail screen

- Keep every write going through the existing procs (`PTS0027S`,
  `PTS0006S`, `PT0029S`) — the green screen and the reports depend on the
  same files, and the procs already carry the business rules.
- The WebNotes split (index row + IFS text file) is the one design worth
  replacing: a single comments table would simplify every reader,
  including the weekly digest.
- `PRCHGLOGP` writing should stay exactly as is — the change-notice
  emails feed off it.
- The new screens' house rules apply: fit beside the LCC menu, StartBlock
  at the top, one-line comments, authority via `chkAutUsr` level 20.
