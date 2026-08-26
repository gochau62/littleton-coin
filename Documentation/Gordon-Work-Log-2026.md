**Tuesday July 14th 2026, 10:00 AM: Initial Meeting with Brian**

* Met with Brian today. We are looking at migrating some of these reports and data entries off Access, since Microsoft is deprecating it, and moving them onto the IBM i; he mentioned building PHP files for them.
* Starting with the first report (the simplest), Requisition requests for coins, with report generation.
* There are several reports in Access that need to be transferred. We are starting with this one: it is built on Visual Basic code and uses Brian's MySQL database.
* The code is not in LCCOnline but in htdocs/Requisitions.
* Created the RFP environment to set up the migration of the code.

**Monday July 20th 2026: Microsoft Access Database Exploration**

* Friday, talked with Kyle about the Access migration. The work floor reaches the forms through Firefox shortcuts only, pointing at Request Material by Id and the Requisition Material form.
* Got access to Microsoft Access on Friday and explored the database: forms, tables, headers and data.
* Monday, got the MySQL database from Brian with a local data dump to test against.
* Implemented the front end for the main menu and created the tables with their headers.

**Thursday July 23rd 2026: Microsoft Access Migration RFP Completion**

* Created the full RFP on Tuesday with 3 new tables and 9 stored procedures that update and edit the PHP front end, replicating the Microsoft Access form exactly.
* Set up a meeting with developers Dan, Kyle and Brian, went over the assessed information and walked them through the program.
* Explored the use cases and the future implementation of this project. First we need to run through testing with the workflow inventory handlers; Topher Perez, the data entry handler, was emailed to explore the development application.

**Friday July 24th 2026: Documentation and Testing**

* Emailed Topher Perez. He came back quickly with strong, specific feedback on what could be improved, which gave us several ideas for modernising the form.
  * The email pointed him at both screens and made clear none of it is tied to production and the data is not live, so he could work through everything freely: the main station (the replacement for the Access form) at http://lcc1:8068/LCCOnline/Requisitions_ctl.php and the floor entry form at http://lcc1:8068/LCCOnline/Requisitions_ctl.php?mode=entry
* Completed documentation for a User Guide and a Technical Reference for future updates and handling.
* Agreed in the meeting on the rollout: test the requisition, email the floor a week before the live update, then email again after the transition with the updated link that redirects them to the new entry point.
* Documentation is at these links: Requisition Technical Reference and Requisition User Guide (Web view).

**Monday July 27th 2026: Updated request.php Redirect**

* Worked on the transitional code by reimplementing request.php, pulled from the seidentst Requisitions folder down into my RFP.
* Turned it into an id-checking handler for users who still have saved links to the editing requests by requisition id: http://lcc1:8068/LCCOnline/Requisitions_ctl.php?id=17177
* Redirected the original Requisitions/request.php to the new controller: http://lcc1:8068/LCCOnline/Requisitions_ctl.php?mode=entry
* Final stages of testing, then we should be able to promote and implement as needed. We may need to work out how to stop anyone who has the file saved and is editing it while we are working, so that the promotion syncs with the latest database as it is transferred into the tables from the SQL dump.

**Monday July 27th 2026, 2:00 PM: Story Card Maintenance Access Migration Planning**

* Downloaded the Access .mdb for the StoryCardMaintenanceXP database.
* This is the same process we followed for Requisitions, so we can start from there. No new documentation needed yet; start by taking the .mdb apart and explaining what is in it, what it does and how it works, then build something out.

**Monday August 3rd 2026: Story Card Maintenance — Access Database Exploration**

* Took StoryCardMaintenanceXP.mdb apart: forms, tables, linked tables and the VBA behind each form. The Access front end is a shell over three AS/400 files reached by ODBC — IVSCBODYP (card body lines), IVSCFOOTP (footer lines) and ITMMSTL1 (item master), all in LSCPRDLIB.
* Read the rules out of the code rather than assuming them from the form. Side 1 holds lines 1 to 11 and side 2 holds lines 13 to 21, at fifty characters a line; the guards in the save and the message text agree, and lines 12 and 22 can be read by the old screen but never written by it.
* Established that the footer is not per card. IVSCFOOTP has no SKU column: it is keyed by footer key and line number, and the body form loads it with scfsky=1 hardcoded, so every story card prints the same footer.
* Counted the SQL in the VBA: 18 inserts, 17 selects, 12 updates and no deletes anywhere. Nothing in the Access application ever removes a record, which sets the rule for the replacement.

**Tuesday August 4th 2026: Time Payment**

Tied the TimePayment procedures out to the real file layouts and fixed the OEPSRCE source code field to SRCCOD CHAR(6); condensed the procedure narratives to one-line summaries and kept every source line inside the member record length.

**Tuesday August 4th 2026: Story Card Maintenance — Db2 Procedures**

