# Requisition Material - User Guide

Updated 7/24/2026

Requisition Material replaces the Access "Req Station" database and the
old request.php pages. Inventory handlers enter requisitions for material
pulled from the vault; the station screen is where those requisitions get
tracked, updated and marked returned. Everything runs in the browser -
no Access, no shared .mdb file, any modern browser works.

## Two ways in

| Link | Who | What you get |
|---|---|---|
| `Requisitions_ctl.php?mode=entry` | Workfloor / inventory handlers | The entry form and nothing else. A fresh blank form appears after every submit. This is the favorited shortcut. |
| `Requisitions_ctl.php` | IT / supervisors | The full station: the open-requisitions grid, updates, returns and reports. |

## Entering a requisition

1. Pick your name from the Requestor list. The date runs on its own - it
   is the live clock, you never touch it.
2. Rush: Yes or No.
3. Pick the Area Code and Area Type. Authorized By starts at
   "Authorization = None" - leave it there unless you already have an
   authorizer.
4. Fill the line sheet:
   - Click into Item # and the item list opens; type to narrow it.
     Pick one (click, Tab or Enter) and the description, coin date, cost
     and retail fill in from inventory. Typing a full item number and
     tabbing out does the same fill.
   - Arrow keys move around the sheet like a spreadsheet: up/down between
     rows, left/right between cells.
   - Enter hops to the next box, like the old form. Enter on the last box
     of the last line starts a new line.
   - The gray ✕ at the end of a line removes it.
5. Comments if you have them.
6. Hit Insert. You get the new requisition number back and a fresh form.

If a box turns red, fix it: quantity has to be a number greater than
zero, and the dollar boxes have to be numbers. If anything fails on the
server, **nothing** is saved - the message tells you which line to fix,
then submit again. No half-saved requisitions.

## The station grid

Each requisition line is two rows, like the old Access screen: the
details on top, the description underneath. The grid opens on **open
lines only** - anything not yet returned - just like Access.

- **Show** dropdown: switch between **Open** (the default), **Returned**
  and **All**. This is how you bring a returned req form back up - pick
  Returned and it lists the lines that have been turned back in, each
  with the date it was returned. All shows open and returned together.
  Because there are ~50,000 returned lines, Returned and All show the
  **500 most recent** (newest returns first); type in the Filter box to
  reach older ones.
- It refreshes itself every minute (and when you come back to the tab).
  The Updated time in the corner tells you the last refresh; if it turns
  red the connection hiccupped - hit Refresh.
- The **Filter** box narrows the grid by req #, name, item or badge. In
  Open it filters what is on screen; in Returned/All it searches the
  whole history, so you can pull up any old returned form, not just the
  recent 500.
- **Sort** by clicking any column header - Req #, Date, Requestor,
  Badge #, and so on. Click once for up, click again for down; a small
  arrow shows which way.
- Click a row to select it - the ▶ marker shows which requisition
  Preview Report will print.
- Click the blue req # to open the requisition.
- **Badge #** starts at 0 on a new requisition; the real badge is filled
  in later. It is editable right in the grid - click the box for the
  employee dropdown (type to filter by badge or name) or type a number.
  Only a real badge number saves; typed name text is ignored, and saving
  no longer jumps the page. All lines of the same requisition share one
  badge.
- **Return Item**: check the box and today's date fills in next to it -
  change the date if the item actually came back earlier. Nothing is
  saved yet: the return goes through on the next refresh (the Refresh
  button or the automatic one), and the line drops off the grid, same as
  the old screen. Uncheck before the refresh to cancel.

## Opening a requisition

The blue req # opens the requisition window - laid out like the old
request.php view: ID, name, area code/type, date, Inv DE Number, then
Authorized By and Comments.

- To update: pick the Authorized By name and/or edit Comments, hit
  **Update**. Picking a real person marks the requisition authorized;
  "Authorization = None" or "Authorization In Process" marks it not
  authorized. There is no separate authorize step - the value you pick IS
  the authorization.
- Every line is listed here - open and returned - with a **Date Ret.**
  column showing when each returned line came back, so a turned-in req
  form shows who requested it, the quantities and the return dates all
  in one place.
- Each line has its own Returned checkbox here. These save immediately
  and stamp today's date. This window is also where you UN-return a line
  - uncheck the box and it goes back to open.
- Print gives you the requisition report for this req.

## The colors

- Yellow pill - "Authorization = None" or "In Process": nobody has
  signed off yet.
- Green pill - a real authorizer's name.
- Red RUSH pill - the requisition was flagged rush.
- Red box on the entry sheet - fix that value before submitting.

## Reports

| Button | What it prints |
|---|---|
| Monthly Report | "Monthly Update: Requisitioned Product" - opens straight onto the **current month** (change it with the Month and Year dropdowns). Grouped by name with Req. Comments, Req. Totals and Totals by Name, and a **Returned** column showing the return date (or a dash if the item is still out). |
| Preview Report | The requisition report (old rptRequest) for the **selected** row - click a row first. Lists **every** line with a **Returned** column (return date, or a dash if still out). |

Print opens the report in its own window, shows the print dialog, and
the window closes itself when you're done - printed or cancelled.

## Good to know

- The old request.php links died at cutover. Use the new shortcut; if you
  don't have it, ask IT.
- Works in any browser now - the Firefox-only restriction is gone.
- Nothing is ever deleted: the Show dropdown brings returned req forms
  back up, and opening any requisition by its number (`?id=N` on the
  station link) shows all of its lines, returned included.
- Change not showing up? Ctrl+F5 forces a fresh reload.
- Blank page or an error box - that's on our end, not something you did.
  Contact IT.
