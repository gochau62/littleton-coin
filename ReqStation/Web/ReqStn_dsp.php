<?php
/*    ***************************************************  -->
<!--  * Program Name - ReqStn_dsp.php                   *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station display.        *  -->
<!--  *   Main grid (replaces Access frmMain), add-     *  -->
<!--  *   request modal (replaces legacy getEntry.php)  *  -->
<!--  *   and view/authorize modal (replaces            *  -->
<!--  *   getIdInfo.php / getUpdate.php).               *  -->
<!--  *   All styling lives in the <style> block below  *  -->
<!--  *   - the display file owns everything visual.    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************   */
?>

<style>
/* Requisition Station styling - inline per shop preference:
   the display file owns everything visual. */
:root {
  --rq-navy:   #1c3557;
  --rq-blue:   #2f6fb2;
  --rq-bg:     #f4f6f9;
  --rq-line:   #d9dee6;
  --rq-text:   #22293a;
  --rq-muted:  #6b7486;
  --rq-green:  #1e7e34;
  --rq-amber:  #b26a00;
  --rq-red:    #b02a37;
}

.rq-app {
  font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
  color: var(--rq-text);
  background: var(--rq-bg);
  padding: 0 0 2rem 0;
}

/* ---------- top bar ---------- */
.rq-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--rq-navy);
  color: #fff;
  padding: .6rem 1.25rem;
}
.rq-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.rq-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

/* ---------- toolbar ---------- */
.rq-toolbar {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .75rem 1.25rem;
  flex-wrap: wrap;
}
.rq-filter {
  flex: 1 1 220px;
  max-width: 340px;
  padding: .45rem .7rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
}
.rq-count { color: var(--rq-muted); font-size: .85rem; margin-left: auto; }
.rq-auto  { color: var(--rq-muted); font-size: .85rem; user-select: none; }

