# Lanzador local de PICS con PHP 8.4 y SQLite.
# Uso: .\serve.ps1

$projectRoot = $PSScriptRoot
$runtimeRoot = Join-Path $projectRoot 'runtime\php'
$runtimePhp = Join-Path $runtimeRoot 'php.exe'
$localIni = Join-Path $projectRoot 'php.local.ini'

if (-not (Test-Path -LiteralPath $runtimePhp)) {
    throw "No se encontró PHP 8.4 portable en: $runtimePhp"
}

if (-not (Test-Path -LiteralPath $localIni)) {
    throw "No se encontró la configuración local de PHP en: $localIni"
}

$env:PHPRC = $localIni
$env:Path = $runtimeRoot + [System.IO.Path]::PathSeparator + $env:Path
$env:OPENSSL_CONF = Join-Path $runtimeRoot 'extras\ssl\openssl.cnf'

Set-Location -LiteralPath $projectRoot

# Laravel solo conserva PHPRC en el proceso HTTP cuando se usa --no-reload.
& $runtimePhp artisan serve --host=127.0.0.1 --port=8000 --no-reload
