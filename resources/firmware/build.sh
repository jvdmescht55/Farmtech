#!/usr/bin/env bash
# Rebuild the KraalTrac Pro firmware and publish it for the browser installer
# (farmtech.site/app/install). Needs arduino-cli with the esp32 core and the
# LiquidCrystal I2C, Keypad and ArduinoJson libraries installed.
# Remember to bump FIRMWARE in kraaltrac_pro.ino and "version" in the manifest.
set -euo pipefail
CLI=${ARDUINO_CLI:-arduino-cli}
HERE="$(cd "$(dirname "$0")" && pwd)"
WORK="$(mktemp -d)"; mkdir -p "$WORK/kraaltrac_pro"
cp "$HERE/kraaltrac_pro.ino" "$WORK/kraaltrac_pro/"
"$CLI" compile --fqbn esp32:esp32:esp32:PartitionScheme=min_spiffs --export-binaries "$WORK/kraaltrac_pro"
B="$(ls -d "$WORK"/kraaltrac_pro/build/*)"
OUT="$HERE/../../public/firmware/kraaltrac-pro"
cp "$B/kraaltrac_pro.ino.bootloader.bin" "$OUT/bootloader.bin"
cp "$B/kraaltrac_pro.ino.partitions.bin" "$OUT/partitions.bin"
cp "$B/boot_app0.bin" "$OUT/boot_app0.bin"
cp "$B/kraaltrac_pro.ino.bin" "$OUT/firmware.bin"
echo "Published to $OUT"
