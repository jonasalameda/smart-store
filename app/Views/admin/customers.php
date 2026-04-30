<?php
$customers = $data['customers'] ?? [];
$error = $data['error'] ?? null;
$success = $data['success'] ?? null;
$current_page = 'customers';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
    <?php include __DIR__ . '/../common/theme_init.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('customers_staff.page_title')) ?></title>
    <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <script src="<?= hs(public_asset_href('js/notification_popup.js')) ?>"></script>
    <style>
        h4, h5, .form-label { font-family: 'Lobster Two', cursive; }
        .form-control { font-family: Arial, sans-serif; }
        .btn { font-family: 'Lobster Two', cursive; }
        table { font-family: 'Lobster Two', cursive; }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/header.php'; ?>
    <main class="main-content">
    <div class="container mt-4">
        <div class="text-end mb-2"><?php include __DIR__ . '/../common/lang_switcher.php'; ?></div>
        <div class="card mt-4 shadow-lg">
            <div class="card-header bg-dark text-white">
                <h5><?= htmlspecialchars(__('customers_staff.list_title')) ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th><?= htmlspecialchars(__('customers_staff.col_name')) ?></th>
                            <th><?= htmlspecialchars(__('customers_staff.col_membership')) ?></th>
                            <th><?= htmlspecialchars(__('customers_staff.col_phone')) ?></th>
                            <th><?= htmlspecialchars(__('customers_staff.col_email')) ?></th>
                            <th><?= htmlspecialchars(__('customers_staff.col_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="customerTable">
                        <?php foreach ($customers as $customer) { ?>
                            <tr>
                                <td><?php
                                    $dn = trim((string)($customer['first_name'] ?? '') . ' ' . (string)($customer['last_name'] ?? ''));
                                    echo htmlspecialchars($dn !== '' ? $dn : (string)($customer['name'] ?? ''));
                                ?></td>
                                <td><?= htmlspecialchars((string)($customer['membership_number'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($customer['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($customer['email'] ?? '')) ?></td>
                                <td>
                                    <form method="post"
                                        action="<?= htmlspecialchars($base) ?>/customers/delete/<?= (int)($customer['id'] ?? 0) ?>"
                                        onsubmit="return confirm(<?= json_encode(__('customers_staff.delete_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <?= htmlspecialchars(__('customers_staff.delete')) ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </main>
</body>
</html>
