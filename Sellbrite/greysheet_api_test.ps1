<#
  greysheet_api_test.ps1
  -----------------------------------------------------------------------------
  Standalone GreySheet CDN Public API v2 tester for Windows PowerShell.
  No PHP, no install - PowerShell is built into Windows. Same modes as the
  PHP tester: ping / probe / node / search.

  GIVE IT YOUR KEY (never commit real keys):
    $env:GS_API_TOKEN = 'xxxx'
    $env:GS_API_KEY   = 'yyyy'
    # optional: $env:GS_BASE_URL = 'https://cpgpublicapiv2.greysheet.com/api'
    # optional: $env:GS_API_LEVEL = 'advanced'
  ...or pass -Token / -Key on the command line.

  RUN (from the Sellbrite folder):
    powershell -ExecutionPolicy Bypass -File .\greysheet_api_test.ps1 ping
    .\greysheet_api_test.ps1 probe -Term "Morgan Dollar"
    .\greysheet_api_test.ps1 probe -Node 17453 -Term "Morgan Dollar"
    .\greysheet_api_test.ps1 node -Node 17453
    .\greysheet_api_test.ps1 search -Path SearchRequest -Param query -Term "1909-S VDB"

  If you get "running scripts is disabled", either use the
  "-ExecutionPolicy Bypass -File" form above, or run once:
    Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [ValidateSet('ping', 'probe', 'node', 'search')]
    [string]$Mode = 'ping',

    [string]$Node  = '1',
    [string]$Term  = 'Morgan Dollar',
    [string]$Path  = '',
    [string]$Param = '',
    [string]$Base  = $(if ($env:GS_BASE_URL)  { $env:GS_BASE_URL }  else { 'https://cpgpublicapiv2dev.greysheet.com/api' }),
    [string]$Level = $(if ($env:GS_API_LEVEL) { $env:GS_API_LEVEL } else { 'basic' }),
    [string]$Token = $env:GS_API_TOKEN,
    [string]$Key   = $env:GS_API_KEY,
    [int]$Timeout  = 20
)

# Force TLS 1.2 (Windows PowerShell 5.1 may default to older protocols).
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

if ($null -eq $Token) { $Token = '' }
if ($null -eq $Key)   { $Key   = '' }

# First entry in each list is the agent's current guess.
$NodeCandidates = @(
    @{ Path = 'GetNodeRequest'; Param = 'NodeId' },
    @{ Path = 'GetNodeRequest'; Param = 'nodeId' },
    @{ Path = 'GetNode';        Param = 'NodeId' },
    @{ Path = 'GetNode';        Param = 'nodeId' },
    @{ Path = 'Node';           Param = 'id' },
    @{ Path = 'nodes';          Param = $null }      # REST style: nodes/{id}
)
$SearchCandidates = @(
    @{ Path = 'SearchRequest';    Param = 'query' },   # current guess
    @{ Path = 'SearchRequest';    Param = 'term' },
    @{ Path = 'SearchRequest';    Param = 'q' },
    @{ Path = 'SearchRequest';    Param = 'keyword' },
    @{ Path = 'Search';           Param = 'query' },
    @{ Path = 'search';           Param = 'q' },
    @{ Path = 'search';           Param = 'query' },
    @{ Path = 'GetSearchRequest'; Param = 'query' }
)

