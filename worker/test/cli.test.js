import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseArgs } from '../src/lib/cli.js';

test('parses --file and defaults the rest to off', () => {
    const args = parseArgs(['--file', 'mock_data.json']);
    assert.equal(args.file, 'mock_data.json');
    assert.equal(args.dryRun, false);
    assert.equal(args.skipImages, false);
    assert.equal(args.sku, null);
});

test('parses --dry-run, --skip-images, and --sku together', () => {
    const args = parseArgs(['--file', 'mock_data.json', '--dry-run', '--skip-images', '--sku', 'FT-RFID-STICK-134K']);
    assert.equal(args.dryRun, true);
    assert.equal(args.skipImages, true);
    assert.equal(args.sku, 'FT-RFID-STICK-134K');
});

test('rejects an unknown flag', () => {
    assert.throws(() => parseArgs(['--bogus']), /Unknown argument/);
});
