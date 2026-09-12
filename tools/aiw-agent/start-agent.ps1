<#
.SYNOPSIS
    프로젝트 하나를 맡는 데몬 프로세스를 띄운다.

.DESCRIPTION
    토큰은 PC 당 하나(.env)다. 프로젝트마다 다른 것은 "어느 프로젝트를 맡는가"와
    "로그를 어디에 쌓는가" 둘뿐이므로, 그 둘만 환경변수로 넣는다.
    dotenv 는 이미 설정된 환경변수를 덮지 않으므로 여기서 넣은 값이 이긴다.

    LOG_DIR 이 프로젝트마다 달라야 한다. PID 잠금이 그 폴더에 잡히기 때문에,
    같게 두면 두 번째 프로세스가 스스로 물러난다.

.EXAMPLE
    .\start-agent.ps1 -ProjectId 23
#>
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [int]$ProjectId,

    [string]$Label
)

$ErrorActionPreference = 'Stop'

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch { }

Set-Location $PSScriptRoot

if (-not (Test-Path '.env')) {
    Write-Error '.env 가 없습니다. SW_BASE_URL 과 SW_AGENT_TOKEN 이 필요합니다.'
    exit 1
}

if (-not (Test-Path 'dist/index.js')) {
    # 실행 폴더에는 소스가 없다. 빌드는 저장소에서 하고 sync-to-home.ps1 로 내보낸다.
    Write-Error 'dist\index.js 가 없습니다. 저장소(tools\aiw-agent)에서 .\sync-to-home.ps1 을 실행하세요.'
    exit 1
}

$env:SW_PROJECT_ID = $ProjectId
$env:LOG_DIR       = "./logs-$ProjectId"

$name = if ($Label) { "$Label (#$ProjectId)" } else { "프로젝트 #$ProjectId" }

Write-Host "[$name] 데몬을 시작합니다 (LOG_DIR=$env:LOG_DIR)" -ForegroundColor Cyan

node dist/index.js
