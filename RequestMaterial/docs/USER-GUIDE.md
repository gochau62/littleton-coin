# Requisition Material - User Guide

Updated 7/24/2026

Requisition Material is where inventory handlers request coins and
material out of the vault, and where those requests are tracked until the
material comes back. It runs in the browser, so there is nothing to
install and no database file to open.

## Two ways in

| Link | Who uses it | What you get |
|---|---|---|
| `Requisitions_ctl.php?mode=entry` | Workfloor and inventory handlers | Just the entry form. A fresh blank form appears after every submit. **This is the one to bookmark.** |
| `Requisitions_ctl.php` | IT and supervisors | The full station: the grid, updates, returns and reports. |

## Entering a requisition

1. Pick your name from the **Requestor** list. The date fills itself in,
   you never touch it.
2. Set **Rush** to Yes or No.
3. Pick the **Area Code** and **Area Type**. Leave Authorized By alone
   unless you already have an authorizer.
4. Fill in the lines. Click into **Item #** and the item list opens: type
   to narrow it, then pick with a click, Tab or Enter, and the
   description, coin date, cost and retail fill in for you. Arrow keys
   move around the sheet like a spreadsheet, Enter jumps to the next box,
   and Enter on the last box starts a new line. The gray X removes a line.
5. Add **Comments** if you have any.
6. Press **Insert**. You get the new requisition number back and a fresh
   form.

If a box turns red, fix it before submitting: quantity has to be a number
above zero, and the dollar boxes have to be numbers. If the save fails,
**nothing is saved** and the message tells you which line to fix. There
is no such thing as a half saved requisition.

## The station grid

Each requisition line takes two rows: the details on top, the description
underneath. The grid opens on **open lines only**, meaning anything not
yet returned.

- **Show** switches between **Open**, **Returned** and **All**. This is
  how you bring a returned req form back up and see when it came back.
  Returned and All list the 500 most recent, so use Filter to reach older
  ones.
- **Filter** narrows the grid by req number, name, item or badge. On
  Returned and All it searches the whole history, not just what is on
  screen.
- **Sort** by clicking any column heading. Click it again to flip the
  direction, and the small arrow shows which way. The grid starts on Date
  with the newest first.
- **Badge #** can be edited right in the grid. Click the box for the
  employee list or type a number. New requisitions start at 0 and someone
  fills in the real badge later.
- **Return Item** puts today's date beside the tick box. Change the date
  if the material actually came back earlier. Nothing is saved until the
  next refresh, so untick the box to cancel.
- Click a row to select it. The marker shows which requisition **Preview
  Report** will print.
- Click the blue **req number** to open that requisition.
- The grid refreshes itself every minute. If the Updated time turns red
  the connection hiccupped, so press **Refresh**.

## Opening a requisition

Clicking the blue req number opens the requisition window.

- Pick a name under **Authorized By** and press **Update**. Picking a real
  person is what marks it authorized, there is no separate approval step.
- Every line is listed, open and returned, with a **Date Ret.** column. A
  turned in form therefore shows who requested it, how much, and when it
  came back, all in one place.
- Each line has its own **Returned** tick box that saves straight away.
  This window is also the only place to undo a return.
- **Print** gives you the paper copy of this requisition.

## What the colors mean

- **Yellow** pill: nobody has authorized it yet.
- **Green** pill: a real authorizer's name is on it.
- **Red RUSH** pill: the requisition was marked rush.
- **Red box** on the entry sheet: fix that value before you submit.

## Reports

| Button | What it gives you |
|---|---|
| Monthly Report | Monthly Update: Requisitioned Product. It opens on the **current month**, and you change it with the Month and Year lists. Grouped by requestor with totals, plus a **Returned** column showing the return date, or a dash while the item is still out. |
| Preview Report | The paper copy of the **selected** requisition, so click a row first. It lists every line with its return date. |

Print brings up your normal print box on the page you are already on. No
extra window opens, so close the print box and you are back where you
were.

## Good to know

- The old links stopped working at cutover. Use the new shortcut, and ask
  IT if you do not have it.
- It works in any browser now.
- **Nothing is ever deleted.** Returned requisitions are still there, and
  the Show list brings them back.
- Change not showing up? **Ctrl+F5** forces a fresh load.
- A blank page or an error box is on our end, not something you did.
  Contact IT.