# ---------------------------------------------------------------------------
function Invoke-GS {
    param([string]$Base, [string]$Path, [hashtable]$Query, [string]$Token, [string]$Key, [string]$Level, [int]$Timeout)

    if ($null -eq $Query) { $Query = @{} }
    if ($Level -and -not $Query.ContainsKey('apiLevel')) { $Query['apiLevel'] = $Level }

    $url = $Base.TrimEnd('/') + '/' + $Path.TrimStart('/')
    if ($Query.Count -gt 0) {
        $pairs = foreach ($k in $Query.Keys) {
            [uri]::EscapeDataString([string]$k) + '=' + [uri]::EscapeDataString([string]$Query[$k])
        }
        $url += '?' + ($pairs -join '&')
    }

    $headers = @{ 'x-api-token' = $Token; 'x-api-key' = $Key; 'Accept' = 'application/json' }
    $out = [ordered]@{ Status = 0; Ms = 0; Body = ''; Headers = @{}; Err = ''; Url = $url }
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method Get -TimeoutSec $Timeout -UseBasicParsing -ErrorAction Stop
        $out.Status = [int]$r.StatusCode
        $out.Body = [string]$r.Content
        foreach ($hk in $r.Headers.Keys) { $out.Headers[$hk] = ($r.Headers[$hk] -join ', ') }
    }
    catch {
        $resp = $null
        try { $resp = $_.Exception.Response } catch {}
        if ($null -ne $resp) {
            try { $out.Status = [int]$resp.StatusCode } catch {}
            # Body: PS7 puts it in ErrorDetails; 5.1 needs the response stream.
            if ($_.ErrorDetails -and $_.ErrorDetails.Message) {
                $out.Body = [string]$_.ErrorDetails.Message
            }
            else {
                try {
                    $stream = $resp.GetResponseStream()
                    $reader = New-Object System.IO.StreamReader($stream)
                    $out.Body = $reader.ReadToEnd()
                    $reader.Close()
                }
                catch {}
            }
            try { foreach ($hk in $resp.Headers.AllKeys) { $out.Headers[$hk] = $resp.Headers[$hk] } } catch {}
        }
        else {
            $out.Err = $_.Exception.Message
        }
    }
    $sw.Stop()
    $out.Ms = [int]$sw.ElapsedMilliseconds
    return [pscustomobject]$out
}

function Get-Hint([int]$s) {
    switch ($s) {
        0   { 'no response (network / DNS / TLS - see err)'; break }
        200 { 'OK'; break }
        400 { 'bad request (route ok, but wrong/missing param?)'; break }
        401 { 'unauthorized (token/key wrong or missing)'; break }
        403 { 'forbidden (key inactive, wrong tier, or gateway block)'; break }
        404 { 'not found (route or id does not exist)'; break }
        429 { 'rate limited (back off; see RateLimit-* headers)'; break }
        default {
            if ($s -ge 200 -and $s -lt 300) { 'success' }
            elseif ($s -ge 500) { 'server error' }
            else { 'unexpected' }
        }
    }
}

function Format-Body([string]$body, [int]$clip = 0) {
    if (-not $body) { return '' }
    try   { $out = ($body | ConvertFrom-Json | ConvertTo-Json -Depth 20) }
    catch { $out = $body }
    if ($clip -gt 0 -and $out.Length -gt $clip) { $out = $out.Substring(0, $clip) + "`n... [clipped]" }
    return $out
}

function Get-Mask([string]$s) {
    if (-not $s) { return '(empty)' }
    $keep = $s.Substring(0, [Math]::Min(4, $s.Length))
    return $keep + ('*' * [Math]::Max(3, $s.Length - 4)) + " (len $($s.Length))"
}

# ---------------------------------------------------------------------------
Write-Host ("Base URL    : " + $Base)
Write-Host ("apiLevel    : " + $Level)
Write-Host ("x-api-token : " + (Get-Mask $Token))
Write-Host ("x-api-key   : " + (Get-Mask $Key))
Write-Host ('-' * 68)

$keysMissing = ($Token -eq '' -or $Key -eq '')
if ($keysMissing -and $Mode -ne 'ping') {
    Write-Host "NO KEY SET. Set `$env:GS_API_TOKEN and `$env:GS_API_KEY (or pass -Token / -Key)."
    Write-Host "Every request will 403 without them. See the header comment for details."
    return
}

