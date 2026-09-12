<#
.SYNOPSIS
    supportworks 에 물어 이 PC 가 맡은 프로젝트만큼 데몬을 띄운다.

.DESCRIPTION
    프로젝트 목록을 로컬에 적어 두지 않는다. supportworks 에서 프로젝트를 만들고
    담당자에 매핑하면, 다음 실행 때 이 스크립트가 그것을 보고 프로세스를 띄운다.
    PC 에 와서 설정 파일을 만드는 단계가 없다.

    토큰은 PC 당 하나다(.env). 프로젝트마다 토큰을 만들면 그 토큰을 PC 로
    전달할 경로가 필요해지고, 그 경로가 곧 자격증명 유출 통로가 된다.
    구분은 SW_PROJECT_ID 로만 한다.

    같은 일을 반복해서 불러도 안전하다 — 살아 있는 것은 건너뛰고, 겹쳐 띄워도
    LOG_DIR 별 PID 잠금이 두 번째를 물러나게 한다. 그래서 주기적으로 다시 부르면
    새로 매핑된 프로젝트가 저절로 뜨고 죽은 프로세스도 저절로 살아난다.

.EXAMPLE
    .\start-all.ps1
    .\start-all.ps1 -List      # 띄우지 않고 무엇을 맡았는지만 본다
#>
param(
    [switch]$List
)

$ErrorActionPreference = 'Stop'

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch { }

Set-Location $PSScriptRoot

if (-not (Test-Path '.env')) {
    Write-Error '.env 가 없습니다.'
    exit 1
}

$baseUrl = $null
$token   = $null

foreach ($line in Get-Content '.env') {
    if ($line -match '^\s*SW_BASE_URL\s*=\s*(.+?)\s*$')     { $baseUrl = $Matches[1] }
    if ($line -match '^\s*SW_AGENT_TOKEN\s*=\s*(.+?)\s*$')  { $token   = $Matches[1] }
}

if (-not $baseUrl -or -not $token) {
    Write-Error '.env 에 SW_BASE_URL 또는 SW_AGENT_TOKEN 이 없습니다.'
    exit 1
}

try {
    $res = Invoke-RestMethod -Uri "$baseUrl/api/aiw/mappings" -Method Get -TimeoutSec 20 `
        -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
} catch {
    # 서버가 잠깐 안 되는 것과 토큰이 틀린 것은 대처가 다르다. 구분해서 알린다.
    $code = $_.Exception.Response.StatusCode.value__

    if ($code -eq 401 -or $code -eq 403) {
        Write-Error "담당자 토큰이 거부되었습니다($code). supportworks 에서 토큰을 다시 발급해 .env 에 넣으세요."
    } else {
        Write-Error "supportworks 에 연결하지 못했습니다: $($_.Exception.Message)"
    }

    exit 1
}

if (-not $res.mappings -or $res.mappings.Count -eq 0) {
    Write-Host '이 PC 에 매핑된 프로젝트가 없습니다. supportworks 에서 매핑하세요.' -ForegroundColor Yellow
    exit 0
}

foreach ($m in $res.mappings) {
    $logDir  = "logs-$($m.project_id)"
    $pidFile = Join-Path $PSScriptRoot (Join-Path $logDir 'daemon.pid')
    $running = $false

    if (Test-Path $pidFile) {
        $recorded = Get-Content $pidFile -ErrorAction SilentlyContinue

        if ($recorded -and (Get-Process -Id $recorded -ErrorAction SilentlyContinue)) {
            $running = $true
        }
    }

    # 경로가 없으면 띄워 봐야 작업 준비 단계에서 실패한다. 여기서 먼저 말해 준다.
    $pathOk = $m.local_path -and (Test-Path -LiteralPath $m.local_path)

    if ($List) {
        '  #{0,-4} {1,-18} {2,-8} 경로 {3,-6} {4}' -f `
            $m.project_id, $m.project_name, $(if ($running) { '실행 중' } else { '멈춤' }),
            $(if ($pathOk) { '있음' } else { '없음' }), $m.local_path
        continue
    }

    if (-not $pathOk) {
        Write-Host "  건너뜀: $($m.project_name) — 경로가 없습니다: $($m.local_path)" -ForegroundColor Red
        continue
    }

    if ($running) {
        Write-Host "  이미 실행 중: $($m.project_name)" -ForegroundColor DarkGray
        continue
    }

    # 프로젝트 이름에 공백이 있으면 인수가 쪼개진다("Mango Shop" → 두 개).
    # -ArgumentList 는 배열을 그대로 공백으로 이어 붙이므로 여기서 따옴표를 씌운다.
    $args = @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-WindowStyle', 'Hidden',
        '-File', ('"{0}"' -f (Join-Path $PSScriptRoot 'start-agent.ps1')),
        '-ProjectId', $m.project_id,
        '-Label', ('"{0}"' -f $m.project_name)
    )

    Start-Process -FilePath 'powershell.exe' -ArgumentList $args `
        -WorkingDirectory $PSScriptRoot -WindowStyle Hidden

    Write-Host "  기동: $($m.project_name) (#$($m.project_id))" -ForegroundColor Green
}
