Write-Host "Checking database structure..." -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "http://172.20.10.6/mabote_api/test_signup_direct.php"
Write-Host ""
Write-Host "Result:" -ForegroundColor Green
$response | ConvertTo-Json -Depth 5






