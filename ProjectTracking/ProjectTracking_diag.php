<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_diag.php         *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/10/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -  Temporary, delete after testing    *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */
?>

<?php
    // retrieves and sets password and username
    require_once 'StartBlockScriptA.php';
    $user     = $_SESSION['username'];
    $password = $_SESSION['password'];
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type="text/javascript">

    document.title = "Project Tracking diagnostics";

    // show the red error box with a message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<style>
#stdPage { font-family: "Segoe UI", Arial, sans-serif; font-size: 13px; padding: .75rem; }
#stdPage h1 { font-size: 1.15rem; margin: 0 0 .8rem; }
#stdPage h2 { font-size: .95rem; margin: 1.1rem 0 .3rem; }
#stdPage table { border-collapse: collapse; margin: .2rem 0 .6rem; }
#stdPage th { text-align: left; font-size: .72rem; text-transform: uppercase;
    letter-spacing: .04em; color: #667085; border-bottom: 1px solid #d0d5dd;
    padding: .25rem .55rem; }
#stdPage td { padding: .22rem .55rem; border-bottom: 1px solid #eef0f4; }
#stdPage .bad { color: #d03b3b; font-weight: 600; }
#stdPage .good { color: #008300; font-weight: 600; }
#stdPage .note { color: #667085; font-size: .82rem; margin: .1rem 0 .5rem; }
</style>

<!--  Begin Content Here -->
<?php
require_once 'StartBlockScriptB.php';

// record where the person was headed so sign-on can send them back
if ($user === '') { $_SESSION['return_after_logon'] = $_SERVER['REQUEST_URI'] ?? ''; }

// authority level 20, the developers group
$authConn   = getDB2PConn($user, $password);
$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

$conn = $authConn;

// run a select and print it, never stopping the page on an error
function diagShow($conn, $title, $sql, $note = '') {
    echo "<h2>" . htmlspecialchars($title) . "</h2>";
    if ($note !== '') { echo "<div class='note'>" . htmlspecialchars($note) . "</div>"; }
    $stmt = @db2_prepare($conn, $sql);
    if (!$stmt || !@db2_execute($stmt)) {
        echo "<div class='bad'>could not run: "
           . htmlspecialchars(db2_stmt_errormsg()) . "</div>";
        echo "<div class='note'>" . htmlspecialchars($sql) . "</div>";
        return array();
    }
    $rows = array();
    while ($r = db2_fetch_assoc($stmt)) { $rows[] = $r; }
    if (!$rows) { echo "<div class='bad'>no rows</div>"; return $rows; }
    echo "<table><tr>";
    foreach (array_keys($rows[0]) as $c) { echo "<th>" . htmlspecialchars($c) . "</th>"; }
    echo "</tr>";
    foreach ($rows as $r) {
        echo "<tr>";
        foreach ($r as $v) { echo "<td>" . htmlspecialchars(trim(strval($v))) . "</td>"; }
        echo "</tr>";
    }
    echo "</table>";
    return $rows;
}

// one number back from a select, or null when it will not run
function diagValue($conn, $sql) {
    $stmt = @db2_prepare($conn, $sql);
    if (!$stmt || !@db2_execute($stmt)) { return null; }
    $r = db2_fetch_array($stmt);
    return $r ? $r[0] : null;
}

echo "<div id='stdPage'>";
echo "<h1>Project Tracking diagnostics</h1>";
echo "<div class='note'>Signed in as " . htmlspecialchars($user)
   . ". This page only reads. Delete it when the testing is done.</div>";

// print as we go, so a query that stops the page still leaves the rest readable
@ini_set('implicit_flush', '1');
@ob_implicit_flush(true);
function diagFlush() { @ob_flush(); @flush(); }

// the library list this web job is actually running with
$libs = diagShow($conn,
    "Library list for this job",
    "SELECT ORDINAL_POSITION, SCHEMA_NAME, TYPE FROM QSYS2.LIBRARY_LIST_INFO "
  . "ORDER BY ORDINAL_POSITION",
    "The first library holding an object wins, so order matters here.");
diagFlush();

// counts per library, which is what says where the real data is
$check = array('LSCPRDLIB', 'LCCTSTLIB', 'LSCDEVLIB', 'LSCDEVLIBP', 'LSCPGMLIB');
foreach ($libs as $l) {
    $s = trim($l['SCHEMA_NAME']);
    if ($s !== '' && !in_array($s, $check)) { $check[] = $s; }
}

echo "<h2>Rows in PRPROJP, by library</h2>";
echo "<div class='note'>A library missing from this table has no PRPROJP, or no "
   . "authority to it. The 90000 to 90100 count is the one that matters: those "
   . "buckets show on the time entry screen for everyone.</div>";
echo "<table><tr><th>Library</th><th>Projects</th><th>90000-90100</th>"
   . "<th>Highest number</th><th>Assigned to " . htmlspecialchars($user) . "</th></tr>";
