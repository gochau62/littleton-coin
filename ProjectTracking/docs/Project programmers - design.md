# Project programmers — In Queue, several programmers per project, comments under each name

Three asks from the 09/01 review of the project detail screen
(`PROJ_ctl.php`, IT Stuff tab), and what was built for each.

## 1. "In Queue" in the Programmer work status dropdown

The dropdown is not hard-coded. `getRecsPRSTATUSP()` in `PROJ_model.php`
calls `PTS0024S`, which reads `LSCPRDLIB/PRSTATUSP` (`PRSCODE`, `PRSDESC`).
So the status is **one row in that file** — `PRSTATUSP_INQUEUE.SQL` adds
`INQ` / `In Queue` if it is not already there. No PHP change makes it
appear; the screen and the green screen both pick it up.

The dashboard already understands `INQ` (and `QUE` as an alias) and colors
it pink. With the recompiled `PRJTRK001S` it also reads the wording from
the same file (`STATUS` type), so whatever the dropdown says is what the
dashboard says.

## 2. The scheduled start date beside In Queue

A queued project is waiting for a start date, and the project master
already has one — `PRESTR`, the *Scheduled start date* lower on the tab.
Rather than add a second date, `PROJ_ctl.php` now appends a date input
right after the work-status dropdown that appears only while **In Queue**
is selected (`wrkStsChanged()` in `PROJ_JS_functions.js`). Picking a date
copies it into the scheduled-start field (`queueDateChanged()`), so the
existing Save writes it through `PTS0027S` untouched.

Both screens show it: the by-developer page prints "starts mm/dd/yyyy"
after an In Queue chip, from the new `PJSTRDATE` column on the `LIST`
read.

## 3. Several programmers on a project, each with a status and comments

The master has one programmer (`PRPGMR`) and one work status
(`PRWRKSTS`), and the green screen, the SC reports and `PTS0027S` all
depend on them. So the primary programmer **stays exactly where it is**,
and additional programmers live in two new files.

### Files (`PRJTRK_TABLES.SQL`, created in `LSCDEVLIBP` until promotion)

| File | One row per | Columns |
|---|---|---|
| `PRPGMASGP` | project + programmer | `PGAPROJ`, `PGAPGMR`, `PGAWRKSTS` (a `PRSTATUSP` code), `PGASTRDTE` (scheduled start), added/changed by-date-time |
| `PRPGMCMTP` | comment | `PGCSEQ` (identity), `PGCPROJ`, `PGCPGMR` (the name it is filed under), `PGCUSER` (who wrote it), `PGCDATE`, `PGCTIME`, `PGCTEXT` (4000), `PGCDELETE` |

Comments are flagged removed, never deleted, so the weekly report keeps
its history. Text is plain — no CKEditor, no IFS file — so every reader,
the weekly digest included, gets it from one SELECT.

### Procedure (`PRJTRK002S.PROC`, `DYNUSRPRF(*OWNER)`, `COMMIT(*NONE)`)

One procedure, one type per call, like `PRJTRK001S`:

| Type | Does |
|---|---|
| `PGLIST` | programmers on a project with status wording and their own hours from `PRTIMEP` |
| `PGSAVE` | add a programmer, or change their status / start date (update, insert when absent) |
| `PGDEL` | take a programmer off |
| `CMLIST` | live comments on a project |
| `CMADD` | file a comment under a programmer |
| `CMDEL` | flag a comment removed |
| `ASGN` | every additional programmer on open projects — the dashboard's read |
| `CMRANGE` | comments dated between two dates — the weekly report's read |

Parameters: `(INTYPE, INPROJ, INPGMR, INSTS, INDATE, INDATE2, INUSER,
INTEXT, INSEQ)`. `PRJTRK001S` does **not** reference the new files, so it
compiles and runs before they exist.

### Screen (`PROJ_pgmrs_dsp.php`, new)

`renderProjPgmrPanel()` draws a panel under *Programmer time to date*:

- a table with the primary first (status and start read from the project
  fields above, no controls), then each additional programmer with a
  status dropdown (the same `PRSTATUSP` list), a start date, their hours
  and Remove;
- an *Add a programmer…* list of everyone on `PRIDTRANSP` not yet on the
  project;
- under the table, one block per programmer: their comments newest first,
  and a box to file a new one under that name.

Every change posts to `PROJ_ajax_request_post.php` (`pgmrSave`,
`pgmrRemove`, `pgmrCommentAdd`, `pgmrCommentRemove`), which writes through
`PRJTRK002S`, stamps a `PRCHGLOGP` row (source `Pgmrs`) so the change
notices and the weekly PROJECT UPDATES see it, and returns the redrawn
panel — the page never reloads. Who may change things: project managers,
the `*PGMR` class and `*SYSOPR`, the same rule as the tab's other fields;
a comment can be removed only by its writer or a project manager.

The panel is appended to `$screenData['pgmrTime']`, so it renders wherever
the display file already echoes the time box. Once `PROJ_dsp.php` is in
the repo it can be placed on its own line.

### Dashboard

- `prjWithAssignments()` adds one row per additional programmer to the
  project list, carrying **that person's** status and start date and
  flagged `ADDL`. The by-developer page lists the project under each
  programmer (chip says *shared*); the load chart and status donut count
  each assignment; the tiles, pipeline and dashboard table still count
  each project once under its primary.
- The weekly digest reads `CMRANGE` and files each comment under the
  programmer it was written for, type `PgmrCmt`, with `by` naming the
  writer — so a PM's note under a developer's name is reported as that
  developer's project activity.

## Order of operations on the box

1. `PRSTATUSP_INQUEUE.SQL` — the dropdown row (`LSCPRDLIB`).
2. `PRJTRK001S` recompile — `STATUS`, `CHGLOG`, `PJSTRDATE` and the
   status/subdate columns the screens already expect.
3. `PRJTRK_TABLES.SQL` — the two files.
4. `PRJTRK002S` — the procedure over them.
5. Copy the PHP: `PROJ_ctl.php`, `PROJ_model.php`,
   `PROJ_ajax_request_post.php`, `PROJ_JS_functions.js`,
   `PROJ_pgmrs_dsp.php` (new) into the LCCOnline docroot; the four
   dashboard files as usual.

Until steps 3–4 are done the panel prints one line saying the procedure
is not on the system yet, and the dashboard simply shows no additional
programmers. Nothing else on the screen changes.

## Still open

- **The display file.** `PROJ_dsp.php` is not in the repo; the panel and
  the queue date are injected through fields the display already echoes.
  With the file, both can be laid out properly.
- **Promotion.** The new files and both procedures reference
  `LSCDEVLIBP`; change to `LSCPRDLIB` in the three source members and in
  `PRJ_PROC_LIB` when they move.
- **The primary's own status per person.** Today the primary programmer's
  status *is* the project's work status. If the team wants the primary to
  carry a separate personal status too, add a `PRPGMASGP` row for them and
  stop reading `PRWRKSTS` in the panel — a small change, deliberately not
  made yet.
