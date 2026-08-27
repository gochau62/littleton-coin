<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectDevelopers_dsp.php        *  -->
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
function dspProjectDevelopers() {
    prjStyles();
?>
<!-- stdPage is the shared layout hook that seats a page beside the nav menu,
     same as every legacy PROJ_* screen -->
<div id="stdPage">
<div class="pt-app">

    <?php prjHeader('Projects by Developer',
                    '<span id="ptUpdated"></span> &nbsp;&middot;&nbsp; ' .
                    '<a href="#" id="lnkRefresh">refresh</a>',
                    'assignments'); ?>

    <div class="pt-card">
        <div class="pt-toolbar">
            <input type="text" id="txtSearch" placeholder="Search project # or description">
            <select id="selPgmr"><option value="">All developers</option></select>
            <button type="button" class="pt-btn pt-btn-primary" id="btnDownload">
                Download to Excel</button>
        </div>
    </div>

    <div id="groupList"></div>

</div>
</div>
<?php
}
?>
