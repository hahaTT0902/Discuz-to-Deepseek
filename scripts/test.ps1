$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$php = Join-Path $root '.tools\php\php.exe'

if (!(Test-Path $php)) {
    throw "PHP runtime not found at $php."
}

$files = Get-ChildItem -Path $root -Recurse -Filter *.php |
    Where-Object { $_.FullName -notmatch '\\.tools\\' }

$failed = @()
foreach ($file in $files) {
    $output = & $php -l $file.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        $failed += [pscustomobject]@{
            File = $file.FullName
            Output = ($output -join "`n")
        }
    }
}

if ($failed.Count -gt 0) {
    $failed | Format-List | Out-String | Write-Error
    exit 1
}

Write-Host "PHP lint passed for $($files.Count) files."
