<#
.SYNOPSIS
    프로젝트별 데몬을 띄운다.

.DESCRIPTION
    데몬 하나가 여러 프로젝트를 맡으면, 그 하나가 죽는 순간 모든 프로젝트가
    한꺼번에 오프라인이 된다. 화면에는 "담당자 없음" 으로만 보이므로 어느
    프로젝트가 왜 멈췄는지 구분이 되지 않는다. 그래서 프로젝트마다 따로 띄운다.

    공통 설정은 .env 하나에 두고, 프로젝트마다 다른 것(토큰·로그 폴더)만
    .env.<slug> 에 둔다. dotenv 는 이미 설정된 환경변수를 덮지 않으므로,
    여기서 먼저 넣어 주면 그 값이 이긴다.

    같은 PC 에서 여러 개가 떠도 서로 막지 않는다 — PID 잠금은 LOG_DIR 별로
    잡히기 때문이다. 반대로 LOG_DIR 을 같게 두면 두 번째가 스스로 물러난다.

.EXAMPLE
    .\start-agent.ps1 mangoshop
    .\start-agent.ps1 unicorn
#>
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Slug
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$envFile = ".env.$Slug"

if (-not (Test-Path $envFile)) {
    Write-Error "$envFile 이 없습니다. SW_AGENT_TOKEN 과 LOG_DIR 을 담은 파일을 먼저 만드세요."
    exit 1
}

# 파일에 있는 값만 환경변수로 올린다. 나머지는 .env 에서 채워진다.
Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()

    if ($line -eq '' -or $line.StartsWith('#')) { return }

    $i = $line.IndexOf('=')
    if ($i -lt 1) { return }

    $name  = $line.Substring(0, $i).Trim()
    $value = $line.Substring($i + 1).Trim()

    Set-Item -Path "env:$name" -Value $value
}

if (-not $env:SW_AGENT_TOKEN) {
    Write-Error "$envFile 에 SW_AGENT_TOKEN 이 없습니다."
    exit 1
}

if (-not $env:LOG_DIR) {
    # 로그 폴더가 겹치면 PID 잠금도 겹쳐 두 번째 데몬이 뜨지 못한다.
    Write-Error "$envFile 에 LOG_DIR 이 없습니다. 프로젝트마다 달라야 합니다."
    exit 1
}

if (-not (Test-Path 'dist/index.js')) {
    # 실행 폴더에는 소스가 없다. 빌드는 저장소에서 하고 sync-to-home.ps1 로 내보낸다.
    Write-Error 'dist\index.js 가 없습니다. 저장소(tools\aiw-agent)에서 .\sync-to-home.ps1 을 실행하세요.'
    exit 1
}

Write-Host "[$Slug] 데몬을 시작합니다 (LOG_DIR=$env:LOG_DIR)" -ForegroundColor Cyan

node dist/index.js