/* ---------- buttons ---------- */
.rq-btn {
  padding: .45rem .9rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
  background: #fff;
  color: var(--rq-text);
  font-size: .9rem;
  cursor: pointer;
}
.rq-btn:hover { border-color: var(--rq-blue); color: var(--rq-blue); }
.rq-btn-primary {
  background: var(--rq-blue);
  border-color: var(--rq-blue);
  color: #fff;
}
.rq-btn-primary:hover { background: var(--rq-navy); color: #fff; }
.rq-btn-ghost { border-style: dashed; color: var(--rq-muted); margin: .5rem 0; }

/* ---------- card + grid ---------- */
.rq-card {
  background: #fff;
  border: 1px solid var(--rq-line);
  border-radius: 8px;
  margin: 0 1.25rem;
  overflow: hidden;
}
.rq-tablewrap { overflow-x: auto; max-height: 70vh; }
.rq-grid { width: 100%; border-collapse: collapse; font-size: .88rem; }
.rq-grid thead th {
  position: sticky;
  top: 0;
  background: #eef1f6;
  color: var(--rq-navy);
  text-align: left;
  padding: .55rem .7rem;
  border-bottom: 2px solid var(--rq-line);
  white-space: nowrap;
}
.rq-grid tbody td {
  padding: .45rem .7rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-grid tbody tr:nth-child(even) { background: #fafbfd; }
#tblGrid tbody tr { cursor: pointer; }
#tblGrid tbody tr:hover { background: #e9f2fb; }
.rq-num { text-align: right; }
.rq-empty { text-align: center; color: var(--rq-muted); padding: 1.5rem !important; }

/* ---------- status pills ---------- */
.rq-pill {
  display: inline-block;
  padding: .1rem .55rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
  white-space: nowrap;
}
.rq-ok       { background: #e2f2e6; color: var(--rq-green); }
.rq-warn     { background: #fdf0dd; color: var(--rq-amber); }
.rq-rushpill { background: #fbe3e6; color: var(--rq-red); }

/* ---------- modals ---------- */
.rq-overlay {
  position: fixed;
  inset: 0;
  background: rgba(20, 28, 45, .45);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 4vh 1rem;
  z-index: 50;
}
.rq-overlay[hidden] { display: none; }
.rq-modal {
  background: #fff;
  border-radius: 10px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 40px rgba(0, 0, 0, .25);
}
.rq-modal-wide { max-width: 1080px; }
.rq-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .8rem 1.1rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-modal-head h2 { margin: 0; font-size: 1.05rem; color: var(--rq-navy); }
.rq-modal-body { padding: 1rem 1.1rem; overflow-y: auto; }
.rq-modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  padding: .8rem 1.1rem;
  border-top: 1px solid var(--rq-line);
}
.rq-x {
  border: 0;
  background: none;
  font-size: 1.3rem;
  line-height: 1;
  color: var(--rq-muted);
  cursor: pointer;
}
.rq-x:hover { color: var(--rq-red); }
.rq-linedel { font-size: 1rem; }

/* ---------- add / view forms ---------- */
.rq-formrow {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: .9rem;
}
.rq-formrow label,
.rq-comments {
  display: flex;
  flex-direction: column;
  gap: .25rem;
  font-size: .82rem;
  color: var(--rq-muted);
}
.rq-formrow select,
.rq-comments input,
.rq-authrow select,
.rq-authrow input {
  padding: .4rem .6rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
  font-size: .9rem;
  color: var(--rq-text);
}
.rq-rush { flex-direction: row !important; align-items: center; gap: .4rem; }
.rq-lines input {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: .25rem .35rem;
  font-size: .85rem;
}
.rq-lines input:focus {
  border-color: var(--rq-blue);
  outline: none;
  background: #f4f9ff;
}
.rq-comments { margin-top: .9rem; }
.rq-comments input { width: 100%; }

.rq-viewhead {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: .4rem .9rem;
  font-size: .88rem;
  margin-bottom: .9rem;
}
.rq-authrow {
  display: flex;
  align-items: flex-end;
  gap: .9rem;
  flex-wrap: wrap;
  margin-top: 1rem;
  padding-top: .9rem;
  border-top: 1px dashed var(--rq-line);
}
.rq-authrow label { display: flex; flex-direction: column; gap: .25rem;
                    font-size: .82rem; color: var(--rq-muted); }
.rq-authrow .rq-comments { flex: 1; margin-top: 0; }
</style>

<div class="rq-app">

  <header class="rq-topbar">
    <h1>Requisition Station</h1>
    <div class="rq-topbar-right">
      <span id="rqUser"><?php echo htmlspecialchars($user); ?></span>
      <span id="rqClock"></span>
    </div>
  </header>

  <div class="rq-toolbar">
    <button type="button" class="rq-btn rq-btn-primary" id="btnAdd">+ Add Requisition</button>
    <button type="button" class="rq-btn" id="btnRefresh">&#8635; Refresh</button>
    <label class="rq-auto">
      <input type="checkbox" id="chkAutoRefresh" checked> Auto-refresh
    </label>
    <input type="search" id="txtFilter" class="rq-filter"
           placeholder="Filter by req #, name, item...">
    <span class="rq-count" id="lblCount"></span>
  </div>

  <div class="rq-card">
    <div class="rq-tablewrap">
      <table class="rq-grid" id="tblGrid">
        <thead>
          <tr>
            <th>Req #</th>
            <th>Date</th>
            <th>Requestor</th>
            <th>Item #</th>
            <th>Description</th>
            <th>Loc</th>
            <th class="rq-num">Qty</th>
            <th>Rush</th>
            <th>Authorized</th>
            <th>Returned</th>
          </tr>
        </thead>
        <tbody id="gridBody">
          <tr><td colspan="10" class="rq-empty">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============ Add Requisition modal (replaces getEntry.php) ============ -->
  <div class="rq-overlay" id="mdlAdd" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>New Requisition</h2>
        <button type="button" class="rq-x" data-close="mdlAdd">&times;</button>
      </div>

      <div class="rq-modal-body">
        <div class="rq-formrow">
          <label>Requestor
            <select id="addName"></select>
          </label>
          <label>Area Code
            <select id="addAreaCode"></select>
          </label>
          <label>Area Type
            <select id="addAreaType"></select>
          </label>
          <label class="rq-rush">Rush
            <input type="checkbox" id="addRush">
          </label>
        </div>

        <div class="rq-tablewrap">
          <table class="rq-grid rq-lines" id="tblLines">
            <thead>
              <tr>
                <th>Item #</th><th>Location</th><th>Item Date</th>
                <th>Description</th><th class="rq-num">Qty</th>
                <th class="rq-num">Cost $</th><th class="rq-num">Retail $</th>
                <th class="rq-num">Add Cost $</th><th>SKU To</th><th></th>
              </tr>
            </thead>
            <tbody id="lineBody"></tbody>
          </table>
        </div>
        <button type="button" class="rq-btn rq-btn-ghost" id="btnAddLine">+ Add line</button>

        <label class="rq-comments">Comments
          <input type="text" id="addComments" maxlength="254">
        </label>
      </div>

      <div class="rq-modal-foot">
        <button type="button" class="rq-btn" data-close="mdlAdd">Cancel</button>
        <button type="button" class="rq-btn rq-btn-primary" id="btnSubmit">Submit Requisition</button>
      </div>
    </div>
  </div>

  <!-- ==== View / Authorize modal (replaces getIdInfo.php + getUpdate.php) ==== -->
  <div class="rq-overlay" id="mdlView" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>Requisition <span id="viewReqNum"></span></h2>
        <button type="button" class="rq-x" data-close="mdlView">&times;</button>
      </div>

      <div class="rq-modal-body">
        <div class="rq-viewhead" id="viewHead"></div>

        <div class="rq-tablewrap">
          <table class="rq-grid" id="tblViewLines">
            <thead>
              <tr>
                <th>Line</th><th>Item #</th><th>Location</th><th>Item Date</th>
                <th>Description</th><th class="rq-num">Qty</th>
                <th class="rq-num">Cost $</th><th class="rq-num">Retail $</th>
                <th>SKU To</th><th>Returned</th>
              </tr>
            </thead>
            <tbody id="viewLineBody"></tbody>
          </table>
        </div>

        <div class="rq-authrow" id="authRow">
          <label>Authorized by
            <select id="authBy"></select>
          </label>
          <label class="rq-comments">Comments
            <input type="text" id="authComments" maxlength="254">
          </label>
          <button type="button" class="rq-btn rq-btn-primary" id="btnAuthorize">Authorize</button>
        </div>
      </div>
    </div>
  </div>

</div>