* Started with eight procedures mirroring the Access queries one for one, then folded them down to three using the same pattern as REQSTN007S, one procedure with a type switch: STYCRD001S reads (SKU list, item check, card lines, footer keys, footer lines), STYCRD002S writes one body or footer line, and STYCRD003S blanks the unused lines at the end of side 2.
* Comments out of the procedure bodies and project 260074 on the web headers, the same as Requisitions.
* Put the Access rules in the procedure rather than the screen: STYCRD002S refuses a blank SKU, refuses any line outside 1 to 11 or 13 to 21, and updates first and inserts only when nothing was updated, which is what CheckLineMode decided line by line in the old save.

**Wednesday August 5th 2026: Requisitions**

Badge handling reworked to match the green screen: the badge is entered after the request rather than derived from the requestor, sets once and locks, lists your own badge first, and a typed badge holds a tint until the refresh confirms it. Renamed Inv DE Number to Entered By and changed what it means — it is now whoever raised the requisition, taken from the sign-on rather than the form, so it stays true when one person raises a requisition for another.

Added WHOAMI to REQSTN007S: matches an active employee on first initial + last name against XEMPLOYP, falling back to the user profile's own description, then preferring the name the requestor list knows them by (Christopher Perez on payroll is Topher Perez on the list).

Established that the two authority levels are separate grants rather than a ladder — an entry action passes on either 10 or 41, everything else asks for 41 — and enforced it in Requisitions_ajax.php as well as on the screen, since the screen only decides what is drawn. Narrowed the activity log to what actually changes a requisition, naming every field a header change touched. Took the comments out of the stored procedure bodies. Moved line corrections into the requisition window, leaving the description in the grid. Seed page now takes all three CSVs in one pass.

**Wednesday August 5th 2026: Story Card Maintenance — Matching the Access Behaviour**

* Took out the delete I had built. The Access application has none, so the web one does not get one either: a failed save blanks the rows it wrote instead of removing them.
* Corrected the line capacity. I had taken 12 and 10 off the rulers drawn on the form, but the guards in the VBA and the message text both say 11 and 9.
* Switched the item master read from ITMMSTP to ITMMSTL1, the logical the .mdb actually links, since a logical can carry select/omit that the physical does not.
* Kept the old footer rule exactly: the save picks insert or update once, up front, from whether the footer already has rows, so a footer that has rows can be rewritten but never grown.
* Wrote 27 test cases against the Access behaviour rather than against what looks reasonable, including one that fails if a delete ever appears in the model or in the procedures. The suite caught a real bug where the save wrote every box on the screen instead of stopping at the last line the user had filled in.

**Thursday August 6th – Friday August 7th 2026: Story Card Maintenance — The Screen**

* Laid the screen out like the form: side 1 on the left, side 2 on the right, the footer under side 2, and the buttons on the top row for room. One line on screen for each line stored, with its number beside it.
* Sized every entry box to the width of the column behind it, with the widths taken from the model so the screen cannot drift from the file.
* SKU list opens on any click and shows the whole list positioned at the current value, with typing filtering it. The Access combo only filtered while you typed and never opened on its own.
* Removed the Revert button, which the Access form never had — it had Save and Close Form only.
* Replaced the sentences that explained the screen's state with a single coloured chip, so the page reads as a form rather than a wall of text.

**Monday August 10th – Tuesday August 11th 2026: Story Card Maintenance — The Footer**

* Footer keys can be chosen, and a new key can be created, stored and picked up later. The Access FootMaintenance combo could not do that: its query only ever returned key 1, and its save wrote scfsky=1 whatever the combo held.
* Capped the footer at the two lines the box holds, in the model, in the editor and in the save, so nothing can write a third line.
* The strip under side 2 draws the same numbered boxes the sides use, read only, and stays empty until a SKU is picked, since LoadText only read the footer once the item description came back.
* The editor works on its own copy of the footer, so browsing keys or typing in it never moves the page. The display changes only when a save lands or when its own key selector is used.

**Wednesday August 12th – Thursday August 13th 2026: Requisitions**

Entry form sized so every box displays exactly what its column accepts and every heading fits on one line at the table's minimum width; SKU To matched to Item #, description sized to the longest stored text, maxlengths capped so nothing typed can sit hidden past the edge. Autofill cleaned to fill real values instead of raw table output (14.5000 fills as 14.5; a zero leaves the box empty rather than reading as a price). Printed requisition gives Sku # and Sku To equal width.

Fixed the short-name failure through the proxy: request.php now redirects on whatever host name the browser arrived with, so the FQDN keeps working where lcc1 does not resolve.

