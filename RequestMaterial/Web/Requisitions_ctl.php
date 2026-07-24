<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ctl.php             *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
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

    document.title = "Requisition Material";

    // ---- message helpers (LCC convention) ----
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// ***--- Check users authority (10 is the minimum to use LCCOnline) ---***
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/Requisitions_model.php';

    // preload the dropdown lists with the page; the ajax lookups action is the fallback
    $rqLookups = null;
    if (isset($authConn) && $authConn) {
        $names  = rqsLookup($authConn, "NAMES");
        $codes  = rqsLookup($authConn, "AREACODE");
        $types  = rqsLookup($authConn, "AREATYPE");
        $auth   = rqsLookup($authConn, "AUTHBY");
        $badges = rqsLookup($authConn, "BADGE");
        if ($names !== false && $codes !== false && $types !== false &&
            $auth !== false && $badges !== false) {
            $rqLookups = array("ok" => true, "names" => $names, "areaCodes" => $codes,
                               "areaTypes" => $types, "authBy" => $auth,
                               "badges" => $badges);
        }
    }

    // mode=entry = the workfloor entry-only shortcut; the plain URL = the full station
    $rqMode = (($_GET['mode'] ?? '') === 'entry') ? 'entry' : '';

    rqsActLog($user, 'OPEN', $rqMode === 'entry' ? 'entry form' : 'station');

    include "Requisitions_dsp.php";
    dspRequisitions($user, $rqLookups, $rqMode);
?>
<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
