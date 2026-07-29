<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCard_ctl.php                 *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/27/2026                         *  -->
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

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Story Card Maintenance";

    // small message helpers following the LCC convention: show the red error box with a message, or the standard not authorized message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }

    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// check users authority (10 is the minimum to use LCCOnline)
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 10);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/StoryCard_model.php';

    // preload the card the URL asked for and the shared footer, so the page
    // arrives ready instead of opening empty and then fetching twice. The ajax
    // card and footer actions are the fallback
    $stcPreload = null;
    if (isset($authConn) && $authConn) {
        $sku      = stcCleanSku($_GET['sku'] ?? '');
        $footer   = stcGetFooter($authConn);
        $footKeys = stcFooterKeys($authConn);

        $card = null;
        if ($sku !== '') {
            $item = stcGetSku($authConn, $sku);
            if ($item !== false && $item !== null) {
                $rows = stcGetCard($authConn, $sku);
                if ($rows !== false) {
                    $card = stcCardToSides($rows);
                    $card['sku']  = rtrim($item['SCSKU']);
                    $card['desc'] = rtrim($item['SCDESC']);
                    $card['isNew'] = (count($rows) === 0);
                }
            }
        }

        if ($footer !== false) {
            $foot = array();
            foreach ($footer as $f) { $foot[] = rtrim($f['SCFTXT']); }
            // the key list failing must not cost the page its preload
            $keyList = array();
            if ($footKeys === false) {
                error_log('StoryCard footer keys unavailable (' . $GLOBALS['stcErr'] .
                          ') - is STYCRD001S built with the KEYS type?');
                $keyList[] = STC_FOOT_KEY;
            } else {
                foreach ($footKeys as $k) { $keyList[] = intval($k['SCFSKY']); }
            }
            $stcPreload = array("ok" => true, "sky" => STC_FOOT_KEY,
                                "footer" => $foot, "keys" => $keyList,
                                "card" => $card);
        }
    }

    // mode footer opens straight on the footer editor, the old FootMaintenance
    // form; the plain URL is the body screen the work actually happens on
    $stcMode = (($_GET['mode'] ?? '') === 'footer') ? 'footer' : '';

    stcActLog($user, 'OPEN', $stcMode === 'footer' ? 'footer editor' : 'card editor');

    include "StoryCard_dsp.php";
    dspStoryCard($user, $stcPreload, $stcMode);
?>
<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