Added return-after-sign-on — the requested address is stashed before an unsigned visit is refused, and LogOnProcess lands the person there instead of the home page, with the redirect restricted to local paths and never index.php, LogOnProcess or LogOut. Built and then scrapped a kiosk sign-on for the floor terminal. Controllers settled on StartBlockScriptB up top with the page script inside the authorized branch.

**Wednesday August 12th – Thursday August 13th 2026: Story Card Maintenance — File Layout**

* Compacted the display file from 874 lines to 777 without removing any code, and proved it identical by a comment-stripped, whitespace-normalised comparison against the original.
* Wrapped every comment to a 158 character cap so no line runs off the edge of the editor.
* Moved the javascript out of the display and into the controller, the same layout the Sellbrite Bulk Loader uses: the display keeps the markup, the styling and the values the page needs from PHP, and the controller carries the script. The display went from 786 lines to 241, and reassembling the two pieces reproduces the original script line for line.

**Monday August 17th – Friday August 21st 2026: Story Card Maintenance — Sandbox and Testing**

* Set up a test copy in LSCDEVLIBP so that nothing a tester saves can reach a real story card. Used CRTDUPOBJ for the object and CPYF for the records rather than DATA(*YES), which avoids the object lock and the wait, and doubles as the refresh when the sandbox needs putting back.
* Counted the rows before copying rather than after: 167,445 body lines and 2 footer lines, the two footer lines confirming from the real data that a footer is one key of two lines.
* Journalled both copies to LSCSAVLIB/LSCJRN. A duplicated physical file arrives unjournalled, and the procedures run under commitment control, so the first save would have failed without it.
* Wrote the SQL to prove testing stayed where it belonged: row counts on both libraries and an EXCEPT run in both directions, so the production side shows nothing changed while the dev side shows exactly what the testing wrote.
* Emailed Brian to ask who maintains the story cards day to day, how best to get the screen in front of them for feedback, and for a look over it himself.

**Monday August 24th – Tuesday August 25th 2026: Requisitions**

Rewrote the ITEM and ITEMSRCH cursors in REQSTN007S: entry-form autofill now reads description, coin date, average cost and price straight off LSCPRDLIB/ITMMSTP (IIAVGC, IIPRCE) instead of the last requisition for that SKU — the original assumed the master carried no pricing, which was wrong. Took IICDAT off an INT cast it could not survive for values like 1940-S. Backfilled 7,568 zero-cost and zero-retail lines on RQSREQDTLT from the master.

Implemented line editing on the maintenance screen: REQSTN010S, one new procedure, updates a single detail line with a null leaving that column unchanged. Cells on the requisition window take typing directly, a corrected item number pulls the rest of the line from the item master with the same type-ahead list the entry form offers, corrected cells colour until saved, and Update writes the header and every changed line one request at a time. New ajax action updateline at level 41 with the correction logged field by field. The grid itself stays as it was — corrections happen only after clicking into a requisition.

**Monday August 24th – Tuesday August 25th 2026: Story Card Maintenance — Promotion**

* Pointed the three procedures back at LSCPRDLIB and confirmed the sources came back byte identical to the versions from before the sandbox work, so the only thing the detour changed was where the data went.
* Checked journalling on the production files before promoting. The Access application wrote through ODBC and never needed it, but the procedures run under commitment control and do, so an unjournalled production file would have failed on the first save with the page already pointed at it.
* Promoted through MDCMS. The web files name no library at all, so going from test to production is the three procedure objects being replaced and nothing else; the same page starts reading and writing production the moment they are.

**Wednesday August 26th 2026: Story Card Maintenance — Go Live and House Standards**

* Verified on production: the three procedures report 2, 5 and 2 parameters, a card loads with its lines, the footer strip shows the real two lines, and a card saved unchanged proves the write path end to end without altering any data.
* Chased down why the authorization message appeared on Story Card but not Requisitions. Story Card asks for LCCONLINE grant 10 and the Requisitions station asks for 41, and the grants are separate rather than a ladder, so a profile holding 41 is still refused by Story Card. Kept the tool at 10 and the grant gets added to the profiles that maintain story cards instead.
* Made the refusal identical across all twelve controllers: the framework's showNotAuthorized() call, never an echoed script tag, and one message everywhere. Time Payment and Sellbrite were echoing a script into a div that only exists on the authorized page, so an unauthorized profile saw a blank content area instead of the refusal.
* Moved the page script inside the authorized branch on Story Card and Sellbrite, so a profile that is turned away no longer downloads a screen it will not be shown.
* Found while checking Sellbrite that three spliced-together edits had left its script unparseable, which means none of its javascript has been running: sblShow defined twice with the first copy never closed, two versions of the GreySheet notfound branch folded into one another, and a duplicated startup block that would have bound every handler twice. Repaired all three, keeping the half the surrounding comments describe. Its javascript parses now.

Story Card Maintenance is on production alongside Requisitions, with go-live follow-ups, user feedback and ticket work in progress.
