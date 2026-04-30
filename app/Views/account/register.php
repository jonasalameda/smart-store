<?php
$pageTitle = $data['pageTitle'] ?? __('account.register_title');
$current_page = $data['current_page'] ?? 'account_register';
$error = $data['error'] ?? null;
$form = $data['form'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
  <style>
    h4, h5, .form-label { font-family: 'Lobster Two', cursive; }
    .form-control { font-family: Arial, sans-serif; }
    .btn { font-family: 'Lobster Two', cursive; }
  </style>
</head>
<body class="bg-white min-vh-100 d-flex flex-column justify-content-center py-4">
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content w-100">
  <div class="container py-3" style="max-width:720px;">
    <div class="text-end mb-2 d-flex justify-content-end align-items-center gap-2 flex-wrap"><?php include __DIR__ . '/../common/theme_toggle.php'; ?><?php include __DIR__ . '/../common/lang_switcher.php'; ?></div>
    <div class="card shadow-lg border-0">
      <div class="card-header bg-primary text-black">
        <h4 class="mb-0"><?= htmlspecialchars(__('account.register_title')) ?></h4>
      </div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="customerForm" method="post" action="<?= htmlspecialchars($base) ?>/account/register">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="first_name">*<?= htmlspecialchars(__('account.name')) ?></label>
              <input type="text" class="form-control" name="first_name" id="first_name"
                     required
                     value="<?= htmlspecialchars((string)($form['first_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">*<?= htmlspecialchars(__('account.phone')) ?></label>
              <input type="text" class="form-control" name="phone" id="phone" required
                     value="<?= htmlspecialchars((string)($form['phone'] ?? '')) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="address"><?= htmlspecialchars(__('account.address')) ?></label>
            <input type="text" class="form-control" name="address" id="address"
                   value="<?= htmlspecialchars((string)($form['address'] ?? '')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="email">*<?= htmlspecialchars(__('account.email')) ?></label>
            <input type="text" class="form-control" name="email" id="email" required autocomplete="email"
                   value="<?= htmlspecialchars((string)($form['email'] ?? '')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="register-password"><?= htmlspecialchars(__('account.password')) ?></label>
            <div class="input-group">
              <input class="form-control" type="password" name="password" id="register-password" required minlength="6" autocomplete="new-password" placeholder="<?= htmlspecialchars(__('account.placeholder_password_min')) ?>">
              <button type="button" class="btn btn-outline-secondary" data-password-hold="1" data-password-toggle-for="register-password" data-label-show="<?= htmlspecialchars(__('common.show_password_hold'), ENT_QUOTES) ?>" data-label-hide="<?= htmlspecialchars(__('common.hide_password'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(__('common.show_password_hold'), ENT_QUOTES) ?>">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label" for="register-password-confirm"><?= htmlspecialchars(__('account.password_confirm')) ?></label>
            <div class="input-group">
              <input class="form-control" type="password" name="password_confirm" id="register-password-confirm" required minlength="6" autocomplete="new-password" placeholder="<?= htmlspecialchars(__('account.placeholder_password_repeat')) ?>">
              <button type="button" class="btn btn-outline-secondary" data-password-hold="1" data-password-toggle-for="register-password-confirm" data-label-show="<?= htmlspecialchars(__('common.show_password_hold'), ENT_QUOTES) ?>" data-label-hide="<?= htmlspecialchars(__('common.hide_password'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(__('common.show_password_hold'), ENT_QUOTES) ?>">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-outline-success"><?= htmlspecialchars(__('account.create')) ?></button>
          <p class="text-center text-muted small mt-3 mb-1">
            <a href="<?= htmlspecialchars($base) ?>/account/login"><?= htmlspecialchars(__('account.back_signin')) ?></a>
            <span class="text-muted">·</span>
            <a href="<?= htmlspecialchars($base) ?>/checkout"><?= htmlspecialchars(__('account.continue_as_guest')) ?></a>
          </p>
          <p class="text-center text-muted small mb-0"><?= htmlspecialchars(__('account.continue_as_guest_sub')) ?></p>
        </form>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= hs(public_asset_href('js/password_toggle.js')) ?>"></script>
</body>
</html>
