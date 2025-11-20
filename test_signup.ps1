$body = @{
    first_name = "Test"
    last_name = "User"
    email = "testuser$(Get-Random)@test.com"
    password = "test123456"
} | ConvertTo-Json

Write-Host "Testing signup endpoint..."
Write-Host "URL: http://172.20.10.6/mabote_api/signup_extended.php"
Write-Host "Body: $body"
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "http://172.20.10.6/mabote_api/signup_extended.php" -Method POST -Body $body -ContentType "application/json"
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 5)
} catch {
    Write-Host "ERROR!" -ForegroundColor Red
    Write-Host $_.Exception.Message
    if ($_.ErrorDetails) {
        Write-Host "Error Details:"
        Write-Host $_.ErrorDetails.Message
    }
    if ($_.Response) {
        Write-Host "Response Status: $($_.Exception.Response.StatusCode.value__)"
    }
}






