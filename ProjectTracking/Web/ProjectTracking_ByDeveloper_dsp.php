<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_ByDeveloper_dsp.php *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 08/11/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// the shared styles and header come from ProjectTracking_dsp.php
function dspProjectTrackingByDeveloper() {
    prjStyles();
?>
<div class="pt-app">

    <?php prjHeader('Projects by developer',
                    'Who is carrying what, grouped the way the monthly ' .
                    'spreadsheet lays it out &middot; <span id="ptUpdated"></span>',
                    'assignments'); ?>

    <div class="pt-card">
        <div class="pt-toolbar">
            <input type="text" id="txtSearch" placeholder="Search project # or description">
            <select id="selPgmr"><option value="">All developers</option></select>
            <label><input type="checkbox" id="chkComplete">
                Include completed / rejected</label>
            <button type="button" class="pt-btn pt-btn-primary" id="btnDownload">
                Download to Excel</button>
            <span class="pt-count" id="lblCount"></span>
        </div>
    </div>

    <div id="groupList"></div>

</div>
<?php
}
?>
