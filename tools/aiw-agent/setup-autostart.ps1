<#
.SYNOPSIS
    로그인하면 이 폴더의 데몬이 전부 자동으로 뜨도록 작업 하나를 등록한다.

.DESCRIPTION
    프로젝트마다 작업을 따로 만들지 않는다. 그러면 프로젝트를 붙일 때마다
    작업 스케줄러를 다시 손대야 하고, 빠뜨리면 그 담당자만 조용히 오프라인이
    된다 — 화면에는 "담당자 없음" 으로만 보여 이유를 찾기 어렵다.

    작업은 하나만 두고, 그 하나가 start-all.ps1 을 부른다. start-all 은
    `.env.<이름>` 을 훑어 있는 만큼 띄운다. 그래서 새 프로젝트는 `.env.<이름>`
    하나만 만들면 되고 여기는 다시 건드릴 필요가 없다.

    같은 작업을 10분마다 다시 부른다. 이미 떠 있는 것은 건너뛰므로 부담이 없고,
    그 사이에 추가된 데몬이나 죽은 데몬이 저절로 살아난다.

    비밀번호를 묻지 않고 관리자 권한도 필요 없다 — 자기 계정으로 자기 세션에서
    도는 작업이기 때문이다. 데몬은 구독 로그인(C:\Users\<계정>\.claude)으로
    Claude Code 를 실행하므로 어차피 그 사용자 세션에서 도는 것이 맞다.

.EXAMPLE
    .\setup-autostart.ps1
    .\setup-autostart.ps1 -StartNow
#>
param(
    [switch]$StartNow,
    [int]$RepeatMinutes = 10
)

$ErrorActionPreference = 'Stop'

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch { }

$dir      = $PSScriptRoot
$user     = "$env:USERDOMAIN\$env:USERNAME"
$taskName = 'AIW Agents'

Write-Host "대상 계정 : $user"
Write-Host "데몬 폴더 : $dir"
Write-Host ''

Write-Host '찾은 데몬:' -ForegroundColor Cyan
& (Join-Path $dir 'start-all.ps1') -List

# 프로젝트별로 만들어 두었던 옛 작업은 치운다. 남겨 두면 같은 데몬을 두 곳에서
# 띄우려 하고, 프로젝트를 지운 뒤에도 실패한 작업만 계속 남는다.
Get-ScheduledTask | Where-Object { $_.TaskName -like 'AIW Agent - *' } | ForEach-Object {
    Unregister-ScheduledTask -TaskName $_.TaskName -Confirm:$false
    Write-Host "  옛 작업 제거: $($_.TaskName)" -ForegroundColor DarkGray
}

$action = New-ScheduledTaskAction -Execute 'powershell.exe' `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$dir\start-all.ps1`"" `
    -WorkingDirectory $dir

# 로그인 직후엔 네트워크가 아직 올라오지 않았을 수 있다. 1분 늦춰
# 첫 하트비트가 실패하지 않게 한다.
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $user
$trigger.Delay = 'PT1M'

# 주기 반복은 트리거에서 직접 만들 수 없어, 일회성 트리거의 설정을 빌려 온다.
$repeat = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes $RepeatMinutes)
$trigger.Repetition = $repeat.Repetition

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)   # start-all 은 띄우고 바로 끝난다

# 권한을 올리지 않는다. 데몬이 여는 것은 매핑된 작업 폴더뿐이다.
$principal = New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive -RunLevel Limited

# 실패했는데 성공으로 찍으면 재부팅해 봐야 안 뜬 이유를 찾게 된다.
try {
    Register-ScheduledTask -TaskName $taskName `
        -Action $action -Trigger $trigger -Settings $settings -Principal $principal `
        -Force -ErrorAction Stop | Out-Null

    Write-Host ''
    Write-Host "등록: $taskName  (로그온 시 + $RepeatMinutes 분마다)" -ForegroundColor Green
} catch {
    Write-Host ''
    Write-Host "실패: $taskName  —  $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

if ($StartNow) { Start-ScheduledTask -TaskName $taskName }

Write-Host ''
Get-ScheduledTask | Where-Object { $_.TaskName -like 'AIW Agent*' } | ForEach-Object {
    '{0,-14} {1,-9} 로그온유형={2}' -f $_.TaskName, $_.State, $_.Principal.LogonType
}

Write-Host ''
Write-Host "이제 .env.<이름> 을 만들면 다음 실행($RepeatMinutes 분 안)에 저절로 뜹니다." -ForegroundColor Yellow
