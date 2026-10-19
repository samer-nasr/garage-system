$ErrorActionPreference = 'Stop'
$projectPath = $PSScriptRoot
$phpPath = 'C:\wamp64\bin\php\php8.3.28\php.exe'
$mysqlPath = 'C:\wamp64\bin\mysql\mysql8.4.7\bin\mysqld.exe'
$mysqlDataPath = Join-Path $projectPath 'work\mysql-data'

function Test-LocalPort([int]$Port) {
    $client = [System.Net.Sockets.TcpClient]::new()
    try { $client.Connect('127.0.0.1', $Port); return $true } catch { return $false } finally { $client.Dispose() }
}

if (!(Test-Path -LiteralPath $phpPath)) { throw 'Update phpPath in start-local.ps1 for this computer.' }
if (!(Test-LocalPort 3307)) {
    if (!(Test-Path -LiteralPath $mysqlDataPath)) { throw 'Configure MySQL in .env, or initialize the project-local MySQL data directory first. See README.' }
    $mysqlArgs = @('--no-defaults', '--basedir=C:/wamp64/bin/mysql/mysql8.4.7', ('--datadir="' + $mysqlDataPath + '"'), '--port=3307', '--bind-address=127.0.0.1', '--mysqlx=0')
    Start-Process -FilePath $mysqlPath -ArgumentList $mysqlArgs -WorkingDirectory $projectPath -WindowStyle Hidden
    $ready = $false
    for ($attempt = 0; $attempt -lt 20; $attempt++) { if (Test-LocalPort 3307) { $ready = $true; break }; Start-Sleep -Milliseconds 500 }
    if (!$ready) { throw 'MySQL did not start. Check work/mysql-data/*.err.' }
}
if (!(Test-LocalPort 8000)) {
    Start-Process -FilePath $phpPath -ArgumentList @('-d','xdebug.mode=off','artisan','serve','--host=127.0.0.1','--port=8000') -WorkingDirectory $projectPath -WindowStyle Hidden -RedirectStandardOutput (Join-Path $projectPath 'work\preview.log') -RedirectStandardError (Join-Path $projectPath 'work\preview-error.log')
}
Write-Output 'Website: http://127.0.0.1:8000/lt'
Write-Output 'Admin: http://127.0.0.1:8000/admin/login'
