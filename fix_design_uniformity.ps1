# ============================================
# StockSathi Design Uniformity Fix
# Fix hover effects and border radius
# ============================================

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  StockSathi Design Fix" -ForegroundColor Cyan
Write-Host "  Hover Effects + Border Radius" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$dashboardPath = Join-Path $scriptPath "pages\dashboards"

Write-Host "Fixing dashboard files...`n" -ForegroundColor Yellow

$files = Get-ChildItem -Path $dashboardPath -Filter "*.php"
$updatedCount = 0

foreach ($file in $files) {
    Write-Host "Processing: $($file.Name)..." -ForegroundColor White
    
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $originalContent = $content
        $changes = 0
        
        # Fix border-radius: 16px -> 10px (reduce large radius)
        if ($content -match 'border-radius:\s*16px') {
            $content = $content -replace 'border-radius:\s*16px', 'border-radius: 10px'
            $changes++
            Write-Host "  ✓ Reduced border-radius 16px → 10px" -ForegroundColor Gray
        }
        
        # Fix border-radius: 12px -> 8px (make more uniform)
        if ($content -match 'border-radius:\s*12px') {
            $content = $content -replace 'border-radius:\s*12px', 'border-radius: 8px'
            $changes++
            Write-Host "  ✓ Reduced border-radius 12px → 8px" -ForegroundColor Gray
        }
        
        # Fix old blue gradient backgrounds
        if ($content -match '#2E4A73|#4A6FA5|#5B7FA8') {
            $content = $content -replace '#2E4A73', '#115e59'
            $content = $content -replace '#4A6FA5', '#14b8a6'
            $content = $content -replace '#5B7FA8', '#0d9488'
            $changes++
            Write-Host "  ✓ Fixed gradient colors to teal" -ForegroundColor Gray
        }
        
        # Fix old blue shadows
        if ($content -match 'rgba\(46,\s*74,\s*115') {
            $content = $content -replace 'rgba\(46,\s*74,\s*115', 'rgba(13, 148, 136'
            $changes++
            Write-Host "  ✓ Fixed shadow colors to teal" -ForegroundColor Gray
        }
        
        # Fix hover border colors
        if ($content -match 'border-color:\s*#4A6FA5') {
            $content = $content -replace 'border-color:\s*#4A6FA5', 'border-color: #14b8a6'
            $changes++
            Write-Host "  ✓ Fixed hover border color" -ForegroundColor Gray
        }
        
        if ($content -ne $originalContent) {
            Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
            Write-Host "  ✅ UPDATED: $changes fix(es) applied" -ForegroundColor Green
            $updatedCount++
        } else {
            Write-Host "  ⏭️  SKIPPED: Already fixed" -ForegroundColor DarkGray
        }
        
    } catch {
        Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Dashboard Files Fixed!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Files updated: $updatedCount" -ForegroundColor Green

Write-Host "`nPress any key to exit..." -ForegroundColor DarkGray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
