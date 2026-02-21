# ============================================
# StockSathi Chart Color Update Script
# Automatically updates all dashboard charts from blue to teal
# ============================================

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  StockSathi Chart Color Updater" -ForegroundColor Cyan
Write-Host "  Blue → Teal Color Scheme" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Get the script's directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$dashboardPath = Join-Path $scriptPath "pages\dashboards"

Write-Host "Dashboard Path: $dashboardPath`n" -ForegroundColor Yellow

# Check if directory exists
if (-not (Test-Path $dashboardPath)) {
    Write-Host "ERROR: Dashboard directory not found!" -ForegroundColor Red
    Write-Host "Expected: $dashboardPath" -ForegroundColor Red
    exit 1
}

# Get all PHP files in dashboards directory
$files = Get-ChildItem -Path $dashboardPath -Filter "*.php"

if ($files.Count -eq 0) {
    Write-Host "No PHP files found in dashboards directory!" -ForegroundColor Red
    exit 1
}

Write-Host "Found $($files.Count) dashboard files to update:`n" -ForegroundColor Green

$updatedCount = 0

foreach ($file in $files) {
    Write-Host "Processing: $($file.Name)..." -ForegroundColor White
    
    try {
        # Read file content
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $originalContent = $content
        
        # Track changes
        $changes = 0
        
        # Replace RGB values
        if ($content -match 'rgb\(74, 111, 165\)') {
            $content = $content -replace 'rgb\(74, 111, 165\)', 'rgb(13, 148, 136)'
            $changes++
            Write-Host "  ✓ Updated rgb(74, 111, 165)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgba\(74, 111, 165,') {
            $content = $content -replace 'rgba\(74, 111, 165,', 'rgba(13, 148, 136,'
            $changes++
            Write-Host "  ✓ Updated rgba(74, 111, 165, ...)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgb\(46, 74, 115\)') {
            $content = $content -replace 'rgb\(46, 74, 115\)', 'rgb(13, 148, 136)'
            $changes++
            Write-Host "  ✓ Updated rgb(46, 74, 115)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgba\(46, 74, 115,') {
            $content = $content -replace 'rgba\(46, 74, 115,', 'rgba(13, 148, 136,'
            $changes++
            Write-Host "  ✓ Updated rgba(46, 74, 115, ...)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgb\(107, 155, 199\)') {
            $content = $content -replace 'rgb\(107, 155, 199\)', 'rgb(13, 148, 136)'
            $changes++
            Write-Host "  ✓ Updated rgb(107, 155, 199)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgb\(91, 141, 184\)') {
            $content = $content -replace 'rgb\(91, 141, 184\)', 'rgb(20, 184, 166)'
            $changes++
            Write-Host "  ✓ Updated rgb(91, 141, 184)" -ForegroundColor Gray
        }
        
        if ($content -match 'rgb\(123, 163, 199\)') {
            $content = $content -replace 'rgb\(123, 163, 199\)', 'rgb(45, 212, 191)'
            $changes++
            Write-Host "  ✓ Updated rgb(123, 163, 199)" -ForegroundColor Gray
        }
        
        # Replace Hex values (case insensitive)
        if ($content -match '#1565C0') {
            $content = $content -replace '#1565C0', '#0d9488' -replace '#1565c0', '#0d9488'
            $changes++
            Write-Host "  ✓ Updated #1565C0" -ForegroundColor Gray
        }
        
        if ($content -match '#4A6FA5') {
            $content = $content -replace '#4A6FA5', '#14b8a6' -replace '#4a6fa5', '#14b8a6'
            $changes++
            Write-Host "  ✓ Updated #4A6FA5" -ForegroundColor Gray
        }
        
        if ($content -match '#2E4A73') {
            $content = $content -replace '#2E4A73', '#115e59' -replace '#2e4a73', '#115e59'
            $changes++
            Write-Host "  ✓ Updated #2E4A73" -ForegroundColor Gray
        }
        
        if ($content -match '#42A5F5') {
            $content = $content -replace '#42A5F5', '#2dd4bf' -replace '#42a5f5', '#2dd4bf'
            $changes++
            Write-Host "  ✓ Updated #42A5F5" -ForegroundColor Gray
        }
        
        if ($content -match '#5B7FA8') {
            $content = $content -replace '#5B7FA8', '#14b8a6' -replace '#5b7fa8', '#14b8a6'
            $changes++
            Write-Host "  ✓ Updated #5B7FA8" -ForegroundColor Gray
        }
        
        # Check if any changes were made
        if ($content -ne $originalContent) {
            # Save the file
            Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
            Write-Host "  ✅ UPDATED: $changes color(s) changed" -ForegroundColor Green
            $updatedCount++
        } else {
            Write-Host "  ⏭️  SKIPPED: No blue colors found" -ForegroundColor DarkGray
        }
        
    } catch {
        Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Update Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Files processed: $($files.Count)" -ForegroundColor White
Write-Host "Files updated:   $updatedCount" -ForegroundColor Green
Write-Host "Files skipped:   $($files.Count - $updatedCount)" -ForegroundColor DarkGray
Write-Host "`nAll dashboard charts now use teal colors! 🎨✨" -ForegroundColor Cyan
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Clear your browser cache (Ctrl + Shift + Delete)" -ForegroundColor White
Write-Host "2. Hard refresh the page (Ctrl + F5)" -ForegroundColor White
Write-Host "3. Check all dashboards to verify teal colors" -ForegroundColor White
Write-Host "`nPress any key to exit..." -ForegroundColor DarkGray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
