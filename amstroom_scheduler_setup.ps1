# amstroom_scheduler_setup.ps1
# Run this script ONCE as Administrator to register the Laravel scheduler
# in Windows Task Scheduler so emails send automatically every day at the configured time.

$phpPath      = (Get-Command php -ErrorAction SilentlyContinue).Source
$artisanPath  = "f:\PROJECT\amstroom\artisan"
$taskName     = "AmstRoom Laravel Scheduler"
$logFile      = "f:\PROJECT\amstroom\storage\logs\scheduler.log"

if (-not $phpPath) {
    Write-Error "PHP not found in PATH. Please ensure PHP is installed and on your PATH."
    exit 1
}

Write-Host "PHP found at: $phpPath"
Write-Host "Registering Windows Task: '$taskName'..."

# Build the action: php artisan schedule:run >> storage/logs/scheduler.log 2>&1
$action  = New-ScheduledTaskAction `
    -Execute $phpPath `
    -Argument "$artisanPath schedule:run >> `"$logFile`" 2>&1" `
    -WorkingDirectory "f:\PROJECT\amstroom"

# Trigger: every 1 minute, indefinitely
$trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)

# Settings: run even if no user is logged on, run ASAP if missed
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 1) `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew

# Principal: run as SYSTEM (no login required)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

# Register the task
Register-ScheduledTask `
    -TaskName $taskName `
    -Action   $action `
    -Trigger  $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "Runs Laravel schedule:run every minute for AmstRoom (email summaries, etc.)" `
    -Force

Write-Host ""
Write-Host "SUCCESS: Task '$taskName' registered." -ForegroundColor Green
Write-Host "The scheduler will now run every minute."
Write-Host "Emails will be sent automatically at the time configured in Settings."
Write-Host ""
Write-Host "To verify, run: Get-ScheduledTask -TaskName '$taskName'"
Write-Host "To remove, run: Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false"
