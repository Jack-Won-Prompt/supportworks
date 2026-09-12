<#
.SYNOPSIS
    저장소에서 빌드해 데몬 실행 폴더로 내보낸다.

.DESCRIPTION
    소스는 저장소(tools/aiw-agent/src)에 두고, 실제로 도는 것은 별도 폴더에 둔다.
    코드는 버전 관리를 받아야 하고, 토큰·로그는 저장소에 들어가면 안 되기 때문이다.

    코드를 고친 뒤에는 이 스크립트 한 번이면 반영된다.
    데몬이 떠 있으면 dist 를 덮어써도 이미 메모리에 올라간 코드는 그대로이므로,
    실제로 바뀌게 하려면 다시 띄워야 한다 — 아래에서 알려 준다.

.EXAMPLE
    .\sync-to-home.ps1
    .\sync-to-home.ps1 -Restart      # 동기화 후 데몬까지 다시 띄운다
#>
param(
    [string]$DaemonHome = 'E:\xampp\htdocs\00.al_demon',
    [switch]$Restart
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

if (-not (Test-Path $DaemonHome)) {
    Write-Error "$DaemonHome 이 없습니다."
    exit 1
}

Write-Host '빌드 중...' -ForegroundColor Cyan
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Error '빌드 실패. 내보내지 않습니다.'
    exit 1
}

# dist 는 통째로 갈아 끼운다. 지운 파일이 남아 있으면 옛 코드가 섞인다.
if (Test-Path "$DaemonHome\dist") { Remove-Item "$DaemonHome\dist" -Recurse -Force }
Copy-Item 'dist' "$DaemonHome\dist" -Recurse -Force
Write-Host "  dist → $DaemonHome\dist" -ForegroundColor Green

# 실행 스크립트와 의존성 목록도 저장소 쪽이 원본이다.
foreach ($f in @('package.json', 'package-lock.json', 'start-agent.ps1', 'start-all.ps1', 'setup-autostart.ps1', 'check-setup.ps1')) {
    Copy-Item $f "$DaemonHome\$f" -Force
    Write-Host "  $f"
}

# 의존성이 바뀌었으면 알려 준다. 말없이 넘어가면 실행 폴더만 옛 버전으로 남는다.
$repoLock = (Get-FileHash 'package-lock.json').Hash
$homeMods = Join-Path $DaemonHome 'node_modules'

if (-not (Test-Path $homeMods)) {
    Write-Host ''
    Write-Host "$DaemonHome 에서 npm ci --omit=dev 를 실행하세요." -ForegroundColor Yellow
}

if ($Restart) {
    Write-Host ''
    Write-Host '데몬을 다시 띄웁니다...' -ForegroundColor Cyan

    foreach ($d in Get-ChildItem $DaemonHome -Directory -Filter 'logs*') {
        $pidFile = Join-Path $d.FullName 'daemon.pid'

        if (Test-Path $pidFile) {
            $procId = Get-Content $pidFile -ErrorAction SilentlyContinue
            if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue }
        }
    }

    Start-Sleep -Seconds 3

    # 어느 프로젝트를 띄울지는 서버가 안다. 여기서 목록을 들고 있지 않는다.
    & (Join-Path $DaemonHome 'start-all.ps1')
} else {
    Write-Host ''
    Write-Host '데몬은 아직 옛 코드로 돌고 있습니다. -Restart 를 붙이거나 직접 다시 띄우세요.' -ForegroundColor Yellow
}