diagFlush();
foreach ($check as $lib) {
    if (!preg_match('/^[A-Z0-9_#$@]{1,10}$/i', $lib)) { continue; }
    $total = diagValue($conn, "SELECT COUNT(*) FROM " . $lib . ".PRPROJP");
    if ($total === null) { continue; }
    $buckets = diagValue($conn,
        "SELECT COUNT(*) FROM " . $lib . ".PRPROJP WHERE \"PR#\" BETWEEN 90000 AND 90100");
    $high = diagValue($conn, "SELECT MAX(\"PR#\") FROM " . $lib . ".PRPROJP");
    $mine = diagValue($conn,
        "SELECT COUNT(*) FROM " . $lib . ".PRPROJP WHERE TRIM(PRPGMR) = '"
      . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $user)) . "'");
    $cls = ($buckets > 0) ? 'good' : 'bad';
    echo "<tr><td>" . htmlspecialchars($lib) . "</td>"
       . "<td>" . htmlspecialchars(strval($total)) . "</td>"
       . "<td class='" . $cls . "'>" . htmlspecialchars(strval($buckets)) . "</td>"
       . "<td>" . htmlspecialchars(strval($high)) . "</td>"
       . "<td>" . htmlspecialchars(strval($mine)) . "</td></tr>";
    diagFlush();
}
echo "</table>";
diagFlush();

// every lookup table behind a dropdown, and where each one is being found
$lookups = array(
    'PRIDTRANSP' => 'Assigned estimator, programmer',
    'PRGROUPP'   => 'Dev group',
    'PRPAYBCKP'  => 'Payback type',
    'PRTYPEP'    => 'Project type',
    'PRPLNDEFP'  => 'Planned',
    'PRRESCODEP' => 'Resolution',
    'PRSTATUSP'  => 'Programmer work status',
    'PRSPNSRP'   => 'Sponsor',
    'PRAUTHP'    => 'Authority',
    'PRTOOLTIPP' => 'Tooltips',
    'LCDEPTP'    => 'Department, sub dept',
);
echo "<h2>Lookup tables behind the dropdowns</h2>";
echo "<div class='note'>Row counts per library, in library list order. The first "
   . "library on the list that holds a table is the one the dropdown reads, and "
   . "that cell is marked. Compare it against LSCPRDLIB.</div>";
$order = array();
foreach ($libs as $l) { $o = trim($l['SCHEMA_NAME']); if ($o !== '') { $order[] = $o; } }
$cols = $order;
if (!in_array('LSCPRDLIB', $cols)) { $cols[] = 'LSCPRDLIB'; }

echo "<table><tr><th>Table</th><th>Feeds</th>";
foreach ($cols as $c) { echo "<th>" . htmlspecialchars($c) . "</th>"; }
echo "</tr>";
diagFlush();
foreach ($lookups as $tbl => $what) {
    echo "<tr><td>" . htmlspecialchars($tbl) . "</td><td>" . htmlspecialchars($what) . "</td>";
    $winner = '';
    foreach ($cols as $c) {
        if (!preg_match('/^[A-Z0-9_#$@]{1,10}$/i', $c)) { echo "<td></td>"; continue; }
        $n = diagValue($conn, "SELECT COUNT(*) FROM " . $c . "." . $tbl);
        if ($n === null) { echo "<td>-</td>"; continue; }
        // the first library on the list holding it is the one that wins
        $isWinner = ($winner === '' && in_array($c, $order));
        if ($isWinner) { $winner = $c; }
        echo "<td class='" . ($isWinner ? 'good' : '') . "'>"
           . htmlspecialchars(strval($n)) . ($isWinner ? " &lt;-- used" : "") . "</td>";
    }
    echo "</tr>";
    diagFlush();
}
echo "</table>";
diagFlush();

// what the screens themselves get back through the library list
echo "<h2>What PTS0002S returns to this job</h2>";
echo "<div class='note'>Unqualified, exactly as the time entry screen calls it.</div>";
if (file_exists("PROJ_model.php")) { require_once("PROJ_model.php"); }
$all = function_exists('getProjListAll') ? getProjListAll($conn, 'yes') : array();
$all = is_array($all) ? $all : array();
if (!function_exists('getProjListAll')) {
    echo "<div class='bad'>PROJ_model.php is not on this server, so this "
       . "part could not run. The library counts above still stand.</div>";
}
$buckets = 0;
$mine    = 0;
foreach ($all as $p) {
    if ($p['PR#'] >= 90000 && $p['PR#'] <= 90100) { $buckets++; }
    if (trim($p['PRPGMR']) == trim($user)) { $mine++; }
}
echo "<table><tr><th>Rows returned</th><th>90000-90100</th><th>Assigned to you</th></tr>";
echo "<tr><td>" . count($all) . "</td>"
   . "<td class='" . ($buckets ? 'good' : 'bad') . "'>" . $buckets . "</td>"
   . "<td>" . $mine . "</td></tr></table>";

if (count($all) > 0) {
    echo "<div class='note'>First few rows back:</div><table>"
       . "<tr><th>PR#</th><th>Description</th><th>Programmer</th>"
       . "<th>Sched impl</th><th>Actual impl</th></tr>";
    foreach (array_slice($all, 0, 8) as $p) {
        echo "<tr><td>" . htmlspecialchars(trim(strval($p['PR#']))) . "</td>"
           . "<td>" . htmlspecialchars(trim(strval($p['PRDESC']))) . "</td>"
           . "<td>" . htmlspecialchars(trim(strval($p['PRPGMR']))) . "</td>"
           . "<td>" . htmlspecialchars(trim(strval($p['PRECOM']))) . "</td>"
           . "<td>" . htmlspecialchars(trim(strval($p['PRACOM']))) . "</td></tr>";
    }
    echo "</table>";
}

echo "</div>";

} // end authority check
// <!--  End Content Here -->

include("EndBlock.php");
?>
