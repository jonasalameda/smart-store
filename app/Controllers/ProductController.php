<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use App\Domain\Models\ProductsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Phase 3 UI preview — catalog, forms, and inventory screens use static sample data (no persistence).
 */
class ProductController extends BaseController
{
    //TODO change the placeholder
    public const PLACEHOLDER_RFID = '3004295B2CB20E1D00000000';
    public function __construct(Container $container, private ProductsModel $products_model)
    {
        parent::__construct($container);
    }

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
                'title' => 'RFID product lookup',
                'rfid' => $rfid,
                'products' => $products,
                'used_placeholder' => $used_placeholder,
            ],
        ]);
    }

    public function readRfid(Request $request, Response $response, array $args): Response
    {
        $scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/OneTimeReader_ChafonUHF.py';
        $output = shell_exec('python3 ' . escapeshellarg($scriptPath) . ' 2>&1');
        $epc = trim($output);
        $response->getBody()->write(json_encode(['epc' => $epc]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $query = $request->getQueryParams();
        $success = match ($query['msg'] ?? '') {
            'created' => 'Product saved (UI preview — not stored).',
            'updated' => 'Product updated (UI preview — not stored).',
            'deleted' => 'Product removed (UI preview — not stored).',
            default => null,
        };
        $products = $this->products_model->getAllProducts();
        return $this->render($response, 'products/index.php', [
            'data' => [
                'pageTitle' => 'Products',
                'current_section' => 'products',
                // 'products' => self::mockProducts(),
                'products' => $products,
                'error' => null,
                'success' => $success,
            ],
        ]);
    }

    public function inventory(Request $request, Response $response, array $args): Response
    {
        $query = $request->getQueryParams();
        $success = ($query['msg'] ?? '') === 'received'
            ? 'Receipt recorded (UI preview — stock totals below are static sample data).'
            : null;

        return $this->render($response, 'inventory/index.php', [
            'data' => [
                'pageTitle' => 'Inventory',
                'current_section' => 'inventory',
                'products' => self::mockProducts(),
                'error' => null,
                'success' => $success,
            ],
        ]);
    }

    public function createForm(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'products/form.php', [
            'data' => [
                'pageTitle' => 'Add product',
                'current_section' => 'products',
                'product' => null,
                'error' => null,
            ],
        ]);
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $row = $this->sanitizeProductInput($body);

        if ($row === null) {
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => 'Add product',
                    'current_section' => 'products',
                    'product' => $body,
                    'error' => 'Please fill in name and valid price.',
                ],
            ]);
        }
        try {
                $this->products_model->addProduct($row);
                return $this->redirect($request, $response, 'products.index', [], ['msg' => 'created']);
            } catch (\Exception $e) {
                return $this->render($response, 'products/form.php', [
                    'data' => [
                        'pageTitle' => 'Add product',
                        'current_section' => 'products',
                        'product' => $body,
                        'error' => 'Failed to save product. Please try again.',
                    ],
                ]);
            }
        // return $this->redirect($request, $response, 'products.index', [], ['msg' => 'created']);
    }

    public function editForm(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $product = self::findMockProduct($id);
        if ($product === null) {
            return $this->redirect($request, $response, 'products.index');
        }

        return $this->render($response, 'products/form.php', [
            'data' => [
                'pageTitle' => 'Edit product',
                'current_section' => 'products',
                'product' => $product,
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
        $row = $this->sanitizeProductInput($body);

        if ($row === null) {
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => 'Edit product',
                    'current_section' => 'products',
                    'product' => array_merge(['id' => $id], $body),
                    'error' => 'Please fill in name and valid price.',
                ],
            ]);
        }
        try {
            $this->products_model->updateProduct($id, $row);
            return $this->redirect($request, $response, 'products.index', [], ['msg' => 'updated']);
        } catch (\PDOException $e) {
            return $this->render($response, 'products/form.php', [
                'data' => [
                    'pageTitle' => 'Edit product',
                    'current_section' => 'products',
                    'product' => array_merge(['id' => $id], $body),
                    'error' => 'Failed to update product. Please try again.',
                ],
            ]);
        }
        // return $this->redirect($request, $response, 'products.index', [], ['msg' => 'updated']);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        // return $this->redirect($request, $response, 'products.index', [], ['msg' => 'deleted']);
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect($request, $response, 'products.index');
        }

        try {
            $this->products_model->deleteProduct($id);
            return $this->redirect($request, $response, 'products.index', [], ['msg' => 'deleted']);
        } catch (\PDOException $e) {
            return $this->render($response, 'products/index.php', [
                'data' => [
                    'pageTitle' => 'Products',
                    'current_section' => 'products',
                    'products' => [], // fetch fresh list
                    'error' => 'Failed to delete product.',
                    'success' => null,
                ],
            ]);
        }
    }

    public function receive(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody() ?? [];
        $productId = (int) ($body['product_id'] ?? 0);
        $qty = (int) ($body['quantity_received'] ?? 0);
        $date = trim((string) ($body['received_at'] ?? ''));

        if ($productId <= 0 || $qty <= 0 || $date === '') {
            return $this->render($response, 'inventory/index.php', [
                'data' => [
                    'pageTitle' => 'Inventory',
                    'current_section' => 'inventory',
                    'products' => self::mockProducts(),
                    'error' => 'Choose a product, quantity, and date.',
                    'success' => null,
                ],
            ]);
        }

        return $this->redirect($request, $response, 'inventory.index', [], ['msg' => 'received']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mockProducts(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Organic Oat Milk 1L',
                'category' => 'Dairy alternatives',
                'price' => 3.49,
                'upc' => '0852398472619',
                'epc' => '3034257F833D680000000001',
                'producer' => 'Valley Farms',
                'stock_qty' => 48,
            ],
            [
                'id' => 2,
                'name' => 'Dark Chocolate Bar 100g',
                'category' => 'Snacks',
                'price' => 2.99,
                'upc' => '0097741458230',
                'epc' => '3034257F833D680000000002',
                'producer' => 'Cocoa & Co.',
                'stock_qty' => 120,
            ],
            [
                'id' => 3,
                'name' => 'Sparkling Water 500ml',
                'category' => 'Beverages',
                'price' => 1.29,
                'upc' => '0610808135012',
                'epc' => '3034257F833D680000000003',
                'producer' => 'ClearSpring',
                'stock_qty' => 200,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findMockProduct(int $id): ?array
    {
        foreach (self::mockProducts() as $p) {
            if ((int) $p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }

    /**
     * @return array{name: string, category: string, price: float, upc: string, epc: string, producer: string}|null
     */
    private function sanitizeProductInput(array $body): ?array
    {
        $name = trim((string) ($body['name'] ?? ''));
        $price = filter_var($body['price'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($name === '' || $price === false) {
            return null;
        }

        return [
            'name' => $name,
            'category' => trim((string) ($body['category'] ?? '')),
            'price' => round((float) $price, 2),
            'upc' => substr(trim((string) ($body['upc'] ?? '')), 0, 13),
            'epc' => substr(trim((string) ($body['epc'] ?? '')), 0, 24),
            'producer' => trim((string) ($body['producer'] ?? '')),
        ];
    }
    //     if ($upc !== '' && (!preg_match('/^\d{12,13}$/', $upc))) {
    //     return null;
    // }
    // if ($epc !== '' && (!preg_match('/^[A-F0-9]{24}$/i', $epc))) {
    //     return null;
    // }
}