switch ($Mode) {

    'ping' {
        $m = Invoke-GS -Base $Base -Path '' -Query @{} -Token $Token -Key $Key -Level $Level -Timeout $Timeout
        Write-Host ("PING  -> HTTP {0}  ({1})  {2} ms" -f $m.Status, (Get-Hint $m.Status), $m.Ms)
        if ($m.Err) { Write-Host ("ERROR: " + $m.Err) }
        Write-Host ""
        Write-Host "Response headers:"
        foreach ($hk in $m.Headers.Keys) { Write-Host ("  {0}: {1}" -f $hk, $m.Headers[$hk]) }
        Write-Host ""
        Write-Host "Body:"
        Write-Host (Format-Body $m.Body 1500)
    }

    'probe' {
        Write-Host ("PROBE  node id=""{0}""  term=""{1}""" -f $Node, $Term)
        Write-Host "A route that returns 200/400/404 EXISTS (401/403 = auth; 404 on a"
        Write-Host "search term usually means the route is wrong, not the term)."
        Write-Host ""
        Write-Host ("  {0,-6} {1,-30} {2,-6} {3,-6} {4}" -f 'KIND', 'TRY', 'HTTP', 'ms', 'HINT')

        foreach ($c in $NodeCandidates) {
            if ($null -eq $c.Param) {
                $m = Invoke-GS -Base $Base -Path ($c.Path + '/' + $Node) -Query @{} -Token $Token -Key $Key -Level $Level -Timeout $Timeout
                $label = $c.Path + '/{id}'
            }
            else {
                $m = Invoke-GS -Base $Base -Path $c.Path -Query @{ $c.Param = $Node } -Token $Token -Key $Key -Level $Level -Timeout $Timeout
                $label = $c.Path + '?' + $c.Param + '='
            }
            $code = if ($m.Status) { $m.Status } else { '-' }
            Write-Host ("  {0,-6} {1,-30} {2,-6} {3,-6} {4}" -f 'node', $label, $code, $m.Ms, (Get-Hint $m.Status))
        }
        foreach ($c in $SearchCandidates) {
            $m = Invoke-GS -Base $Base -Path $c.Path -Query @{ $c.Param = $Term } -Token $Token -Key $Key -Level $Level -Timeout $Timeout
            $code = if ($m.Status) { $m.Status } else { '-' }
            $label = $c.Path + '?' + $c.Param + '='
            Write-Host ("  {0,-6} {1,-30} {2,-6} {3,-6} {4}" -f 'search', $label, $code, $m.Ms, (Get-Hint $m.Status))
        }
        Write-Host ""
        Write-Host "Tip: re-run with a REAL node id you know exists to tell a working"
        Write-Host "route (200) from a merely-valid one (404):"
        Write-Host "  .\greysheet_api_test.ps1 probe -Node 17453 -Term ""Morgan Dollar"""
    }

    'node' {
        $m = Invoke-GS -Base $Base -Path 'GetNodeRequest' -Query @{ NodeId = $Node } -Token $Token -Key $Key -Level $Level -Timeout $Timeout
        Write-Host ("NODE {0} -> HTTP {1}  ({2})  {3} ms" -f $Node, $m.Status, (Get-Hint $m.Status), $m.Ms)
        Write-Host ("  " + $m.Url)
        if ($m.Err) { Write-Host ("ERROR: " + $m.Err) }
        Write-Host ""
        Write-Host (Format-Body $m.Body)
    }

    'search' {
        $p  = if ($Path)  { $Path }  else { 'SearchRequest' }
        $pn = if ($Param) { $Param } else { 'query' }
        $m = Invoke-GS -Base $Base -Path $p -Query @{ $pn = $Term } -Token $Token -Key $Key -Level $Level -Timeout $Timeout
        Write-Host ("SEARCH ""{0}"" via {1}?{2}= -> HTTP {3}  ({4})  {5} ms" -f $Term, $p, $pn, $m.Status, (Get-Hint $m.Status), $m.Ms)
        Write-Host ("  " + $m.Url)
        if ($m.Err) { Write-Host ("ERROR: " + $m.Err) }
        Write-Host ""
        Write-Host (Format-Body $m.Body)
    }
}
