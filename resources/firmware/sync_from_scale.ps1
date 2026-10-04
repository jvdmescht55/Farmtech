<#
  sync_from_scale.ps1 — copy the KraalTrac Pro's offline queue to Herd Manager over USB.

  For when the scale has no Wi-Fi: plug it into any Windows laptop that has
  internet, then right-click this file → "Run with PowerShell".
  It asks the scale for its queue, sends every record to https://farmtech.site,
  and only tells the scale to clear its memory once EVERY record was accepted.

  You need the scale's device key once: Herd Manager → KraalTrac Pro → More →
  Devices → your scale → "Device key" (click "New key" and paste it below,
  and on the scale enter it via pairing or DEVICE_KEY).
#>
param(
  [string]$DeviceKey = "PASTE-YOUR-DEVICE-KEY-HERE",
  [string]$Port = "",
  [string]$Server = "https://farmtech.site"
)

$ErrorActionPreference = "Stop"
if ($DeviceKey -like "PASTE-*") { $DeviceKey = Read-Host "Device key" }
if (-not $Port) {
  $ports = [System.IO.Ports.SerialPort]::GetPortNames()
  if ($ports.Count -eq 0) { Write-Host "No scale found — is the USB cable plugged in?" -ForegroundColor Red; exit 1 }
  $Port = $ports[-1]
  Write-Host "Using $Port (pass -Port COM5 to choose another)"
}

$sp = New-Object System.IO.Ports.SerialPort $Port, 115200, None, 8, One
$sp.ReadTimeout = 4000; $sp.NewLine = "`n"
$sp.Open(); Start-Sleep -Milliseconds 1500; $sp.DiscardInBuffer()
$sp.WriteLine("DUMP")

$lines = @(); $inside = $false; $deadline = (Get-Date).AddSeconds(15)
while ((Get-Date) -lt $deadline) {
  try { $l = $sp.ReadLine().Trim() } catch { break }
  if ($l -eq "----BEGIN QUEUE----") { $inside = $true; continue }
  if ($l -eq "----END QUEUE----") { break }
  if ($inside -and $l -and $l -notlike "(empty*") { $lines += $l }
}

if ($lines.Count -eq 0) { Write-Host "Nothing queued on the scale. All caught up." -ForegroundColor Green; $sp.Close(); exit 0 }
Write-Host "$($lines.Count) record(s) found. Sending..."

$failed = 0
foreach ($rec in $lines) {
  $f = $rec.Split("|")
  if ($f.Count -eq 9) { $body = @{ ref=$f[0]; id=$f[1]; tag=$f[2]; type=$f[3]; gender=$f[4]; sire=$f[5]; dam=$f[6]; weight=$f[7]; ts=$f[8] } }
  elseif ($f.Count -eq 6) { $body = @{ ref="usb-$($f[0])-$($f[5])"; id=$f[0]; type=$f[1]; gender=$f[2]; sire=$f[3]; dam=$f[4]; weight=$f[5] } }
  else { continue }
  try {
    $r = Invoke-RestMethod -Method Post -Uri "$Server/api/v1/scans" -Headers @{ Authorization = "Bearer $DeviceKey" } -Body $body
    if ($r.status -ne "SUCCESS") { $failed++ }
  } catch { $failed++; Write-Host "  failed: $rec" -ForegroundColor Yellow }
}

if ($failed -eq 0) {
  $sp.WriteLine("CLEAR $($lines.Count)")
  Write-Host "All $($lines.Count) saved to Herd Manager. Scale memory cleared. Lekker!" -ForegroundColor Green
} else {
  Write-Host "$failed record(s) did not save — the scale keeps everything. Check internet / device key and run again." -ForegroundColor Yellow
}
$sp.Close()
