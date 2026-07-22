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
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<!-- includes css and javascript libraries (local copies, same as the other LCC tools) -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Requisition Station";

    /* ---- message helpers (LCC convention) ---- */
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

//***--- Check users authority (10 is the minimum to use LCCOnline) ---***
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/Requisitions_model.php';

    // preload the dropdown lists with the page (saves an ajax round trip);
    // the page falls back to the ajax lookups if anything fails here
    $rqLookups = null;
    if (isset($authConn) && $authConn) {
        $names = rqsLookup($authConn, "NAMES");
        $codes = rqsLookup($authConn, "AREACODE");
        $types = rqsLookup($authConn, "AREATYPE");
        $auth  = rqsLookup($authConn, "AUTHBY");
        if ($names !== false && $codes !== false && $types !== false && $auth !== false) {
            $rqLookups = array("ok" => true, "names" => $names, "areaCodes" => $codes,
                               "areaTypes" => $types, "authBy" => $auth);
        }
    }

    // mode=entry is the workfloor shortcut: entry form only, no grid.
    // The plain URL is the full station view for IT/supervisors.
    $rqMode = (($_GET['mode'] ?? '') === 'entry') ? 'entry' : '';

    include "Requisitions_dsp.php";
    dspRequisitions($user, $rqLookups, $rqMode);
?>
<!--  End Content Here -->
<?php
} // end authority check

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
