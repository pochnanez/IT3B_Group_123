param([int]$Port = 8088)
$ErrorActionPreference='Stop'
$projectPath=Split-Path $PSScriptRoot -Parent
Push-Location $projectPath
$keys=@('CI_ENVIRONMENT','database.defaultGroup','database.tests.database','database.tests.DBDriver','database.tests.DBPrefix','accountaccess.preview','app.baseURL')
$previous=@{}
foreach($key in $keys) { $previous[$key]=[Environment]::GetEnvironmentVariable($key,'Process') }
try {
    $json=& php -d extension=sqlite3 tools/Prepare-ManagementPreview.php
    if($LASTEXITCODE -ne 0) { throw 'Isolated preview creation failed.' }
    $preview=$json | ConvertFrom-Json
    # Normal browser sessions require development mode. Explicitly select the
    # separate SQLite test group; PHPUnit's testing mode suppresses sessions.
    $values=@{'CI_ENVIRONMENT'='development';'database.defaultGroup'='tests';'database.tests.database'=$preview.database;'database.tests.DBDriver'='SQLite3';'database.tests.DBPrefix'='qa_';'accountaccess.preview'='true';'app.baseURL'="http://127.0.0.1:$Port/"}
    foreach($key in $keys) { [Environment]::SetEnvironmentVariable($key,$values[$key],'Process') }
    Write-Output "Synthetic preview only: http://127.0.0.1:$Port/ - the live MySQL database is not selected."
    & php -d extension=sqlite3 -d variables_order=EGPCS -S "127.0.0.1:$Port" -t public
} finally {
    foreach($key in $keys) { [Environment]::SetEnvironmentVariable($key,$previous[$key],'Process') }
    Pop-Location
}
