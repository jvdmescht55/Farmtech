import * as mysqlDriver from './db.mysql.js';
import * as sqliteDriver from './db.sqlite.js';

/**
 * WORKER_DB_DRIVER selects the backend: 'mysql' (default, matches
 * docker-compose.yml/production) or 'sqlite' (for environments without a
 * MySQL/MariaDB server — points at the same database.sqlite file the
 * Laravel app itself reads, via WORKER_SQLITE_PATH).
 */
const driver = process.env.WORKER_DB_DRIVER === 'sqlite' ? sqliteDriver : mysqlDriver;

export const closeConnection = driver.closeConnection;
export const readFallbackRate = driver.readFallbackRate;
export const writeRate = driver.writeRate;
export const isSupplierBlacklisted = driver.isSupplierBlacklisted;
export const getSetting = driver.getSetting;
export const insertVettedProduct = driver.insertVettedProduct;
