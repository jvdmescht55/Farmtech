import { readFile, writeFile } from 'node:fs/promises';

const raw = JSON.parse(await readFile('./scraped_data.json', 'utf-8'));
const rawData = Array.isArray(raw) ? raw : (raw.items || raw.data || [raw]);

if (!Array.isArray(rawData) || rawData.length === 0 || raw.error) {
  console.error('Scraped data is empty or returned an error:', raw);
  process.exit(1);
}

const normalized = rawData.map((item, index) => {
  // Extract price
  let price = 0;
  if (item.product?.price?.minPrice) {
    price = parseFloat(item.product.price.minPrice);
  } else if (item.price?.min) {
    price = parseFloat(item.price.min);
  } else if (item.price) {
    price = parseFloat(String(item.price).replace(/[^0-9.]/g, '')) || 0;
  }

  const title = item.product?.title || item.title || item.subject || `Product ${index + 1}`;
  const supplier = item.supplier?.companyName || item.companyProfile?.name || item.seller?.name || 'Verified Alibaba Supplier';
  const specs = JSON.stringify(item.detail?.specs || item.productDetail || item.specs || {});
  
  let category = 'smart_farming';
  let weight = 0.4; // Default light electronics weight (kg)

  const lower = title.toLowerCase();
  if (lower.includes('rfid') || lower.includes('tag') || lower.includes('microchip')) {
    category = 'livestock_management';
    weight = 0.25;
    if (price <= 0) price = 65.00;
  } else if (lower.includes('ultrasound') || lower.includes('scanner')) {
    category = 'livestock_management';
    weight = 1.2;
    if (price <= 0) price = 450.00;
  } else if (lower.includes('scale') || lower.includes('load cell') || lower.includes('weigh')) {
    category = 'livestock_management';
    weight = 1.8;
    if (price <= 0) price = 120.00;
  } else if (lower.includes('pump') || lower.includes('inverter')) {
    category = 'irrigation_water';
    weight = 2.5;
    if (price <= 0) price = 280.00;
  } else if (lower.includes('laser') || lower.includes('meter') || lower.includes('tester')) {
    category = 'smart_farming';
    weight = 0.6;
    if (price <= 0) price = 85.00;
  } else {
    if (price <= 0) price = 75.00;
  }

  const rawImages = item.product?.images || item.images || [];
  const images = rawImages.map(img => typeof img === 'string' ? img : img.url).filter(Boolean);

  return {
    sku: `FT-ALI-${Date.now().toString().slice(-6)}-${index + 1}`,
    raw_title: title,
    category_hint: category,
    supplier_name: supplier,
    supplier_price_usd: price,
    weight_kg: weight,
    duty_rate: 0.0,
    raw_specs_text: specs.length > 2 ? specs : title,
    images: images.length > 0 ? images : ['https://farmtech.site/images/placeholder.webp']
  };
});

await writeFile('./vetted_input.json', JSON.stringify(normalized, null, 2));
console.log(`Transformed ${normalized.length} products with adjusted weights and prices.`);
