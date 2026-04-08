<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\PurchaseModel;
use App\Domain\Models\ProductsModel;
use App\Domain\Models\CustomerModel;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckoutController extends BaseController
{
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
        $products = $this->products_model->getAllProducts();

        $data['data'] = [
            'title' => 'Checkout',
            'products' => $products,
        ];

        return $this->render($response, 'checkoutView.php', $data); //TODO change the placeholder view name
    }

    public function process(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $items = $body['items'] ?? [];           // array of {product_id, quantity}
        $customer_id = !empty($body['customer_id']) ? (int)$body['customer_id'] : null; //TODO verify if 'customer_id' or 'id' is passed
        $payment_method = $body['payment_method'] ?? 'cash';

        if (empty($items)) {
            $data['data'] = [
                'title' => 'Checkout',
                'error' => 'Cart is empty, go shop!',
                'products' => $this->products_model->getAllProducts(),
            ];
            return $this->render($response, 'checkoutView.php', $data); //TODO change the placeholder view name
        }

        $total = 0;
        $purchase_items = [];

        foreach ($items as $item) {
            $product = $this->products_model->getOneProduct((int)$item['product_id']);

            $qtt      = (int)$item['quantity'] ?? 1;
            //TODO idk we either have qtt field where we get it by grouping items of the same name, or we can just ignore qtt (discuss with team)

            $subtotal = round($product['price'] * $qtt, 2);

            $total   += $subtotal;

            $purchase_items[] = [
                'product_id' => $product['id'],
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
            $item['purchase_id'] = $purchase_id;
            $this->purchase_model->addPurchaseItem($item);
        }

        //* Update customer points
        if ($customer_id && $points_earned > 0) {
            $this->customer_model->addPoints($customer_id, $points_earned);
        }

        // Send receipt if customer has email
        if ($customer_id) {
            $customer = $this->customer_model->getOneCustomer($customer_id);
            if (!empty($customer['email'])) {
                $this->sendReceiptEmail($customer, (int)$purchase_id, $purchase_items, round($total, 2), $points_earned);
                $this->purchase_model->markReceiptSent($purchase_id);
            }
        }

        //TODO: maybe we turn ON the led for phase 4 here

        $data['data'] = [
            'title'       => 'Checkout',
            'success'     => 'Checkout completed successfully!',
            'purchase_id' => $purchase_id,
            'total'       => round($total, 2),
            'points'      => $points_earned,
            'products'    => $this->products_model->getAllProducts(),
        ];

        return $this->render($response, 'checkoutView.php', $data); //TODO update to the correct view page by Russel later
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $purchase = $this->purchase_model->getOnePurchase($id);
        $items    = $this->purchase_model->getPurchaseItems($id);

        $data['data'] = [
            'title'    => 'Purchase #' . $id,
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
            'title'     => 'Purchase History',
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
            'title'   => 'Receipt #' . $purchase_id,
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
                'title' => 'Receipt',
                'error' => 'Receipt not found.',
            ];
            return $this->render($response, 'receiptView.php', $data);
        }

        $customer_email = $receipt[0]['customer_email'] ?? null;
        // $customer_email = 'mkprogrammerk80@gmail.com';
         //TODO for debugging I am using my email, but we should change that

        $customer_name  = $receipt[0]['customer_name'] ?? 'Guest';

        if (!$customer_email) {
            $data['data'] = [
                'title' => 'Receipt',
                'error' => 'No email address on file for this customer.',
                'receipt' => $receipt,
            ];
            return $this->render($response, 'receiptView.php', $data);
        }

        $this->purchase_model->markReceiptSent($purchase_id);

        $this->email_helper->sendEmail(
            $customer_email,
            "Your Smart Store Receipt #$purchase_id",
            $this->formatReceiptEmail($customer_name, $receipt)
        );


        $data['data'] = [
            'title'   => 'Receipt #' . $purchase_id,
            'receipt' => $receipt,
            'success' => 'Receipt sent to ' . $customer_email,
        ];

        return $this->render($response, 'receiptView.php', $data); //TODO change view name
    }

    private function sendReceiptEmail(array $customer, int $purchase_id, array $items, float $total, int $points): void
    {
        $lines = array_map(fn($i) => sprintf(
            "- %s x%d @ $%.2f = $%.2f",
            $i['product_id'], $i['quantity'], $i['unit_price'], $i['subtotal']
        ), $items);

        $body = sprintf(
            "Hi %s,\n\nThank you for your purchase!\n\nReceipt #%d\nDate: %s\n\n%s\n\nTotal: $%.2f\nPoints earned: %d\n\nSee you next time!",
            $customer['name'],
            $purchase_id,
            date('Y-m-d H:i:s'),
            implode("\n", $lines),
            $total,
            $points
        );

        $this->email_helper->sendEmail($customer['email'], "Your Smart Store Receipt #$purchase_id", $body);
    }

    private function formatReceiptEmail(string $customer_name, array $receipt): string
    {
        $lines = array_map(fn($r) => sprintf(
            "- %s x%d @ $%.2f = $%.2f",
            $r['product_name'], $r['quantity'], $r['unit_price'], $r['subtotal']
        ), $receipt);

        return sprintf(
            "Hi %s,\n\nHere is your receipt:\n\n%s\n\nTotal: $%.2f\nPoints earned: %d\n\nThank you for shopping with us!",
            $customer_name,
            implode("\n", $lines),
            $receipt[0]['total_amount'],
            $receipt[0]['points_earned']
        );
    }
}
