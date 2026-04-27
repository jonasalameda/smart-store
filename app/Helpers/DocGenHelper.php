<?php
// First, run this: "../../composer.bat" require tecnickcom/tcpdf
declare(strict_types=1);

namespace App\Helpers;

use TCPDF;
use PDO;
use InvalidArgumentException;

class DocGenHelper
{
    public function __construct() {}

    // ═══════════════════════════════════════════════════════════════
    //  SHARED PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build a base TCPDF instance with Smart Store branding.
     */
    private function createPdf(string $title, string $subject): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('Smart Store IoT System');
        $pdf->SetAuthor('Smart Store');
        $pdf->SetTitle($title);
        $pdf->SetSubject($subject);

        $pdf->setHeaderData('', 0, 'Smart Store', $subject, [0, 51, 102], [0, 51, 102]);
        $pdf->setHeaderFont(['helvetica', 'B', 12]);

        $pdf->setFooterData([0, 51, 102], [0, 51, 102]);
        $pdf->setFooterFont(['helvetica', '', 9]);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Page title block
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(4);

        return $pdf;
    }

    /**
     * Render a styled HTML table inside the PDF.
     *
     * @param TCPDF  $pdf
     * @param array  $headers  ['Column 1', 'Column 2', ...]
     * @param array  $rows     [ ['val1','val2',...], ... ]
     * @param string $caption  Optional table caption / section heading.
     */
    private function renderTable(TCPDF $pdf, array $headers, array $rows, string $caption = ''): void
    {
        if ($caption !== '') {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(0, 51, 102);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 7, $caption, 0, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(1);
        }

        $colCount = count($headers);
        $colWidth = (int) round(100 / $colCount);

        $html  = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">';
        $html .= '<tr style="background-color:#003366;color:#ffffff;font-weight:bold;">';
        foreach ($headers as $h) {
            $html .= '<th width="' . $colWidth . '%">' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr>';

        foreach ($rows as $i => $row) {
            $bg    = ($i % 2 === 0) ? '#f0f4fa' : '#ffffff';
            $html .= '<tr style="background-color:' . $bg . ';">';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);
    }

    /**
     * Render a key→value summary block (e.g. totals, filter info).
     *
     * @param TCPDF  $pdf
     * @param array  $items  ['Label' => 'Value', ...]
     * @param string $title  Section heading.
     */
    private function renderSummaryBlock(TCPDF $pdf, array $items, string $title = ''): void
    {
        if ($title !== '') {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(230, 236, 245);
            $pdf->Cell(0, 7, $title, 0, 1, 'L', true);
            $pdf->Ln(1);
        }

        foreach ($items as $label => $value) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 6, $label . ':', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, (string) $value, 0, 1);
        }
        $pdf->Ln(4);
    }

    /**
     * Stream PDF to the browser as a forced download, then exit.
     */
    private function streamPdf(TCPDF $pdf, string $filename): void
    {
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Return raw PDF bytes without streaming.
     */
    private function getPdfBytes(TCPDF $pdf): string
    {
        return $pdf->Output('report.pdf', 'S');
    }


    // ═══════════════════════════════════════════════════════════════
    //  ADMIN REPORT 1 — INVENTORY REPORT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate an Inventory Report for administrators.
     *
     * @param array $params {
     *   'low_stock_threshold' => int   (default 10)
     *   'category_id'         => int   (optional)
     *   'stream'              => bool  (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generateInventoryReport(array $params, PDO $pdo)
    {
        $lowThreshold = (int) ($params['low_stock_threshold'] ?? 10);
        $categoryId   = isset($params['category_id']) ? (int) $params['category_id'] : null;
        $stream       = (bool) ($params['stream'] ?? true);

        $sql = '
            SELECT
                p.id                            AS product_id,
                p.name                          AS product_name,
                c.name                          AS category,
                sr.current_stock,
                sr.quantity_received,
                sr.date_received                AS last_restocked,
                p.price
            FROM product p
            JOIN category c         ON c.id = p.category_id
            JOIN stock_reception sr  ON sr.product_id = p.id
            WHERE sr.id = (
                SELECT MAX(sr2.id)
                FROM stock_reception sr2
                WHERE sr2.product_id = p.id
            )
        ';

        $bindings = [];
        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $bindings[':category_id'] = $categoryId;
        }
        $sql .= ' ORDER BY sr.current_stock ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalProducts = count($products);
        $lowCount      = 0;
        $outCount      = 0;

        $pdf = $this->createPdf('Inventory Report', 'Administrator View — Stock Levels');

        $filterInfo = ['Low-Stock Threshold' => $lowThreshold . ' units'];
        if ($categoryId !== null) {
            $filterInfo['Category Filter'] = 'Category ID ' . $categoryId;
        }
        $this->renderSummaryBlock($pdf, $filterInfo, 'Applied Filters');

        $rows = [];
        foreach ($products as $p) {
            $stock = (int) $p['current_stock'];

            if ($stock === 0) {
                $status = 'OUT OF STOCK';
                $outCount++;
            } elseif ($stock < $lowThreshold) {
                $status = 'LOW';
                $lowCount++;
            } else {
                $status = 'OK';
            }

            $rows[] = [
                $p['product_id'],
                $p['product_name'],
                $p['category'],
                $stock,
                $p['quantity_received'],
                $p['last_restocked'],
                '$' . number_format((float) $p['price'], 2),
                $status,
            ];
        }

        $this->renderTable(
            $pdf,
            ['ID', 'Product', 'Category', 'In Stock', 'Last Received Qty', 'Last Restocked', 'Unit Price', 'Status'],
            $rows,
            'Stock Overview'
        );

        $this->renderSummaryBlock($pdf, [
            'Total Products'     => $totalProducts,
            'Low Stock Items'    => $lowCount . ' (below ' . $lowThreshold . ' units)',
            'Out of Stock Items' => $outCount,
        ], 'Summary');

        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 5,
            "Status Legend:  OK = stock >= {$lowThreshold} units  |  LOW = 1-" . ($lowThreshold - 1) . " units  |  OUT OF STOCK = 0 units",
            0, 'L'
        );

        return $stream
            ? $this->streamPdf($pdf, 'inventory_report_' . date('Ymd') . '.pdf')
            : $this->getPdfBytes($pdf);
    }


    // ═══════════════════════════════════════════════════════════════
    //  ADMIN REPORT 2 — SALES REPORT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate a Sales Report for administrators.
     *
     * @param array $params {
     *   'date_from'   => string  (required) — 'YYYY-MM-DD'
     *   'date_to'     => string  (required) — 'YYYY-MM-DD'
     *   'category_id' => int     (optional)
     *   'top_n'       => int     (default 5)
     *   'stream'      => bool    (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generateSalesReport(array $params, PDO $pdo)
    {
        $dateFrom   = $params['date_from']   ?? date('Y-m-01');
        $dateTo     = $params['date_to']     ?? date('Y-m-d');
        $categoryId = isset($params['category_id']) ? (int) $params['category_id'] : null;
        $topN       = (int) ($params['top_n'] ?? 5);
        $stream     = (bool) ($params['stream'] ?? true);

        $sqlItems = '
            SELECT
                p.id                  AS product_id,
                p.name                AS product_name,
                cat.name              AS category,
                SUM(pi.quantity)      AS total_sold,
                SUM(pi.subtotal)      AS total_revenue,
                AVG(pi.unit_price)    AS avg_price
            FROM purchase_item pi
            JOIN purchase  pu  ON pu.id  = pi.purchase_id
            JOIN product   p   ON p.id   = pi.product_id
            JOIN category  cat ON cat.id = p.category_id
            WHERE DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
        ';

        $bindings = [':date_from' => $dateFrom, ':date_to' => $dateTo];
        if ($categoryId !== null) {
            $sqlItems .= ' AND p.category_id = :category_id';
            $bindings[':category_id'] = $categoryId;
        }
        $sqlItems .= ' GROUP BY p.id, p.name, cat.name ORDER BY total_sold DESC';

        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute($bindings);
        $soldItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $sqlRev = '
            SELECT
                COUNT(DISTINCT pu.id) AS total_transactions,
                SUM(pu.total_amount)  AS total_revenue,
                SUM(pu.points_earned) AS total_points
            FROM purchase pu
            WHERE DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
        ';
        $stmtRev = $pdo->prepare($sqlRev);
        $stmtRev->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $revenue = $stmtRev->fetch(PDO::FETCH_ASSOC);

        $sqlDaily = '
            SELECT
                DATE(pu.purchase_date) AS sale_date,
                COUNT(pu.id)           AS transactions,
                SUM(pu.total_amount)   AS daily_revenue
            FROM purchase pu
            WHERE DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
            GROUP BY sale_date
            ORDER BY sale_date ASC
        ';
        $stmtDaily = $pdo->prepare($sqlDaily);
        $stmtDaily->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $dailyRevenue = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

        $pdf = $this->createPdf('Sales Report', "Period: {$dateFrom} to {$dateTo}");

        $filterInfo = ['Date Range' => "{$dateFrom} to {$dateTo}", 'Top/Bottom N' => $topN];
        if ($categoryId !== null) {
            $filterInfo['Category Filter'] = 'Category ID ' . $categoryId;
        }
        $this->renderSummaryBlock($pdf, $filterInfo, 'Applied Filters');

        $this->renderSummaryBlock($pdf, [
            'Total Transactions'  => $revenue['total_transactions'] ?? 0,
            'Total Revenue'       => '$' . number_format((float) ($revenue['total_revenue'] ?? 0), 2),
            'Total Points Earned' => number_format((int) ($revenue['total_points'] ?? 0)),
        ], 'Revenue Summary');

        $rows = [];
        foreach ($soldItems as $item) {
            $rows[] = [
                $item['product_id'],
                $item['product_name'],
                $item['category'],
                (int) $item['total_sold'],
                '$' . number_format((float) $item['avg_price'], 2),
                '$' . number_format((float) $item['total_revenue'], 2),
            ];
        }
        $this->renderTable(
            $pdf,
            ['ID', 'Product', 'Category', 'Units Sold', 'Avg Price', 'Revenue'],
            $rows,
            'Sold Items by Product'
        );

        $topRows = array_slice($soldItems, 0, $topN);
        $topTableRows = [];
        foreach ($topRows as $rank => $item) {
            $topTableRows[] = [
                '#' . ($rank + 1),
                $item['product_name'],
                (int) $item['total_sold'],
                '$' . number_format((float) $item['total_revenue'], 2),
            ];
        }
        $this->renderTable($pdf, ['Rank', 'Product', 'Units Sold', 'Revenue'], $topTableRows, "Top {$topN} Best Sellers");

        $bottomRows = array_reverse(array_slice($soldItems, -$topN));
        $bottomTableRows = [];
        foreach ($bottomRows as $rank => $item) {
            $bottomTableRows[] = [
                '#' . ($rank + 1),
                $item['product_name'],
                (int) $item['total_sold'],
                '$' . number_format((float) $item['total_revenue'], 2),
            ];
        }
        $this->renderTable($pdf, ['Rank', 'Product', 'Units Sold', 'Revenue'], $bottomTableRows, "Bottom {$topN} Least Sold");

        $dailyRows = [];
        foreach ($dailyRevenue as $day) {
            $dailyRows[] = [
                $day['sale_date'],
                (int) $day['transactions'],
                '$' . number_format((float) $day['daily_revenue'], 2),
            ];
        }
        $this->renderTable($pdf, ['Date', 'Transactions', 'Daily Revenue'], $dailyRows, 'Daily Revenue Breakdown');

        return $stream
            ? $this->streamPdf($pdf, 'sales_report_' . $dateFrom . '_' . $dateTo . '.pdf')
            : $this->getPdfBytes($pdf);
    }


    // ═══════════════════════════════════════════════════════════════
    //  ADMIN REPORT 3 — CUSTOMER ACTIVITY REPORT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate a Customer Activity Report for administrators.
     *
     * @param array $params {
     *   'date_from' => string  (required) — 'YYYY-MM-DD'
     *   'date_to'   => string  (required) — 'YYYY-MM-DD'
     *   'stream'    => bool    (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generateCustomerActivityReport(array $params, PDO $pdo)
    {
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo   = $params['date_to']   ?? date('Y-m-d');
        $stream   = (bool) ($params['stream'] ?? true);

        $sqlDaily = '
            SELECT
                DATE(pu.purchase_date)         AS activity_date,
                COUNT(DISTINCT pu.customer_id) AS unique_customers,
                COUNT(pu.id)                   AS total_purchases,
                SUM(pu.total_amount)           AS daily_revenue
            FROM purchase pu
            WHERE pu.customer_id IS NOT NULL
              AND DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
            GROUP BY activity_date
            ORDER BY activity_date ASC
        ';
        $stmtDaily = $pdo->prepare($sqlDaily);
        $stmtDaily->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $dailyActivity = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

        $sqlTotal = '
            SELECT COUNT(DISTINCT pu.customer_id) AS total_customers
            FROM purchase pu
            WHERE pu.customer_id IS NOT NULL
              AND DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
        ';
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $totals = $stmtTotal->fetch(PDO::FETCH_ASSOC);

        $sqlNew = '
            SELECT COUNT(DISTINCT c.CustomerID) AS new_customers
            FROM customer c
            JOIN purchase pu ON pu.customer_id = c.CustomerID
            WHERE DATE(c.CreatedAt) BETWEEN :date_from AND :date_to
              AND DATE(pu.purchase_date) BETWEEN :date_from2 AND :date_to2
        ';
        $stmtNew = $pdo->prepare($sqlNew);
        $stmtNew->execute([
            ':date_from'  => $dateFrom, ':date_to'  => $dateTo,
            ':date_from2' => $dateFrom, ':date_to2' => $dateTo,
        ]);
        $newData = $stmtNew->fetch(PDO::FETCH_ASSOC);

        $sqlTop = '
            SELECT
                c.CustomerID,
                CONCAT(c.FirstName, " ", c.LastName) AS customer_name,
                c.MembershipNumber,
                COUNT(pu.id)                         AS purchase_count,
                SUM(pu.total_amount)                 AS total_spent,
                SUM(pu.points_earned)                AS points_earned
            FROM customer c
            JOIN purchase pu ON pu.customer_id = c.CustomerID
            WHERE DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
            GROUP BY c.CustomerID, c.FirstName, c.LastName, c.MembershipNumber
            ORDER BY total_spent DESC
            LIMIT 10
        ';
        $stmtTop = $pdo->prepare($sqlTop);
        $stmtTop->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $topCustomers = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        $pdf = $this->createPdf('Customer Activity Report', "Period: {$dateFrom} to {$dateTo}");

        $this->renderSummaryBlock($pdf, ['Date Range' => "{$dateFrom} to {$dateTo}"], 'Applied Filters');

        $totalCustomers     = (int) ($totals['total_customers'] ?? 0);
        $newCustomers       = (int) ($newData['new_customers']  ?? 0);
        $returningCustomers = max(0, $totalCustomers - $newCustomers);

        $this->renderSummaryBlock($pdf, [
            'Total Unique Customers' => $totalCustomers,
            'New Customers'          => $newCustomers,
            'Returning Customers'    => $returningCustomers,
        ], 'Customer Overview');

        $dailyRows = [];
        foreach ($dailyActivity as $day) {
            $dailyRows[] = [
                $day['activity_date'],
                (int) $day['unique_customers'],
                (int) $day['total_purchases'],
                '$' . number_format((float) $day['daily_revenue'], 2),
            ];
        }
        $this->renderTable(
            $pdf,
            ['Date', 'Unique Customers', 'Total Purchases', 'Revenue'],
            $dailyRows,
            'Daily Customer Activity'
        );

        $topRows = [];
        foreach ($topCustomers as $cust) {
            $topRows[] = [
                $cust['CustomerID'],
                $cust['customer_name'],
                $cust['MembershipNumber'],
                (int) $cust['purchase_count'],
                '$' . number_format((float) $cust['total_spent'], 2),
                number_format((int) $cust['points_earned']),
            ];
        }
        $this->renderTable(
            $pdf,
            ['ID', 'Name', 'Membership', 'Purchases', 'Total Spent', 'Points'],
            $topRows,
            'Top 10 Customers by Spend'
        );

        return $stream
            ? $this->streamPdf($pdf, 'customer_activity_' . $dateFrom . '_' . $dateTo . '.pdf')
            : $this->getPdfBytes($pdf);
    }


    // ═══════════════════════════════════════════════════════════════
    //  CUSTOMER REPORT 1 — RECEIPT HISTORY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate a personal Receipt History report for a customer.
     *
     * @param array $params {
     *   'customer_id' => int     (required)
     *   'date_from'   => string  (optional) — defaults to 30 days ago
     *   'date_to'     => string  (optional) — defaults to today
     *   'stream'      => bool    (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generateReceiptHistoryReport(array $params, PDO $pdo)
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $dateFrom   = $params['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo     = $params['date_to']   ?? date('Y-m-d');
        $stream     = (bool) ($params['stream'] ?? true);

        if ($customerId === 0) {
            throw new InvalidArgumentException('customer_id is required.');
        }

        $stmtC = $pdo->prepare('SELECT FirstName, LastName, Email, MembershipNumber FROM customer WHERE CustomerID = :id');
        $stmtC->execute([':id' => $customerId]);
        $customer = $stmtC->fetch(PDO::FETCH_ASSOC);

        $sql = '
            SELECT
                pu.id                AS purchase_id,
                pu.purchase_date,
                pu.payment_method,
                pu.total_amount,
                pu.points_earned,
                p.name               AS product_name,
                cat.name             AS category,
                pi.quantity,
                pi.unit_price,
                pi.subtotal
            FROM purchase pu
            JOIN purchase_item pi  ON pi.purchase_id = pu.id
            JOIN product       p   ON p.id           = pi.product_id
            JOIN category      cat ON cat.id          = p.category_id
            WHERE pu.customer_id = :customer_id
              AND DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
            ORDER BY pu.purchase_date DESC, pu.id, pi.id
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':customer_id' => $customerId, ':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group rows by purchase_id
        $purchases = [];
        foreach ($allRows as $row) {
            $pid = $row['purchase_id'];
            if (!isset($purchases[$pid])) {
                $purchases[$pid] = [
                    'purchase_date'  => $row['purchase_date'],
                    'payment_method' => $row['payment_method'],
                    'total_amount'   => $row['total_amount'],
                    'points_earned'  => $row['points_earned'],
                    'items'          => [],
                ];
            }
            $purchases[$pid]['items'][] = [
                $row['product_name'],
                $row['category'],
                (int) $row['quantity'],
                '$' . number_format((float) $row['unit_price'], 2),
                '$' . number_format((float) $row['subtotal'],   2),
            ];
        }

        $pdf = $this->createPdf(
            'Purchase History',
            ($customer['FirstName'] ?? '') . ' ' . ($customer['LastName'] ?? '') . ' — ' . $dateFrom . ' to ' . $dateTo
        );

        $this->renderSummaryBlock($pdf, [
            'Customer'       => ($customer['FirstName'] ?? '') . ' ' . ($customer['LastName'] ?? ''),
            'Email'          => $customer['Email'] ?? 'N/A',
            'Membership #'   => $customer['MembershipNumber'] ?? 'N/A',
            'Period'         => "{$dateFrom} to {$dateTo}",
            'Total Receipts' => count($purchases),
        ], 'Account Info');

        foreach ($purchases as $pid => $purchase) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(220, 230, 245);
            $pdf->Cell(0, 7,
                'Receipt #' . $pid . '   |   ' . $purchase['purchase_date'] . '   |   ' . ucfirst(str_replace('_', ' ', $purchase['payment_method'])),
                0, 1, 'L', true
            );
            $pdf->Ln(1);

            $this->renderTable(
                $pdf,
                ['Product', 'Category', 'Qty', 'Unit Price', 'Subtotal'],
                $purchase['items']
            );

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(130, 6, 'Total', 0, 0, 'R');
            $pdf->Cell(0, 6, '$' . number_format((float) $purchase['total_amount'], 2), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(130, 6, 'Points Earned', 0, 0, 'R');
            $pdf->Cell(0, 6, (int) $purchase['points_earned'] . ' pts', 0, 1, 'R');
            $pdf->Ln(5);
        }

        return $stream
            ? $this->streamPdf($pdf, 'receipt_history_' . $customerId . '_' . date('Ymd') . '.pdf')
            : $this->getPdfBytes($pdf);
    }


    // ═══════════════════════════════════════════════════════════════
    //  CUSTOMER REPORT 2 — PURCHASE AMOUNT SUMMARY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate a total-spend summary report for a customer.
     *
     * @param array $params {
     *   'customer_id' => int     (required)
     *   'date_from'   => string  (optional)
     *   'date_to'     => string  (optional)
     *   'stream'      => bool    (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generatePurchaseSummaryReport(array $params, PDO $pdo)
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $dateFrom   = $params['date_from'] ?? date('Y-m-01');
        $dateTo     = $params['date_to']   ?? date('Y-m-d');
        $stream     = (bool) ($params['stream'] ?? true);

        if ($customerId === 0) {
            throw new InvalidArgumentException('customer_id is required.');
        }

        $stmtC = $pdo->prepare('SELECT FirstName, LastName, Email, MembershipNumber, TotalPoints FROM customer WHERE CustomerID = :id');
        $stmtC->execute([':id' => $customerId]);
        $customer = $stmtC->fetch(PDO::FETCH_ASSOC);

        $sqlPeriod = '
            SELECT
                COUNT(id)          AS purchase_count,
                SUM(total_amount)  AS total_spent,
                SUM(points_earned) AS points_in_period,
                AVG(total_amount)  AS avg_basket
            FROM purchase
            WHERE customer_id = :customer_id
              AND DATE(purchase_date) BETWEEN :date_from AND :date_to
        ';
        $stmtP = $pdo->prepare($sqlPeriod);
        $stmtP->execute([':customer_id' => $customerId, ':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $periodTotals = $stmtP->fetch(PDO::FETCH_ASSOC);

        $sqlCat = '
            SELECT
                cat.name          AS category,
                SUM(pi.quantity)  AS units_bought,
                SUM(pi.subtotal)  AS category_spend
            FROM purchase_item pi
            JOIN purchase  pu  ON pu.id  = pi.purchase_id
            JOIN product   p   ON p.id   = pi.product_id
            JOIN category  cat ON cat.id = p.category_id
            WHERE pu.customer_id = :customer_id
              AND DATE(pu.purchase_date) BETWEEN :date_from AND :date_to
            GROUP BY cat.name
            ORDER BY category_spend DESC
        ';
        $stmtCat = $pdo->prepare($sqlCat);
        $stmtCat->execute([':customer_id' => $customerId, ':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $categorySpend = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        $sqlMonthly = '
            SELECT
                DATE_FORMAT(purchase_date, "%Y-%m") AS month,
                COUNT(id)                           AS purchases,
                SUM(total_amount)                   AS monthly_spend
            FROM purchase
            WHERE customer_id = :customer_id
              AND DATE(purchase_date) BETWEEN :date_from AND :date_to
            GROUP BY month
            ORDER BY month ASC
        ';
        $stmtM = $pdo->prepare($sqlMonthly);
        $stmtM->execute([':customer_id' => $customerId, ':date_from' => $dateFrom, ':date_to' => $dateTo]);
        $monthly = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        $pdf = $this->createPdf('Spending Summary', ($customer['FirstName'] ?? '') . ' ' . ($customer['LastName'] ?? ''));

        $this->renderSummaryBlock($pdf, [
            'Customer'     => ($customer['FirstName'] ?? '') . ' ' . ($customer['LastName'] ?? ''),
            'Membership #' => $customer['MembershipNumber'] ?? 'N/A',
            'Total Points' => number_format((int) ($customer['TotalPoints'] ?? 0)),
            'Period'       => "{$dateFrom} to {$dateTo}",
        ], 'Account Info');

        $this->renderSummaryBlock($pdf, [
            'Total Purchases'    => (int) ($periodTotals['purchase_count']    ?? 0),
            'Total Spent'        => '$' . number_format((float) ($periodTotals['total_spent']       ?? 0), 2),
            'Points This Period' => number_format((int) ($periodTotals['points_in_period'] ?? 0)),
            'Avg. Basket Size'   => '$' . number_format((float) ($periodTotals['avg_basket']        ?? 0), 2),
        ], 'Period Summary');

        $catRows = [];
        foreach ($categorySpend as $cat) {
            $catRows[] = [
                $cat['category'],
                (int) $cat['units_bought'],
                '$' . number_format((float) $cat['category_spend'], 2),
            ];
        }
        $this->renderTable($pdf, ['Category', 'Units Purchased', 'Amount Spent'], $catRows, 'Spending by Category');

        $monthRows = [];
        foreach ($monthly as $m) {
            $monthRows[] = [
                $m['month'],
                (int) $m['purchases'],
                '$' . number_format((float) $m['monthly_spend'], 2),
            ];
        }
        $this->renderTable($pdf, ['Month', 'Purchases', 'Total Spent'], $monthRows, 'Monthly Breakdown');

        return $stream
            ? $this->streamPdf($pdf, 'spending_summary_' . $customerId . '_' . date('Ymd') . '.pdf')
            : $this->getPdfBytes($pdf);
    }


    // ═══════════════════════════════════════════════════════════════
    //  CUSTOMER REPORT 3 — ITEM SEARCH REPORT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate a report showing a customer's purchase history for a specific item.
     *
     * @param array $params {
     *   'customer_id' => int     (required)
     *   'search_term' => string  (required) — partial product name (LIKE search)
     *   'date_from'   => string  (optional)
     *   'date_to'     => string  (optional)
     *   'stream'      => bool    (default true)
     * }
     * @param PDO $pdo
     * @return string|void
     */
    public function generateItemSearchReport(array $params, PDO $pdo)
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $searchTerm = trim($params['search_term'] ?? '');
        $dateFrom   = $params['date_from'] ?? null;
        $dateTo     = $params['date_to']   ?? null;
        $stream     = (bool) ($params['stream'] ?? true);

        if ($customerId === 0) {
            throw new InvalidArgumentException('customer_id is required.');
        }
        if ($searchTerm === '') {
            throw new InvalidArgumentException('search_term is required.');
        }

        $stmtC = $pdo->prepare('SELECT FirstName, LastName, MembershipNumber FROM customer WHERE CustomerID = :id');
        $stmtC->execute([':id' => $customerId]);
        $customer = $stmtC->fetch(PDO::FETCH_ASSOC);

        $sql = '
            SELECT
                p.name              AS product_name,
                cat.name            AS category,
                pu.purchase_date,
                pi.quantity,
                pi.unit_price,
                pi.subtotal
            FROM purchase_item pi
            JOIN purchase  pu  ON pu.id  = pi.purchase_id
            JOIN product   p   ON p.id   = pi.product_id
            JOIN category  cat ON cat.id = p.category_id
            WHERE pu.customer_id = :customer_id
              AND p.name LIKE :search
        ';

        $bindings = [
            ':customer_id' => $customerId,
            ':search'      => '%' . $searchTerm . '%',
        ];

        if ($dateFrom !== null) {
            $sql .= ' AND DATE(pu.purchase_date) >= :date_from';
            $bindings[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $sql .= ' AND DATE(pu.purchase_date) <= :date_to';
            $bindings[':date_to'] = $dateTo;
        }
        $sql .= ' ORDER BY pu.purchase_date DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        $hits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalQty      = array_sum(array_column($hits, 'quantity'));
        $totalSpent    = array_sum(array_column($hits, 'subtotal'));
        $purchaseDates = array_unique(array_column($hits, 'purchase_date'));

        $pdf = $this->createPdf('Item Search Report', 'Search: "' . $searchTerm . '"');

        $this->renderSummaryBlock($pdf, [
            'Customer'    => ($customer['FirstName'] ?? '') . ' ' . ($customer['LastName'] ?? ''),
            'Membership'  => $customer['MembershipNumber'] ?? 'N/A',
            'Search Term' => '"' . $searchTerm . '"',
            'Date Filter' => ($dateFrom && $dateTo) ? "{$dateFrom} to {$dateTo}" : 'All time',
        ], 'Search Parameters');

        $this->renderSummaryBlock($pdf, [
            'Matching Records'      => count($hits),
            'Unique Purchase Dates' => count($purchaseDates),
            'Total Units Purchased' => $totalQty,
            'Total Amount Spent'    => '$' . number_format((float) $totalSpent, 2),
        ], 'Search Results Summary');

        if (count($hits) > 0) {
            $rows = [];
            foreach ($hits as $row) {
                $rows[] = [
                    $row['product_name'],
                    $row['category'],
                    $row['purchase_date'],
                    (int) $row['quantity'],
                    '$' . number_format((float) $row['unit_price'], 2),
                    '$' . number_format((float) $row['subtotal'],   2),
                ];
            }
            $this->renderTable(
                $pdf,
                ['Product', 'Category', 'Date & Time', 'Qty', 'Unit Price', 'Subtotal'],
                $rows,
                'Purchase Instances'
            );
        } else {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 8, 'No purchases found matching "' . $searchTerm . '".', 0, 1, 'C');
        }

        return $stream
            ? $this->streamPdf($pdf, 'item_search_' . $customerId . '_' . date('Ymd') . '.pdf')
            : $this->getPdfBytes($pdf);
    }
}
