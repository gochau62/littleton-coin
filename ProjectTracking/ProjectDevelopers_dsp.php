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

// shared styles and header from ProjectTracking_dsp.php
function dspProjectDevelopers() {
    prjStyles();
?>
<!-- stdPage seats the page beside the nav menu -->
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
            <select id="selStatus"><option value="">All statuses</option></select>
        </div>
    </div>

    <div id="groupList"></div>

</div>
</div>
<?php
}
?>
