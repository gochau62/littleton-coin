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

The entry form is the whole job for most people: four steps, top to
bottom, and you are done.

### Step 1: Say who is asking

Five boxes across the top describe the whole requisition. You only touch
four of them.

1. **Requestor:** pick your name from the list.
2. **Date:** fills itself in and keeps ticking. You never touch it.
3. **Rush:** Yes or No.
4. **Area Code** and **Area Type:** where the material is going.
5. **Authorized By:** leave it alone unless you already have an
   authorizer. Someone can set it later.

### Step 2: Add the item lines

The lines behave like a spreadsheet, so you can stay on the keyboard the
whole way through.

- Click into **Item #** and the item list opens. Type to narrow it, then
  pick with a click, Tab or Enter.
- Picking an item fills the description, coin date, cost and retail for
  you. Typing a full item number and tabbing out does the same thing.
- **Arrow keys** move around the sheet. Up and down change rows, left and
  right move between boxes.
- **Enter** jumps to the next box, and Enter on the last box starts a new
  line.
- The gray **X** at the end of a line removes it.

If an item is not in the list it is not in inventory under that number.
Check the number before you type it in by hand.

### Step 3: Watch the colors

- **Red box:** fix this before you submit. Quantity has to be a number
  above zero, and the dollar boxes have to be numbers.
- Everything else: you are fine to submit.

### Step 4: Insert

Add **Comments** if you have any, then press **Insert**. You get the new
requisition number back and a fresh blank form.

If the save fails, **nothing at all is saved** and the message names the
line to fix. There is no such thing as a half saved requisition, so fix
that line and submit again.

## The station grid

This is the supervisor view. Each requisition line takes two rows: the
details on top, the description underneath. It opens on **open lines
only**, meaning anything not yet returned.

- **Show:** switches between **Open**, **Returned** and **All**. This is
  how you bring a returned req form back up and see when it came back.
  Returned and All list the 500 most recent, so use Filter to reach older
  ones.
- **Filter:** narrows the grid by req number, name, item or badge. On
  Returned and All it searches the whole history, not just what is on
  screen.
- **Sort:** click any column heading, and click it again to flip the
  direction. The small arrow shows which way. The grid starts on Date
  with the newest first.
- **Badge #:** edit it right in the grid. Click the box for the employee
  list or type a number. New requisitions start at 0 and someone fills in
  the real badge later.
- **Return Item:** ticking it puts today's date beside the box. Change
  the date if the material actually came back earlier. Nothing is saved
  until the next refresh, so untick the box to cancel.
- **Refresh:** the grid also refreshes itself every minute. If the
  Updated time turns red the connection hiccupped, so press it yourself.
- **Clicking a row** selects it, and the marker shows which requisition
  Preview Report will print. **Clicking the blue req number** opens that
  requisition.

## Opening a requisition

Clicking the blue req number opens the requisition window.

- **Authorized By:** pick a name and press **Update**. Picking a real
  person is what marks it authorized, there is no separate approval step.
- **Date Ret.:** every line is listed, open and returned, with the date it
  came back. A turned in form therefore shows who requested it, how much,
  and when it came back, all in one place.
- **Returned:** each line has its own tick box that saves straight away.
  This window is also the only place to undo a return.
- **Print:** the paper copy of this requisition.

## What the colors mean

- **Yellow** pill: nobody has authorized it yet.
- **Green** pill: a real authorizer's name is on it.
- **Red RUSH** pill: the requisition was marked rush.
- **Red box** on the entry sheet: fix that value before you submit.
- **Green date** in the Returned column: the date that line came back. A
  dash means it is still out.

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
