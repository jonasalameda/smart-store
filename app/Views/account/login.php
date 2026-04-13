<?php
$pageTitle = $data['pageTitle'] ?? __('account.login_title');
$current_page = $data['current_page'] ?? 'account_login';
$error = $data['error'] ?? null;
$success = $data['success'] ?? null;
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
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content w-100">
  <div class="container py-5" style="max-width:480px;">
    <div class="text-center mb-2">
      <?php include __DIR__ . '/../common/lang_switcher.php'; ?>
    </div>
    <div class="text-center mb-4">
      <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3 fw-bold" style="width:3rem;height:3rem;font-size:1.25rem;">A</div>
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('account.customer')) ?></p>
      <h1 class="h2 fw-bold"><?= htmlspecialchars(__('account.login_title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars(__('account.login_sub')) ?></p>
    </div>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow">
      <div class="card-body p-4">
        <form method="post" action="<?= htmlspecialchars($base) ?>/account/login">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('account.email')) ?></label>
            <input class="form-control form-control-lg" type="email" name="email" required autocomplete="username" placeholder="<?= htmlspecialchars(__('account.placeholder_email')) ?>">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('account.password')) ?></label>
            <input class="form-control form-control-lg" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg"><?= htmlspecialchars(__('account.continue')) ?></button>
          <p class="text-center text-muted small mt-3 mb-1">
            <?= htmlspecialchars(__('account.no_account')) ?>
            <a href="<?= htmlspecialchars($base) ?>/account/register"><?= htmlspecialchars(__('nav.register')) ?></a>
            <span class="text-muted">·</span>
            <a href="<?= htmlspecialchars($base) ?>/checkout"><?= htmlspecialchars(__('account.continue_as_guest')) ?></a>
          </p>
          <p class="text-center text-muted small mb-0"><?= htmlspecialchars(__('account.continue_as_guest_sub')) ?></p>
        </form>
      </div>
    </div>
  </div>
</main>
<a href="<?= htmlspecialchars($base) ?>/admin/login" class="btn btn-sm btn-outline-secondary position-fixed bottom-0 end-0 m-3 shadow-sm"><?= htmlspecialchars(__('account.admin_staff')) ?></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
