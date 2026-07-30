<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCardTest_ctl.php             *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/30/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

// the sandbox screen: same page, but every call goes to the LSCDEVLIBP
// procedures, so nothing saved here can reach the real story cards
define('STC_LIB', 'LSCDEVLIBP/');
require __DIR__ . '/StoryCard_ctl.php';
