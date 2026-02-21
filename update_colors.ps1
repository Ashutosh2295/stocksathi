# Update all hardcoded teal colors to blue across the project

$files = @(
    'pages\dashboards\accountant.php',
    'pages\dashboards\sales-executive.php',
    'pages\dashboards\store-manager.php',
    'pages\dashboards\super-admin.php',
    'pages\sales-dashboard.php',
    'pages\settings.php',
    'js\theme-manager.js'
)

foreach ($file in $files) {
    $fullPath = Join-Path $PSScriptRoot $file
    if (Test-Path $fullPath) {
        Write-Host "Updating: $file"
        $content = Get-Content $fullPath -Raw
        
        # Replace teal colors with blue
        $content = $content -replace '#0d9488', '#4f82d5'
        $content = $content -replace '#115e59', '#3a63a5'
        $content = $content -replace '#0f766e', '#3a63a5'
        $content = $content -replace '#14b8a6', '#4f82d5'
        $content = $content -replace 'rgba\(13, 148, 136', 'rgba(79, 130, 213'
        $content = $content -replace '#115E59', '#3a63a5'
        $content = $content -replace '#0D9488', '#4f82d5'
        
        Set-Content $fullPath -Value $content -NoNewline
        Write-Host "  ✓ Updated successfully"
    } else {
        Write-Host "  ✗ File not found: $fullPath"
    }
}

Write-Host "`nColor update complete!"
