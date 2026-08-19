export function parseArgs(argv) {
    const args = { file: null, dryRun: false, skipImages: false, sku: null };

    for (let i = 0; i < argv.length; i++) {
        const arg = argv[i];

        switch (arg) {
            case '--file':
                args.file = argv[++i];
                break;
            case '--dry-run':
                args.dryRun = true;
                break;
            case '--skip-images':
                args.skipImages = true;
                break;
            case '--sku':
                args.sku = argv[++i];
                break;
            case '--help':
            case '-h':
                args.help = true;
                break;
            default:
                throw new Error(`Unknown argument: ${arg}`);
        }
    }

    return args;
}

export const HELP_TEXT = `Farmtech sourcing & compliance vetting pipeline

Usage:
  node src/pipeline.js --file <path-to-listings.json> [options]

Input:
  --file <path>     Path to a JSON file: either a single sourced-listing
                     object, or an array of them (see mock_data.json for the
                     schema and 5 worked examples).

Options:
  --sku <sku>       Only process the listing with this SKU from the file.
  --dry-run         Skip DB reads/writes and image downloads; use config
                     defaults for settings/forex and print the vetting
                     result to stdout instead of inserting into MySQL.
  --skip-images     Run the real pipeline but skip image download/convert
                     (still writes to the DB).
  --help            Show this help.

Requires GEMINI_API_KEY. USD_ZAR_API_KEY and a running MySQL are required
unless --dry-run is set (forex/settings fall back to config, DB writes are
skipped and the result is printed instead).`;
