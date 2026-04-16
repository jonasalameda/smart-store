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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('customers_staff.page_title')) ?></title>
    <link rel="stylesheet" href="/assets/css/layout/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <script src="/assets/js/notification_popup.js"></script>
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
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-black">
                <h4><?= htmlspecialchars(__('customers_staff.add_title')) ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars((string) $success) ?></div>
                <?php endif; ?>
                <form id="customerForm" action="<?= htmlspecialchars($base) ?>/customers" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">*<?= htmlspecialchars(__('customers_staff.name')) ?></label>
                            <input type="text" class="form-control" name="first_name" id="first_name">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">*<?= htmlspecialchars(__('customers_staff.phone')) ?></label>
                            <input type="text" class="form-control" name="phone" id="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(__('customers_staff.address')) ?></label>
                        <input type="text" class="form-control" name="address" id="address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">*<?= htmlspecialchars(__('customers_staff.email')) ?></label>
                        <input type="text" class="form-control" name="email" id="email">
                    </div>
                    <button type="submit" class="btn btn-outline-success">
                        <?= htmlspecialchars(__('customers_staff.submit')) ?>
                    </button>
                </form>
            </div>
        </div>

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
                            <th><?= htmlspecialchars(__('customers_staff.col_address')) ?></th>
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
                                <td><?= htmlspecialchars((string)($customer['address'] ?? '')) ?></td>
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
