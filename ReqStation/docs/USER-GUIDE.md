# Requisition Material - User Guide

Updated 7/23/2026

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
   - Type the Item # . After two characters a dropdown appears with
     matching items - pick one and the description, coin date, cost and
     retail fill in from inventory. Typing a full item
     number and tabbing out does the same fill.
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
details on top, the description underneath. The grid shows **open lines
only** - anything not yet returned.

- It refreshes itself every minute (and when you come back to the tab).
  The Updated time in the corner tells you the last refresh; if it turns
  red the connection hiccupped - hit Refresh.
- The filter box narrows the grid by req #, name, item or badge.
- Click a row to select it - the ▶ marker shows which requisition
  Preview Report will print.
- Click the blue req # to open the requisition.
- **Badge #** is editable right in the grid. Click the box for a dropdown
  of employees (type to filter by badge or name), or type the number -
  Enter or clicking away saves immediately. All lines of the same requisition
  share one badge.
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
- Each line has its own Returned checkbox here. These save immediately
  and stamp today's date. This window is also the only place to
  UN-return a line (the grid can't - returned lines aren't on it).
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
| Monthly Report | "Monthly Update: Requisitioned Product" for the month you pick from the Month and Year dropdowns - grouped by name with Req. Comments, Req. Totals and Totals by Name, same layout as the old printed report. |
| Preview Report | The requisition report (old rptRequest) for the **selected** row - click a row first. Shows unreturned items only. |

Print opens the report in its own window, shows the print dialog, and
the window closes itself when you're done - printed or cancelled.

## Good to know

- The old request.php links died at cutover. Use the new shortcut; if you
  don't have it, ask IT.
- Works in any browser now - the Firefox-only restriction is gone.
- The grid is open lines only, but nothing is ever deleted: open any
  requisition by its number (`?id=N` on the station link) to see all of
  its lines, returned included.
- Change not showing up? Ctrl+F5 forces a fresh reload.
- Blank page or an error box - that's on our end, not something you did.
  Contact IT.
