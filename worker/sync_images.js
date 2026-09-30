import { readFile } from 'node:fs/promises';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config({ path: new URL('../.env', import.meta.url).pathname });

const raw = JSON.parse(await readFile('./scraped_data.json', 'utf-8'));
const items = Array.isArray(raw) ? raw : (raw.items || raw.data || []);

const db = await mysql.createConnection({
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USERNAME,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_DATABASE,
});

for (let i = 0; i < items.length; i++) {
  const item = items[i];
  const title = item.product?.title || item.title || item.subject;
  const rawImages = item.product?.images || item.images || [];
  const imageUrls = rawImages.map(img => typeof img === 'string' ? img : img.url).filter(Boolean);

  if (!title || imageUrls.length === 0) continue;

  // Find product by title match or ID
  const [products] = await db.query(
    'SELECT id FROM products WHERE title LIKE ? LIMIT 1',
    [`%${title.slice(0, 30)}%`]
  );

  if (products.length > 0) {
    const productId = products[0].id;
    
    // Clear temporary placeholders
    await db.query('DELETE FROM product_images WHERE product_id = ?', [productId]);

    // Insert real supplier images
    for (let sortOrder = 0; sortOrder < imageUrls.length; sortOrder++) {
      await db.query(
        'INSERT INTO product_images (product_id, url, local_path, is_primary, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
        [productId, imageUrls[sortOrder], '', sortOrder === 0 ? 1 : 0, sortOrder]
      );
    }
    console.log(`Synced ${imageUrls.length} image(s) for Product ID ${productId}`);
  }
}

await db.end();
console.log('Finished syncing real images.');
