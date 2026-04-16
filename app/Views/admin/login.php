<?php
$pageTitle = $data['pageTitle'] ?? __('staff.page_title');
$error = $data['error'] ?? null;
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column justify-content-center py-4">
<div class="text-center mb-2"><?php include __DIR__ . '/../common/lang_switcher.php'; ?></div>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content w-100">
  <div class="container py-5" style="max-width:480px;">
    <div class="text-center mb-4">
      <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3 fw-bold" style="width:3rem;height:3rem;font-size:1.25rem;">S</div>
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('staff.badge')) ?></p>
      <h1 class="h2 fw-bold"><?= htmlspecialchars(__('staff.sign_in_title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars(__('staff.login_sub')) ?></p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow">
      <div class="card-body p-4">
        <form method="post" action="<?= htmlspecialchars($base) ?>/admin/login">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('staff.email')) ?></label>
            <input class="form-control form-control-lg" type="email" name="email" required autocomplete="username" placeholder="<?= htmlspecialchars(__('checkout.guest_receipt_placeholder')) ?>">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold" for="admin-login-password"><?= htmlspecialchars(__('staff.password')) ?></label>
            <div class="input-group input-group-lg">
              <input class="form-control" type="password" name="password" id="admin-login-password" required autocomplete="current-password" placeholder="••••••••">
              <button type="button" class="btn btn-outline-secondary" data-password-toggle-for="admin-login-password" data-label-show="<?= htmlspecialchars(__('common.show_password'), ENT_QUOTES) ?>" data-label-hide="<?= htmlspecialchars(__('common.hide_password'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(__('common.show_password'), ENT_QUOTES) ?>">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg"><?= htmlspecialchars(__('staff.continue')) ?></button>
          <p class="text-center text-muted small mt-3 mb-0">
            <?= htmlspecialchars(__('staff.customer_prompt')) ?> <a href="<?= htmlspecialchars($base) ?>/account/login"><?= htmlspecialchars(__('staff.customer_link')) ?></a>
          </p>
        </form>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= htmlspecialchars($base) ?>/public/assets/js/password_toggle.js"></script>
</body>
</html>
