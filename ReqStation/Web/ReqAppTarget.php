<?php
/*    ***************************************************  -->
<!--  * Program Name - ReqAppTarget.php                 *  -->
<!--  *                                                 *  -->
<!--  * Narrative - THE one place to say where the new  *  -->
<!--  *   Requisition Station app lives. All five       *  -->
<!--  *   legacy shims (request.php, getEntry.php,      *  -->
<!--  *   getIdInfo.php, getInsert.php, getUpdate.php)  *  -->
<!--  *   include this file, so pointing the workfloor  *  -->
<!--  *   at a new location is a one-line edit here.    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************   */

// >>> EDIT THIS LINE ONLY <<<
// Dev today. At go-live, change to the production instance, e.g.
// 'http://lcc1.littletoncoin.com:10088/LCCOnline/Requisitions_ctl.php'
$REQAPP = 'http://lcc1.littletoncoin.com:8068/LCCOnline/Requisitions_ctl.php';
?>
