<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ctl.php             *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station controller.     *  -->
<!--  *   Web replacement for the Access frmMain grid   *  -->
<!--  *   and the legacy request.php entry page.        *  -->
<!--  *   Bootstraps exactly like SellbriteBulkLoader:  *  -->
<!--  *   StartBlockScriptA/B, guarded authority check, *  -->
<!--  *   then the display function.                    *  -->
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
?>

<?php
    // shared LCC plumbing (StartBlockScriptA/B, jQuery/, swal/, Utils/) lives at
    // the instance docroot; this app may sit there or in /Requisitions/ - the
    // prefix works for both the PHP includes and the browser asset URLs.
    $lccRoot = file_exists('StartBlockScriptA.php') ? '' : '../';

    // retrieves and sets password and username
    if (file_exists($lccRoot . 'StartBlockScriptA.php')) { require_once $lccRoot . 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<!-- includes css and javascript libraries (local copies, same as the other LCC tools) -->
<script type='text/javascript' src='<?php echo $lccRoot; ?>jQuery/jquery.js'></script>
<script type='text/javascript' src='<?php echo $lccRoot; ?>swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='<?php echo $lccRoot; ?>swal/sweetalert.min.js'></script>
<link href="<?php echo $lccRoot; ?>swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Requisition Station";

    /* ---- message helpers (LCC convention) ---- */
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#b02a37; font-weight:bold;"></div>

<?php
if (file_exists($lccRoot . 'StartBlockScriptB.php')) { require_once $lccRoot . 'StartBlockScriptB.php'; }

//***--- Check users authority (10 is the minimum to use LCCOnline) ---***
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    include "Requisitions_dsp.php";
    dspRequisitions($user);
?>
<!--  End Content Here -->
<?php
} // end authority check

if (file_exists($lccRoot . 'EndBlock.php')) { include $lccRoot . 'EndBlock.php'; }
?>
