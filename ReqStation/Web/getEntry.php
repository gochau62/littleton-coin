<?php
/*    ***************************************************  -->
<!--  * Program Name - getEntry.php                      *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Legacy URL shim. Production and     *  -->
<!--  *   inventory controllers have this address       *  -->
<!--  *   bookmarked (and the Access stations open it), *  -->
<!--  *   so the filename stays alive and maps the old  *  -->
<!--  *   behavior (entry form)                         *  -->
<!--  *   onto Requisitions_ctl.php.                    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

$id = 0;
if (isset($_GET['id']))       { $id = intval($_GET['id']); }
if (isset($_REQUEST['req']))  { $id = intval($_REQUEST['req']); }

if ($id > 0) {
    header('Location: Requisitions_ctl.php?id=' . $id, true, 302);
} else {
    header('Location: Requisitions_ctl.php?action=add', true, 302);
}
exit;
?>
