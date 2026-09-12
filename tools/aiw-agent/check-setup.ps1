<#
.SYNOPSIS
    매핑된 폴더가 지시를 받을 수 있는 상태인지 확인하고 supportworks 에 보고한다.

.DESCRIPTION
    담당자 PC 만 알 수 있는 것들이다 — 폴더가 있는지, git 저장소인지, 매핑에 적은
    브랜치가 실제로 있는지, 커밋되지 않은 변경이 남아 있는지.

    지금까지는 지시를 넣어 봐야 드러났다. 하루에만 세 번 같은 식으로 막혔다:
    폴더가 git 저장소가 아니었고, 미정리 변경이 70건 있었고, 기본 브랜치가
    master 로 적혀 있는데 실제로는 main 이었다. 셋 다 작업이 실패한 뒤에야
    알았고 화면에는 이유 없이 멈춘 것처럼 보였다.

    **읽기만 한다.** 폴더를 만들거나 git init 하거나 변경을 지우지 않는다.
    경로를 잘못 적었는데 스크립트가 알아서 폴더를 만들어 버리면 되돌리기 어렵다.
    무엇을 고쳐야 하는지 알리는 것까지가 여기 일이다.

.EXAMPLE
    .\check-setup.ps1
    .\check-setup.ps1 -NoReport     # 보고하지 않고 화면으로만 확인
#>
param(
    [switch]$NoReport
)

$ErrorActionPreference = 'Stop'

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch { }

Set-Location $PSScriptRoot

$baseUrl = $null
$token   = $null

foreach ($line in Get-Content '.env') {
    if ($line -match '^\s*SW_BASE_URL\s*=\s*(.+?)\s*$')    { $baseUrl = $Matches[1] }
    if ($line -match '^\s*SW_AGENT_TOKEN\s*=\s*(.+?)\s*$') { $token   = $Matches[1] }
}

if (-not $baseUrl -or -not $token) {
    Write-Error '.env 에 SW_BASE_URL 또는 SW_AGENT_TOKEN 이 없습니다.'
    exit 1
}

$headers = @{ Authorization = "Bearer $token"; Accept = 'application/json' }

try {
    $res = Invoke-RestMethod -Uri "$baseUrl/api/aiw/mappings" -Method Get -Headers $headers -TimeoutSec 20
} catch {
    Write-Error "supportworks 에 연결하지 못했습니다: $($_.Exception.Message)"
    exit 1
}

foreach ($m in $res.mappings) {
    $status  = 'ok'
    $message = $null
    $path    = $m.local_path

    if (-not $path -or -not (Test-Path -LiteralPath $path)) {
        $status  = 'path_missing'
        $message = "폴더가 없습니다: $path — 매핑의 소스 경로를 고치거나 그 위치에 저장소를 두세요."
    }
    elseif (-not (Test-Path -LiteralPath (Join-Path $path '.git'))) {
        $status  = 'not_git_repo'
        $message = "git 저장소가 아닙니다: $path — 브랜치 분리·커밋·배포가 모두 git 위에서 돕니다. git init 과 원격 연결이 필요합니다."
    }
    else {
        Push-Location -LiteralPath $path

        try {
            # --no-optional-locks: 인덱스 잠금을 잡지 않는다. 이게 없으면 점검이
            # 데몬의 커밋·푸시와 부딪혀 index.lock 오류를 낸다 — 실제로 그렇게
            # 푸시가 실패했다. 점검은 읽기만 하므로 잠글 이유가 없다.
            $branch = (git --no-optional-locks rev-parse --abbrev-ref HEAD 2>$null)
            $want   = $m.default_branch

            if ($want) {
                $exists = (git --no-optional-locks rev-parse --verify --quiet "refs/heads/$want" 2>$null)

                if (-not $exists) {
                    $available = ((git --no-optional-locks branch --format='%(refname:short)' 2>$null) -join ', ')
                    $status  = 'branch_missing'
                    $message = "기본 브랜치 '$want' 가 없습니다. 현재 '$branch', 있는 브랜치: $available"
                }
            }

            if ($status -eq 'ok') {
                $dirty = @(git --no-optional-locks status --porcelain 2>$null)

                if ($dirty.Count -gt 0) {
                    # 커밋되지 않은 변경은 새 브랜치로 따라와 작업 결과와 섞인다.
                    $sample = ($dirty | Select-Object -First 5 | ForEach-Object { $_.Trim() }) -join ' / '
                    $status  = 'dirty_tree'
                    $message = "커밋되지 않은 변경 $($dirty.Count)건: $sample — 커밋하거나 .gitignore 로 정리해야 지시를 시작할 수 있습니다."
                }
            }
        } catch {
            $status  = 'unknown'
            $message = "확인 중 오류: $($_.Exception.Message)"
        } finally {
            Pop-Location
        }
    }

    $color = if ($status -eq 'ok') { 'Green' } elseif ($status -eq 'dirty_tree') { 'Yellow' } else { 'Red' }
    Write-Host ("  #{0,-4} {1,-18} {2}" -f $m.project_id, $m.project_name, $status) -ForegroundColor $color

    if ($message) { Write-Host "         $message" -ForegroundColor DarkGray }

    if ($NoReport) { continue }

    try {
        Invoke-RestMethod -Uri "$baseUrl/api/aiw/mappings/setup" -Method Post -Headers $headers -TimeoutSec 20 `
            -ContentType 'application/json' `
            -Body (@{ project_id = $m.project_id; status = $status; message = $message } | ConvertTo-Json) | Out-Null
    } catch {
        Write-Host "         보고 실패: $($_.Exception.Message)" -ForegroundColor Red
    }
}
