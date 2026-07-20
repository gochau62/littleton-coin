<?php
/*    ***************************************************  -->
<!--  * Program Name - ReqStn_dsp.php                   *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station display.        *  -->
<!--  *   Main grid (replaces Access frmMain), add-     *  -->
<!--  *   request modal (replaces legacy getEntry.php)  *  -->
<!--  *   and view/authorize modal (replaces            *  -->
<!--  *   getIdInfo.php / getUpdate.php).               *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************   */
?>

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
