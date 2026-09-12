<#
.SYNOPSIS
    PC 가 켜지면 사람이 로그인하지 않아도 데몬이 뜨도록 작업을 등록한다.

.DESCRIPTION
    "로그온 시" 트리거만으로는 부족하다. 새벽에 Windows 업데이트로 재부팅되면
    PC 는 로그인 화면에서 멈추고, 아무도 앉지 않는 한 담당자는 계속 오프라인이다.
    화면에는 "온라인 상태인 담당자가 없습니다" 로만 보여 이유를 알 수 없다.

    그래서 트리거를 "시스템 시작 시" 로 두고, 로그온 여부와 무관하게 실행한다.
    이 방식은 Windows 가 비밀번호를 LSA 비밀 저장소에 넣는다 — 자동 로그온처럼
    레지스트리에 평문으로 남기지 않는다.

    로그온 트리거도 함께 남긴다. 둘 다 걸려 두 번 떠도 문제가 없다 —
    LOG_DIR 별 PID 잠금이 두 번째를 스스로 물러나게 한다.

.NOTES
    관리자 권한 PowerShell 에서 실행해야 한다.
    비밀번호는 이 창에만 입력되고 파일이나 로그에 남지 않는다.

.EXAMPLE
    .\setup-autostart.ps1
#>
param(
    [string[]]$Slugs = @('mangoshop', 'leefriends', 'unicorn')
)

$ErrorActionPreference = 'Stop'

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
    ).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host '관리자 권한이 필요합니다.' -ForegroundColor Red
    Write-Host '시작 → PowerShell 을 마우스 오른쪽 → "관리자 권한으로 실행" 후 다시 실행하세요.'
    exit 1
}

$dir  = $PSScriptRoot
$user = "$env:USERDOMAIN\$env:USERNAME"

Write-Host "대상 계정 : $user"
Write-Host "데몬 폴더 : $dir"
Write-Host ''
Write-Host '이 계정의 Windows 로그인 비밀번호를 입력하세요.' -ForegroundColor Cyan
Write-Host 'Windows 가 LSA 에 보관하며, 파일이나 로그에는 남지 않습니다.'

$secure = Read-Host '비밀번호' -AsSecureString

if ($secure.Length -eq 0) {
    Write-Host '입력이 비어 있어 중단합니다.' -ForegroundColor Red
    exit 1
}

# Register-ScheduledTask 는 평문만 받는다. 메모리에서만 쓰고 바로 지운다.
$bstr  = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
$plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)

try {
    foreach ($slug in $Slugs) {
        $envFile = Join-Path $dir ".env.$slug"

        if (-not (Test-Path $envFile)) {
            Write-Host "  건너뜀: $envFile 이 없습니다." -ForegroundColor Yellow
            continue
        }

        $name = "AIW Agent - $slug"

        $action = New-ScheduledTaskAction -Execute 'powershell.exe' `
            -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$dir\start-agent.ps1`" $slug" `
            -WorkingDirectory $dir

        # 부팅 직후엔 네트워크가 아직 올라오지 않았다. 1분 늦춰 첫 하트비트를 살린다.
        $atStartup = New-ScheduledTaskTrigger -AtStartup
        $atStartup.Delay = 'PT1M'

        $atLogon = New-ScheduledTaskTrigger -AtLogOn -User $user
        $atLogon.Delay = 'PT1M'

        $settings = New-ScheduledTaskSettingsSet `
            -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
            -StartWhenAvailable `
            -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 2) `
            -ExecutionTimeLimit (New-TimeSpan -Seconds 0)   # 기본 3일 제한을 없앤다

        Register-ScheduledTask -TaskName $name `
            -Action $action -Trigger @($atStartup, $atLogon) -Settings $settings `
            -User $user -Password $plain -RunLevel Limited -Force | Out-Null

        Write-Host "  등록: $name  (시작 시 + 로그온 시, 로그온 여부 무관)" -ForegroundColor Green
    }
}
finally {
    # 평문 비밀번호를 메모리에 남기지 않는다.
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    $plain = $null
    [GC]::Collect()
}

Write-Host ''
Write-Host '=== 등록 결과 ===' -ForegroundColor Cyan

Get-ScheduledTask | Where-Object { $_.TaskName -like 'AIW Agent*' } | ForEach-Object {
    $t = $_
    $triggers = ($t.Triggers | ForEach-Object { $_.CimClass.CimClassName -replace 'MSFT_Task|Trigger', '' }) -join ', '
    '{0,-26} {1,-8} 트리거: {2}  실행계정: {3}' -f $t.TaskName, $t.State, $triggers, $t.Principal.UserId
}

Write-Host ''
Write-Host '재부팅 후 로그인하지 않은 상태에서도 담당자가 온라인인지 확인하세요.' -ForegroundColor Yellow
