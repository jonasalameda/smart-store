<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\ProductsModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductsController extends BaseController
{
    public function __construct(Container $container, private ProductsModel $products_model)
    {
        parent::__construct($container);
    }

    /**
     * Display products resolved from an RFID/EPC. Query ?rfid= or path /rfid/products/{rfid}.
     * When absent, uses PLACEHOLDER_RFID until the external reader is integrated.
     */
    public function rfidProducts(Request $request, Response $response, array $args): Response
    {
        $rfid = isset($args['rfid']) ? rawurldecode((string) $args['rfid']) : '';
        if ($rfid === '') {
            $params = $request->getQueryParams();
            $rfid = trim((string) ($params['rfid'] ?? ''));
        }

        $used_placeholder = $rfid === '';
        if ($used_placeholder) {
            $rfid = ProductsModel::PLACEHOLDER_RFID;
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
}
