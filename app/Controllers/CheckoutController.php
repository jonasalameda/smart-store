<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\PurchaseModel;
use App\Domain\Models\ProductsModel;
use App\Domain\Models\CustomerModel;
use App\Helpers\EmailHelper;
use App\Helpers\FlashHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckoutController extends BaseController
{
    private const CUSTOMER_SESSION_KEY = 'customer_account';

    public function __construct(
        Container $container,
        private PurchaseModel $purchase_model,
        private ProductsModel $products_model,
        private CustomerModel $customer_model,
        private EmailHelper $email_helper
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $products = $this->products_model->getProductsWithStockSummary();
        $catalog = [];
        foreach ($products as $p) {
            $catalog[] = [
                'id' => (int) $p['id'],
                'name' => (string) $p['name'],
                'price' => (float) $p['price'],
                'upc' => (string) ($p['upc'] ?? ''),
                'epc' => (string) ($p['epc'] ?? ''),
                'stock_qty' => (int) ($p['stock_qty'] ?? 0),
            ];
        }
        $productsJson = json_encode($catalog, JSON_THROW_ON_ERROR);

        $sessionCustomer = $_SESSION[self::CUSTOMER_SESSION_KEY] ?? null;
        $customerId = is_array($sessionCustomer) && !empty($sessionCustomer['id'])
            ? (int) $sessionCustomer['id']
            : null;

        $data['data'] = [
            'title' => __('checkout.title'),
            'products' => $products,
            'products_json' => $productsJson,
            'customer_id' => $customerId,
        ];

        return $this->render($response, 'checkoutView.php', $data);
    }

    public function process(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = $request->getParsedBody() ?? [];
        $items = $body['items'] ?? [];
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }
        $customer_id = !empty($body['customer_id']) ? (int) $body['customer_id'] : null;
        if ($customer_id === null && !empty($body['membership_number'])) {
            $memberRow = $this->customer_model->getCustomerByMembership((string) $body['membership_number']);
            if ($memberRow !== false) {
                $customer_id = (int) $memberRow['id'];
            }
        }
        $guest_receipt_email = trim((string) ($body['guest_receipt_email'] ?? ''));
        $payment_method = $body['payment_method'] ?? 'cash';

        $products = $this->products_model->getProductsWithStockSummary();
        $catalog = [];
        foreach ($products as $p) {
            $catalog[] = [
                'id' => (int) $p['id'],
                'name' => (string) $p['name'],
                'price' => (float) $p['price'],
                'upc' => (string) ($p['upc'] ?? ''),
                'epc' => (string) ($p['epc'] ?? ''),
                'stock_qty' => (int) ($p['stock_qty'] ?? 0),
            ];
        }
        $productsJson = json_encode($catalog, JSON_THROW_ON_ERROR);
        $sessionCustomer = $_SESSION[self::CUSTOMER_SESSION_KEY] ?? null;
        $viewCustomerId = is_array($sessionCustomer) && !empty($sessionCustomer['id'])
            ? (int) $sessionCustomer['id']
            : null;

        if ($items === []) {
            $data['data'] = [
                'title' => __('checkout.title'),
                'error' => __('checkout.error_empty'),
                'products' => $products,
                'products_json' => $productsJson,
                'customer_id' => $viewCustomerId,
            ];

            return $this->render($response, 'checkoutView.php', $data);
        }

        if ($customer_id === null && $guest_receipt_email !== ''
            && !filter_var($guest_receipt_email, FILTER_VALIDATE_EMAIL)) {
            $data['data'] = [
                'title' => __('checkout.title'),
                'error' => __('checkout.error_guest_email'),
                'products' => $products,
                'products_json' => $productsJson,
                'customer_id' => $viewCustomerId,
            ];

            return $this->render($response, 'checkoutView.php', $data);
        }

        $total = 0;
        $purchase_items = [];

        foreach ($items as $item) {
            $product = $this->products_model->getOneProduct((int)$item['product_id']);
            if ($product === false) {
                continue;
            }

            $qtt = (int) ($item['quantity'] ?? 1);
            $stockRows = $this->products_model->getStockByProduct($product['id']);
            $current_stock = !empty($stockRows) ? (int) $stockRows[0]['current_stock'] : 0;
            if ($qtt > $current_stock) {
                $data['data'] = [
                    'title' => __('checkout.title'),
                    'error' => sprintf(__('checkout.error_stock_limit'), $product['name'], $current_stock),
                    'products' => $products,
                    'products_json' => $productsJson,
                    'customer_id' => $viewCustomerId,
                ];

                return $this->render($response, 'checkoutView.php', $data);
            }

            $subtotal = round($product['price'] * $qtt, 2);

            $total += $subtotal;

            $purchase_items[] = [
                'product_id' => $product['id'],
                'product_name' => (string) ($product['name'] ?? ''),
                'quantity'   => $qtt,
                'unit_price' => $product['price'],
                'subtotal'   => $subtotal,
            ];

            //* Update stock
            $stock = $this->products_model->getStockByProduct($product['id']);
            if (!empty($stock)) {
                // I made the model fetch from order of the most recent stock, so check index 0
                $new_stock = (int)$stock[0]['current_stock'] - $qtt;
                $this->products_model->updateStock($product['id'], $new_stock);
            }
        }

        //TODO: Is it good to do 1 point per dollar spent
        $points_earned = $customer_id ? (int)floor($total) : 0;

        $purchase_id = $this->purchase_model->createPurchase([
            'customer_id'    => $customer_id,
            'total_amount'   => round($total, 2),
            'points_earned'  => $points_earned,
            'purchase_date'  => date('Y-m-d H:i:s'),
            'payment_method' => $payment_method,
            'receipt_sent'   => false,
        ]);

        foreach ($purchase_items as $item) {
            $row = [
                'purchase_id' => $purchase_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
            ];
            $this->purchase_model->addPurchaseItem($row);
        }

        //* Update customer points
        if ($customer_id && $points_earned > 0) {
            $this->customer_model->addPoints($customer_id, $points_earned);
        }

        $receipt_sent = false;
        if ($customer_id) {
            $customer = $this->customer_model->getOneCustomer($customer_id);
            $memberEmail = isset($customer['email']) ? trim((string) $customer['email']) : '';
            if ($memberEmail !== '' && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                $recipientName = trim((string) ($customer['name'] ?? ''));
                if ($recipientName === '') {
                    $recipientName = __('checkout.receipt_recipient_guest');
                }
                $this->sendReceiptEmail(
                    $memberEmail,
                    $recipientName,
                    (int) $purchase_id,
                    $purchase_items,
                    round($total, 2),
                    $points_earned
                );
                $receipt_sent = true;
            }
        } elseif ($guest_receipt_email !== '' && filter_var($guest_receipt_email, FILTER_VALIDATE_EMAIL)) {
            $this->sendReceiptEmail(
                mb_strtolower($guest_receipt_email),
                __('checkout.receipt_recipient_guest'),
                (int) $purchase_id,
                $purchase_items,
                round($total, 2),
                $points_earned
            );
            $receipt_sent = true;
        }
        if ($receipt_sent) {
            $this->purchase_model->markReceiptSent($purchase_id);
        }

        FlashHelper::set('success', __('checkout.success'));

        $data['data'] = [
            'title' => __('checkout.title'),
            'success' => __('checkout.success'),
            'purchase_id' => (int) $purchase_id,
            'total' => round($total, 2),
            'points' => $points_earned,
            'products' => $products,
            'products_json' => $productsJson,
            'customer_id' => $viewCustomerId,
        ];

        return $this->render($response, 'checkoutView.php', $data);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $purchase = $this->purchase_model->getOnePurchase($id);
        $items    = $this->purchase_model->getPurchaseItems($id);

        $data['data'] = [
            'title'    => sprintf(__('staff.purchase_detail'), $id),
            'purchase' => $purchase,
            'items'    => $items,
        ];

        return $this->render($response, 'purchaseDetailView.php', $data); //TODO update to the correct view page by Russel later
    }

    public function history(Request $request, Response $response, array $args): Response
    {
        $customer_id = (int)$args['customer_id'];
        $purchases   = $this->purchase_model->getPurchasesByCustomer($customer_id);
        $customer    = $this->customer_model->getOneCustomer($customer_id);

        $data['data'] = [
            'title'     => __('staff.purchase_history'),
            'customer'  => $customer,
            'purchases' => $purchases,
        ];

        return $this->render($response, 'purchaseHistoryView.php', $data);
    }

    public function receipt(Request $request, Response $response, array $args): Response
    {
        $purchase_id = (int)$args['purchase_id'];
        $receipt     = $this->purchase_model->getReceipt($purchase_id);

        $data['data'] = [
            'title'   => __('receipt_page.title') . ' #' . $purchase_id,
            'receipt' => $receipt,
        ];

        return $this->render($response, 'receiptView.php', $data);
    }

    public function sendReceipt(Request $request, Response $response, array $args): Response
    {
        $purchase_id = (int)$args['purchase_id'];
        $receipt     = $this->purchase_model->getReceipt($purchase_id);

        if (empty($receipt)) {
            $data['data'] = [
                'title' => __('receipt_page.title'),
                'error' => __('receipts.error_not_found'),
            ];
            return $this->render($response, 'receiptView.php', $data);
        }

        $customer_email = $receipt[0]['customer_email'] ?? null;

        $customer_name  = $receipt[0]['customer_name'] ?? __('checkout.receipt_recipient_guest');

        if (!$customer_email) {
            $data['data'] = [
                'title' => __('receipt_page.title'),
                'error' => __('receipts.error_no_email'),
                'receipt' => $receipt,
            ];
            return $this->render($response, 'receiptView.php', $data);
        }

        $this->purchase_model->markReceiptSent($purchase_id);

        $this->email_helper->sendEmail(
            $customer_email,
            str_replace('{id}', (string) $purchase_id, __('receipts.email_subject')),
            $this->formatReceiptEmail($customer_name, $receipt)
        );

        FlashHelper::set('success', __('flash.receipt_sent'));

        $data['data'] = [
            'title' => __('receipt_page.title') . ' #' . $purchase_id,
            'receipt' => $receipt,
            'success' => __('flash.receipt_sent'),
        ];

        return $this->render($response, 'receiptView.php', $data);
    }

    /**
     * @param list<array{product_id: int, product_name?: string, quantity: int, unit_price: float|int, subtotal: float}> $items
     */
    private function sendReceiptEmail(
        string $toEmail,
        string $recipientName,
        int $purchase_id,
        array $items,
        float $total,
        int $points,
    ): void {
        $lines = array_map(function (array $i): string {
            $label = trim((string) ($i['product_name'] ?? ''));
            if ($label === '') {
                $label = sprintf(__('receipt_email.product_unknown'), (int) ($i['product_id'] ?? 0));
            }

            return sprintf(
                '- %s x%d @ $%.2f = $%.2f',
                $label,
                (int) $i['quantity'],
                (float) $i['unit_price'],
                (float) $i['subtotal']
            );
        }, $items);

        $body = sprintf(
            __('receipt_email.body_checkout'),
            $recipientName,
            $purchase_id,
            date('Y-m-d H:i:s'),
            implode("\n", $lines),
            $total,
            $points
        );

        $this->email_helper->sendEmail(
            $toEmail,
            str_replace('{id}', (string) $purchase_id, __('receipts.email_subject')),
            $body
        );
    }

    private function formatReceiptEmail(string $customer_name, array $receipt): string
    {
        $lines = array_map(fn($r) => sprintf(
            "- %s x%d @ $%.2f = $%.2f",
            $r['product_name'], $r['quantity'], $r['unit_price'], $r['subtotal']
        ), $receipt);

        return sprintf(
            __('receipt_email.body_resend'),
            $customer_name,
            implode("\n", $lines),
            (float) $receipt[0]['total_amount'],
            (int) $receipt[0]['points_earned']
        );
    }
}
