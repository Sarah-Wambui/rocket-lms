<#
 CVH Africa — sort the TrainerCentral recordings downloaded by hand into the layout 23_import_trainercentral.php reads.

   powershell -ExecutionPolicy Bypass -File database\cvh\31_sort_recordings.ps1 -Downloads <folder> [-Export <zip or folder>] [-Apply]

 TrainerCentral's Download button gives each recording a name of its own, so files are matched by their exact size
 (every recording's size is in materials.json and no two are the same). Without -Apply it only shows what it found;
 with -Apply it moves (not copies, the recordings are ~13 GB) each match to <Downloads>\recordings\<recordingId>.mp4.
 -Export defaults to the trainercentral_upload.zip next to the rocket-lms folder.
#>
param(
    [Parameter(Mandatory = $true)][string]$Downloads,
    [string]$Export,
    [switch]$Apply
)
$ErrorActionPreference = 'Stop'
if (-not $Export) { $Export = Join-Path (Split-Path $MyInvocation.MyCommand.Path) '..\..\..\trainercentral_upload.zip' }

# materials.json is ~3 MB, past ConvertFrom-Json's limit in Windows PowerShell 5.1
Add-Type -AssemblyName System.Web.Extensions
if ((Get-Item $Export).PSIsContainer) {
    $json = [IO.File]::ReadAllText((Join-Path $Export 'materials.json'))
} else {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [IO.Compression.ZipFile]::OpenRead((Resolve-Path $Export))
    try {
        $entry = $zip.Entries | Where-Object { $_.FullName -like '*materials.json' } | Select-Object -First 1
        $reader = New-Object IO.StreamReader($entry.Open())
        $json = $reader.ReadToEnd(); $reader.Close()
    } finally { $zip.Dispose() }
}
$parser = New-Object Web.Script.Serialization.JavaScriptSerializer
$parser.MaxJsonLength = [int]::MaxValue
$materials = $parser.DeserializeObject($json)

# the recordings, once each (one recording is used by two lessons)
$recordings = [ordered]@{}
foreach ($session in $materials.Values) {
    foreach ($m in @($session['materials'])) {
        if ($m -and $m['materialType'] -eq '8' -and -not $recordings.Contains($m['recordingId'])) {
            $recordings[$m['recordingId']] = [pscustomobject]@{ Id = $m['recordingId']; Name = $m['name']; Size = [long]$m['size'] }
        }
    }
}

$out = Join-Path $Downloads 'recordings'
$bySize = @{}
foreach ($f in Get-ChildItem $Downloads -File) { $bySize[$f.Length] = $f }

$found = 0
foreach ($r in $recordings.Values) {
    $target = Join-Path $out "$($r.Id).mp4"
    if (Test-Path $target) { Write-Host "  done     $($r.Name)"; $found++; continue }
    $f = $bySize[$r.Size]
    if (-not $f) { Write-Host "  MISSING  $($r.Name)  ($([math]::Round($r.Size / 1MB)) MB)" -ForegroundColor Yellow; continue }
    $found++
    Write-Host "  found    $($r.Name)  <-  $($f.Name)"
    if ($Apply) {
        New-Item -ItemType Directory -Force $out | Out-Null
        Move-Item -LiteralPath $f.FullName -Destination $target
    }
}
Write-Host ""
Write-Host "$found of $($recordings.Count) recordings found."
if (-not $Apply -and $found) { Write-Host "Run again with -Apply to move them into $out" }
