<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\ProductsModel;
use App\Domain\Models\PurchaseModel;
use App\Helpers\FlashHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReportController extends BaseController
{
    public function __construct(
        Container $container,
        private ProductsModel $products_model,
        private PurchaseModel $purchase_model,
    ) {
        parent::__construct($container);
    }

    public function adminReports(Request $request, Response $response, array $args): Response
    {
        $q = $request->getQueryParams();
        $from = trim((string) ($q['from'] ?? ''));
        $to = trim((string) ($q['to'] ?? ''));
        $categoryId = (int) ($q['category_id'] ?? 0);

        $inventoryRows = $this->products_model->getProductsWithStockSummary();
        $thresholdMap = $this->products_model->getCategoryThresholdMap(5);
        $inventoryRows = $this->decorateInventoryRows($inventoryRows, $thresholdMap);
        $categoryRows = $this->products_model->getAllCategories();
        $salesByProduct = $this->purchase_model->getSalesByProductReport(
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            $categoryId > 0 ? $categoryId : null
        );
        $totalSales = $this->purchase_model->getSalesTotalForRange(
            $from !== '' ? $from : null,
            $to !== '' ? $to : null
        );
        $trend = $this->purchase_model->getSalesTrendByDay(
            $from !== '' ? $from : null,
            $to !== '' ? $to : null
        );
        $customerActivity = $this->purchase_model->getCustomerActivitySummary(
            $from !== '' ? $from : null,
            $to !== '' ? $to : null
        );

        $topSold = $salesByProduct;
        usort($topSold, static fn (array $a, array $b): int => ((int) $b['sold_qty']) <=> ((int) $a['sold_qty']));
        $mostSold = array_slice(array_filter($topSold, static fn (array $r): bool => (int) $r['sold_qty'] > 0), 0, 3);

        $leastSold = $salesByProduct;
        usort($leastSold, static fn (array $a, array $b): int => ((int) $a['sold_qty']) <=> ((int) $b['sold_qty']));
        $leastSold = array_slice($leastSold, 0, 3);

        return $this->render($response, 'admin/reports.php', [
            'data' => [
                'pageTitle' => 'Phase 4 Reports',
                'current_page' => 'reports',
                'from' => $from,
                'to' => $to,
                'category_id' => $categoryId,
                'categories' => $categoryRows,
                'low_stock_threshold' => 5,
                'inventory_rows' => $inventoryRows,
                'sales_by_product' => $salesByProduct,
                'most_sold' => $mostSold,
                'least_sold' => $leastSold,
                'total_sales' => $totalSales,
                'sales_trend' => $trend,
                'customer_activity' => $customerActivity,
            ],
        ]);
    }

    public function saveThresholds(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $posted = $body['category_thresholds'] ?? [];
        $thresholds = [];
        if (is_array($posted)) {
            foreach ($posted as $categoryId => $value) {
                $n = filter_var($value, FILTER_VALIDATE_INT);
                if ($n === false) {
                    continue;
                }
                $thresholds[(int) $categoryId] = max(1, (int) $n);
            }
        }

        if ($thresholds === []) {
            FlashHelper::set('error', 'No valid thresholds provided.');
            return $this->redirect($request, $response, 'admin.reports');
        }

        try {
            $this->products_model->updateCategoryThresholds($thresholds);
            FlashHelper::set('success', 'Category thresholds updated.');
        } catch (\Throwable) {
            FlashHelper::set('error', 'Failed to update category thresholds.');
        }

        return $this->redirect($request, $response, 'admin.reports');
    }

    public function inventoryLive(Request $request, Response $response, array $args): Response
    {
        $rows = $this->products_model->getProductsWithStockSummary();
        $thresholdMap = $this->products_model->getCategoryThresholdMap(5);
        $rows = $this->decorateInventoryRows($rows, $thresholdMap);
        $payload = ['rows' => $rows, 'generated_at' => date('Y-m-d H:i:s')];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function exportCsv(Request $request, Response $response, array $args): Response
    {
        $q = $request->getQueryParams();
        $from = trim((string) ($q['from'] ?? ''));
        $to = trim((string) ($q['to'] ?? ''));
        $categoryId = (int) ($q['category_id'] ?? 0);
        $lowStockThreshold = max(1, (int) ($q['low_stock_threshold'] ?? 5));
        $section = trim((string) ($q['section'] ?? 'all'));

        $lines = [];
        if ($section === 'inventory' || $section === 'all') {
            $inventoryRows = $this->products_model->getProductsWithStockSummary();
            $thresholdMap = $this->products_model->getCategoryThresholdMap(5);
            $lines[] = 'Inventory Report';
            $lines[] = 'Product,Product ID,Available Quantity,Last Restocked,Threshold,Status';
            foreach ($inventoryRows as $row) {
                $qty = (int) ($row['stock_qty'] ?? 0);
                $categoryIdRow = (int) ($row['category_id'] ?? 0);
                $threshold = $thresholdMap[$categoryIdRow] ?? $lowStockThreshold;
                $status = $this->statusByThreshold($qty, $threshold);
                $lines[] = implode(',', [
                    $this->csv((string) ($row['name'] ?? '')),
                    (string) ((int) ($row['id'] ?? 0)),
                    (string) $qty,
                    $this->csv($this->excelTextDate((string) ($row['last_received_at'] ?? ''))),
                    (string) $threshold,
                    $this->csv($status),
                ]);
            }
            $lines[] = '';
        }

        if ($section === 'sales' || $section === 'all') {
            $salesByProduct = $this->purchase_model->getSalesByProductReport(
                $from !== '' ? $from : null,
                $to !== '' ? $to : null,
                $categoryId > 0 ? $categoryId : null
            );
            $totalSales = $this->purchase_model->getSalesTotalForRange(
                $from !== '' ? $from : null,
                $to !== '' ? $to : null
            );
            $lines[] = 'Sales Report';
            $lines[] = 'Product,Sold Qty,Sales Value';
            foreach ($salesByProduct as $row) {
                $lines[] = implode(',', [
                    $this->csv((string) ($row['product_name'] ?? '')),
                    (string) ((int) ($row['sold_qty'] ?? 0)),
                    number_format((float) ($row['sales_value'] ?? 0), 2, '.', ''),
                ]);
            }
            $lines[] = 'Total Sales,' . number_format($totalSales, 2, '.', '');
            $lines[] = '';
        }

        if ($section === 'activity' || $section === 'all') {
            $activity = $this->purchase_model->getCustomerActivitySummary(
                $from !== '' ? $from : null,
                $to !== '' ? $to : null
            );
            $lines[] = 'Customer Activity Report';
            $lines[] = 'Total Customers,New Customers,Returning Customers';
            $lines[] = implode(',', [
                (string) ((int) ($activity['total_customers'] ?? 0)),
                (string) ((int) ($activity['new_customers'] ?? 0)),
                (string) ((int) ($activity['returning_customers'] ?? 0)),
            ]);
            $lines[] = '';
        }

        if ($lines === []) {
            $lines[] = 'No report data selected.';
        }

        $content = implode("\r\n", $lines) . "\r\n";
        $filename = 'smart-store-reports-' . date('Ymd-His') . '.csv';
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function csv(string $value): string
    {
        $escaped = str_replace('"', '""', $value);
        return '"' . $escaped . '"';
    }

    private function excelTextDate(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 'N/A';
        }

        // Prefix with apostrophe so Excel treats it as text (prevents ####### display in narrow columns).
        return "'" . $trimmed;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array<int,int> $thresholdMap
     * @return list<array<string,mixed>>
     */
    private function decorateInventoryRows(array $rows, array $thresholdMap): array
    {
        foreach ($rows as &$row) {
            $qty = (int) ($row['stock_qty'] ?? 0);
            $catId = (int) ($row['category_id'] ?? 0);
            $threshold = $thresholdMap[$catId] ?? 5;
            $status = $this->statusByThreshold($qty, $threshold);
            $row['threshold'] = $threshold;
            $row['status'] = $status;
        }
        unset($row);

        return $rows;
    }

    private function statusByThreshold(int $qty, int $threshold): string
    {
        return $qty <= $threshold ? 'Low' : ($qty <= ($threshold * 2) ? 'Moderate' : 'OK');
    }
}
