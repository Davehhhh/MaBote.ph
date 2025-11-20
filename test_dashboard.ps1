Write-Host "Checking dashboard statistics..." -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "http://172.20.10.6/mabote_api/verify_dashboard_stats.php"
Write-Host ""
Write-Host "Database Statistics:" -ForegroundColor Green
$response | ConvertTo-Json -Depth 10




