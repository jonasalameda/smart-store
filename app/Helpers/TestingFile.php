<?php
//** test_reports.php — run from browser or CLI: php test_reports.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/path/to/DocGenHelper.php'; //* adjust path

use App\Helpers\DocGenHelper;
use App\Helpers\Core\PDOService;
use App\Domain\Models\BaseModel;
use Throwable;

// Use BaseModel to access PDO through PDOService
try {
    $pdoService = new PDOService();
    $baseModel = new BaseModel($pdoService);
    $pdo = $pdoService->getPDO(); //$baseModel->pdo ?? 
} catch (Throwable $e) {
    echo "[FAIL] Could not initialize database connection: " . $e->getMessage() . "\n";
    exit(1);
}

$helper  = new DocGenHelper();
$outDir  = __DIR__ . '/test_pdfs/';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

//** ── Helper to save and report ─────────────────────────────────
function saveReport(string $filename, string $bytes, string $outDir): void {
    $path = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($path, $bytes);
    $kb = round(strlen($bytes) / 1024, 1);
    echo "[OK] {$filename} ({$kb} KB) → {$path}\n";
}

echo "Running report tests...\n\n";

//* ── ADMIN: Inventory Report ───────────────────────────────────
try {
    $bytes = $helper->generateInventoryReport([
        'low_stock_threshold' => 30,
        'category_id'         => null,
        'stream'              => false,
    ], $pdo);
    saveReport('inventory_report.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Inventory Report: " . $e->getMessage() . "\n";
}

//* ── ADMIN: Sales Report ───────────────────────────────────────
try {
    $bytes = $helper->generateSalesReport([
        'date_from'   => '2026-03-01',
        'date_to'     => '2026-03-31',
        'category_id' => null,
        'top_n'       => 3,
        'stream'      => false,
    ], $pdo);
    saveReport('sales_report.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Sales Report: " . $e->getMessage() . "\n";
}

//* ── ADMIN: Customer Activity Report ──────────────────────────
try {
    $bytes = $helper->generateCustomerActivityReport([
        'date_from' => '2026-03-01',
        'date_to'   => '2026-03-31',
        'stream'    => false,
    ], $pdo);
    saveReport('customer_activity_report.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Customer Activity Report: " . $e->getMessage() . "\n";
}

//* ── CUSTOMER: Receipt History ─────────────────────────────────
try {
    $bytes = $helper->generateReceiptHistoryReport([
        'customer_id' => 1,
        'date_from'   => '2026-03-01',
        'date_to'     => '2026-03-31',
        'stream'      => false,
    ], $pdo);
    saveReport('receipt_history_customer1.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Receipt History: " . $e->getMessage() . "\n";
}

//* ── CUSTOMER: Spending Summary ────────────────────────────────
try {
    $bytes = $helper->generatePurchaseSummaryReport([
        'customer_id' => 1,
        'date_from'   => '2026-01-01',
        'date_to'     => '2026-03-31',
        'stream'      => false,
    ], $pdo);
    saveReport('spending_summary_customer1.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Spending Summary: " . $e->getMessage() . "\n";
}

//* ── CUSTOMER: Item Search ─────────────────────────────────────
try {
    $bytes = $helper->generateItemSearchReport([
        'customer_id' => 1,
        'search_term' => 'Milk',
        'date_from'   => null,
        'date_to'     => null,
        'stream'      => false,
    ], $pdo);
    saveReport('item_search_milk_customer1.pdf', $bytes, $outDir);
} catch (Throwable $e) {
    echo "[FAIL] Item Search: " . $e->getMessage() . "\n";
}

echo "\nDone. Open the files in " . $outDir . " to review.\n";
?>
