<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\FlashHelper;
use DI\Container;
use App\Domain\Models\ProductsModel;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController extends BaseController
{
    /*
     * RFID shelf page (http://…/smart-store/rfid/products) — disabled; routes commented in web-routes.php
     */
    // public const PLACEHOLDER_RFID = '3004295B2CB20E1D00000000';

    public function __construct(Container $container, private ProductsModel $products_model)
    {
        parent::__construct($container);
    }

    /*
    public function rfidProducts(Request $request, Response $response, array $args): Response
    {
        $rfid = isset($args['rfid']) ? rawurldecode((string) $args['rfid']) : '';
        if ($rfid === '') {
            $params = $request->getQueryParams();
            $rfid = trim((string) ($params['rfid'] ?? ''));
        }

        $used_placeholder = $rfid === '';
        if ($used_placeholder) {
            $rfid = self::PLACEHOLDER_RFID;
        }

        $products = $this->products_model->findByRfid($rfid);

        return $this->render($response, 'rfidProductsView.php', [
            'data' => [
                'title' => __('nav.rfid_products'),
                'current_page' => 'rfid',
                'rfid' => $rfid,
                'products' => $products,
                'used_placeholder' => $used_placeholder,
            ],
        ]);
    }
    */

    public function apiByUpc(Request $request, Response $response, array $args): Response
    {
        $upc = trim((string) ($request->getQueryParams()['upc'] ?? ''));
        if ($upc === '') {
            $response->getBody()->write(json_encode(['product' => null]));

            return $response->withHeader('Content-Type', 'application/json');
        }
        $row = $this->products_model->getProductByUPC($upc);
        $product = $row === false ? null : [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'price' => (float) $row['price'],
            'upc' => (string) ($row['upc'] ?? ''),
            'epc' => (string) ($row['epc'] ?? ''),
        ];
        $response->getBody()->write(json_encode(['product' => $product]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function apiByEpc(Request $request, Response $response, array $args): Response
    {
        $epc = trim((string) ($request->getQueryParams()['epc'] ?? ''));
        if ($epc === '') {
            $response->getBody()->write(json_encode(['product' => null]));

            return $response->withHeader('Content-Type', 'application/json');
        }
        $row = $this->products_model->getProductByEPC($epc);
        $product = $row === false ? null : [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'price' => (float) $row['price'],
            'upc' => (string) ($row['upc'] ?? ''),
            'epc' => (string) ($row['epc'] ?? ''),
        ];
        $response->getBody()->write(json_encode(['product' => $product]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function readRfid(Request $request, Response $response, array $args): Response
    {
        $scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/OneTimeReader_ChafonUHF.py';
        $output = shell_exec('python3 ' . escapeshellarg($scriptPath) . ' 2>&1');
        $rawOutput = trim((string) $output);
        $epc = $rawOutput !== '' ? $rawOutput : '';
        $response->getBody()->write(json_encode(['epc' => $epc, 'raw' => $rawOutput]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $allProducts = $this->products_model->getProductsWithStockSummary();
        $params = $request->getQueryParams();
        $searchQ = trim((string) ($params['q'] ?? ''));
        $hasSearch = $searchQ !== '';

        $lower = static function (string $s): string {
            return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
        };

        $products = $allProducts;
        $searchNotFound = false;
        if ($hasSearch) {
            $needle = $lower($searchQ);
            $products = array_values(array_filter(
                $allProducts,
                static function (array $p) use ($needle, $lower): bool {
                    if ((int) ($p['stock_qty'] ?? 0) <= 0) {
                        return false;
                    }
                    $blob = $lower(
                        (string) ($p['name'] ?? '')
                        . ' ' . (string) ($p['upc'] ?? '')
                        . ' ' . (string) ($p['epc'] ?? '')
                        . ' ' . (string) ($p['category'] ?? '')
                        . ' ' . (string) ($p['manufacturer'] ?? '')
                        . ' ' . (string) ($p['producer'] ?? '')
                    );

                    return str_contains($blob, $needle);
                }
            ));
            $searchNotFound = $products === [];
        }

        $threshold = (int) ($this->settings->get('inventory')['low_stock_threshold'] ?? 15);
        $lowStock = 0;
        foreach ($products as $p) {
            $q = (int) ($p['stock_qty'] ?? 0);
            if ($q > 0 && $q <= $threshold) {
                $lowStock++;
            }
        }
        $lastImport = $this->products_model->getLatestStockImportDate();

        return $this->render($response, 'products/index.php', [
            'data' => [
                'pageTitle' => __('products.title'),
                'current_section' => 'products',
                'products' => $products,
                'error' => null,
                'low_stock_count' => $lowStock,
                'last_import' => $lastImport,
                'search_query' => $searchQ,
                'search_active' => $hasSearch,
                'search_not_found' => $searchNotFound,
            ],
        ]);
    }

    public function inventory(Request $request, Response $response, array $args): Response
    {
        $products = $this->products_model->getProductsWithStockSummary();

        return $this->render($response, 'inventory/index.php', [
            'data' => [
                'pageTitle' => __('inventory.title'),
                'current_section' => 'inventory',
                'products' => $products,
                'error' => null,
            ],
        ]);
    }

    /** @deprecated Legacy route alias — use {@see inventory()} */
    public function stock(Request $request, Response $response, array $args): Response
    {
        return $this->inventory($request, $response, $args);
    }

    /** @deprecated Legacy route — redirects to reception history for the product */
    public function stockByProduct(Request $request, Response $response, array $args): Response
    {
        $args['id'] = (int) ($args['product_id'] ?? 0);

        return $this->receptionHistory($request, $response, $args);
    }

    /** @deprecated Legacy route alias — use {@see receive()} */
    public function receiveStock(Request $request, Response $response, array $args): Response
    {
        return $this->receive($request, $response, $args);
    }

    public function receptionHistory(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect($request, $response, 'products.index');
        }
        $product = $this->products_model->getOneProduct($id);
        if ($product === false) {
            return $this->redirect($request, $response, 'products.index');
        }
        $history = $this->products_model->getReceptionHistoryForProduct($id);

        return $this->render($response, 'products/history.php', [
            'data' => [
                'pageTitle' => __('history.title'),
                'current_section' => 'products',
                'current_page' => 'products',
                'product' => $product,
                'history' => $history,
            ],
        ]);
    }// ...existing code...

    public function createForm(Request $request, Response $response, array $args): Response
    {
        $categories = $this->products_model->getAllCategories();
        return $this->render($response, 'products/form.php', [
            'data' => [
                'pageTitle' => __('products.form.add_title'),
                'current_section' => 'products',
                'product' => null,
                'categories' => $categories,
                'error' => null,
            ],
        ]);
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $resolvedCategoryId = $this->resolveCategoryId($body);
        if ($resolvedCategoryId !== null) {
            $body['category_id'] = $resolvedCategoryId;
        }
        $row = $this->sanitizeProductInput($body);

        if ($row === null) {
            $categories = $this->products_model->getAllCategories();
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => __('products.form.add_title'),
                    'current_section' => 'products',
                    'product' => $body,
                    'categories' => $categories,
                    'error' => __('products.form.error_required'),
                ],
            ]);
        }
        try {
            $productId = $this->products_model->addProduct($row);
            if ($productId > 0) {
                $this->products_model->receiveStock([
                    'product_id' => $productId,
                    'quantity_received' => 1,
                    'date_received' => date('Y-m-d'),
                    'current_stock' => 1,
                ]);
            }
            FlashHelper::set('success', __('products.created'));

            return $this->redirect($request, $response, 'products.index');
        } catch (\Exception) {
            $categories = $this->products_model->getAllCategories();
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => __('products.form.add_title'),
                    'current_section' => 'products',
                    'product' => $body,
                    'categories' => $categories,
                    'error' => __('products.form.error_save'),
                ],
            ]);
        }
    }

    public function editForm(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $product = $this->products_model->getOneProduct($id);
        if ($product === false) {
            return $this->redirect($request, $response, 'products.index');
        }
        $product['producer'] = $product['manufacturer'] ?? '';
        $product['current_stock'] = $this->products_model->getCurrentStockByProduct($id);
        $categories = $this->products_model->getAllCategories();

        return $this->render($response, 'products/form.php', [
            'data' => [
                'pageTitle' => __('products.form.edit_title'),
                'current_section' => 'products',
                'product' => $product,
                'categories' => $categories,
                'error' => null,
            ],
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect($request, $response, 'products.index');
        }
        $body = $request->getParsedBody() ?? [];
        $resolvedCategoryId = $this->resolveCategoryId($body);
        if ($resolvedCategoryId !== null) {
            $body['category_id'] = $resolvedCategoryId;
        }
        $row = $this->sanitizeProductInput($body);
        $stockAdjustment = filter_var($body['stock_adjustment'] ?? 0, FILTER_VALIDATE_INT);
        $stockAdjustment = $stockAdjustment === false ? 0 : (int) $stockAdjustment;

        if ($row === null) {
            $categories = $this->products_model->getAllCategories();
            $currentStock = $this->products_model->getCurrentStockByProduct($id);
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => __('products.form.edit_title'),
                    'current_section' => 'products',
                    'product' => array_merge(['id' => $id, 'current_stock' => $currentStock], $body),
                    'categories' => $categories,
                    'error' => __('products.form.error_required'),
                ],
            ]);
        }
        try {
            $this->products_model->updateProduct($id, $row);
            if ($stockAdjustment !== 0) {
                $prevStock = $this->products_model->getCurrentStockByProduct($id);
                $newStock = max(0, $prevStock + $stockAdjustment);
                $this->products_model->receiveStock([
                    'product_id' => $id,
                    'quantity_received' => $stockAdjustment,
                    'date_received' => date('Y-m-d'),
                    'current_stock' => $newStock,
                ]);
            }
            FlashHelper::set('success', __('products.updated'));

            return $this->redirect($request, $response, 'products.index');
        } catch (PDOException) {
            $categories = $this->products_model->getAllCategories();
            $currentStock = $this->products_model->getCurrentStockByProduct($id);
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => __('products.form.edit_title'),
                    'current_section' => 'products',
                    'product' => array_merge(['id' => $id, 'current_stock' => $currentStock], $body),
                    'categories' => $categories,
                    'error' => __('products.form.error_save'),
                ],
            ]);
        }
    }

    /**
     * @return array{name: string, category_id: ?int, price: float, upc: ?string, epc: ?string, manufacturer: ?string, shelf_life_days: ?int}|null
     */
    private function sanitizeProductInput(array $body): ?array
    {
        $name = trim((string) ($body['name'] ?? ''));
        $price = filter_var($body['price'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($name === '' || $price === false) {
            return null;
        }

        $categoryId = filter_var($body['category_id'] ?? null, FILTER_VALIDATE_INT);

        $shelf = $body['shelf_life_days'] ?? null;
        $shelfInt = $shelf !== null && $shelf !== '' ? filter_var($shelf, FILTER_VALIDATE_INT) : null;

        return [
            'name' => $name,
            'category_id' => $categoryId !== false && $categoryId !== null ? (int) $categoryId : null,
            'price' => round((float) $price, 2),
            'upc' => ($u = substr(trim((string) ($body['upc'] ?? '')), 0, 13)) !== '' ? $u : null,
            'epc' => ($e = substr(trim((string) ($body['epc'] ?? '')), 0, 24)) !== '' ? $e : null,
            'manufacturer' => ($m = trim((string) ($body['producer'] ?? $body['manufacturer'] ?? ''))) !== '' ? $m : null,
            'shelf_life_days' => $shelfInt !== false && $shelfInt !== null ? (int) $shelfInt : null,
        ];
    }

    private function resolveCategoryId(array $body): ?int
    {
        $newCategory = trim((string) ($body['new_category'] ?? ''));
        if ($newCategory !== '') {
            $createdOrFound = $this->products_model->findOrCreateCategory($newCategory);
            return $createdOrFound > 0 ? $createdOrFound : null;
        }

        $existing = filter_var($body['category_id'] ?? null, FILTER_VALIDATE_INT);
        if ($existing === false || $existing === null || (int) $existing <= 0) {
            return null;
        }

        return (int) $existing;
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect($request, $response, 'products.index');
        }

        try {
            $this->products_model->deleteProduct($id);
            FlashHelper::set('success', __('products.deleted'));

            return $this->redirect($request, $response, 'products.index');
        } catch (\Throwable) {
            FlashHelper::set('error', __('products.delete_fail'));

            return $this->redirect($request, $response, 'products.index');
        }
    }

    public function receive(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $productId = (int) ($body['product_id'] ?? 0);
        $qty = (int) ($body['quantity_received'] ?? 0);
        $date = trim((string) ($body['received_at'] ?? ''));

        if ($productId <= 0 || $qty <= 0 || $date === '') {
            FlashHelper::set('error', __('inventory.error_form'));

            return $this->redirect($request, $response, 'inventory.index');
        }

        $stock = $this->products_model->getStockByProduct($productId);
        $prev = $stock === [] ? 0 : (int) $stock[0]['current_stock'];
        $newStock = $prev + $qty;

        try {
            $this->products_model->receiveStock([
                'product_id' => $productId,
                'quantity_received' => $qty,
                'date_received' => $date,
                'current_stock' => $newStock,
            ]);
            FlashHelper::set('success', __('inventory.received'));
        } catch (PDOException) {
            FlashHelper::set('error', __('products.form.error_save'));
        }

        return $this->redirect($request, $response, 'inventory.index');
    }

    public function adjustStock(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $productId = (int) ($body['product_id'] ?? 0);
        $adjustment = filter_var($body['adjustment'] ?? 0, FILTER_VALIDATE_INT);
        $adjustment = $adjustment === false ? 0 : (int) $adjustment;

        if ($productId <= 0 || $adjustment === 0) {
            FlashHelper::set('error', __('inventory.error_form'));
            return $this->redirect($request, $response, 'inventory.index');
        }

        $prevStock = $this->products_model->getCurrentStockByProduct($productId);
        $newStock = max(0, $prevStock + $adjustment);
        $appliedAdjustment = $newStock - $prevStock;

        if ($appliedAdjustment === 0) {
            FlashHelper::set('info', 'Stock is already at 0. No decrease applied.');
            return $this->redirect($request, $response, 'inventory.index');
        }

        try {
            $this->products_model->receiveStock([
                'product_id' => $productId,
                'quantity_received' => $appliedAdjustment,
                'date_received' => date('Y-m-d'),
                'current_stock' => $newStock,
            ]);
            FlashHelper::set('success', 'Inventory updated successfully.');
        } catch (PDOException) {
            FlashHelper::set('error', __('products.form.error_save'));
        }

        return $this->redirect($request, $response, 'inventory.index');
    }

    /**
     * TO read multiple items at the same time
     */
    public function streamRfid(Request $request, Response $response, array $args): Response
    {
        $scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/ContinuousReader_ChafonUHF.py';
        // These headers turn the response into an SSE stream I think
        $response = $response
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('X-Accel-Buffering', 'no');

        $body = $response->getBody();

        $proc = popen('python3 ' . escapeshellarg($scriptPath) . ' 2>&1', 'r');
        if (!$proc) {
            $body->write("data: {\"error\":\"Could not start reader\"}\n\n");
            return $response;
        }

        // Stream each line as an SSE event
        while (!feof($proc)) {
            $line = fgets($proc);
            if ($line === false) break;
            $epc = trim($line);
            if ($epc === '' || $epc === 'Inventory started') continue;
            $body->write("data: " . json_encode(['epc' => $epc]) . "\n\n");
            if (ob_get_level()) ob_flush();
            flush();
        }
        pclose($proc);

        return $response;
    }
}
