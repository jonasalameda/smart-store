<?php
$pageTitle = $data['pageTitle'] ?? 'Register';
$current_page = $data['current_page'] ?? 'account_register';
$error = $data['error'] ?? null;
$form = $data['form'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/public/assets/css/layout/customer.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/../customer/header.php'; ?>
<main class="main-content">
  <div class="container py-5" style="max-width:520px;">
    <div class="text-center mb-4">
      <p class="small text-uppercase text-muted fw-semibold mb-1">Customer</p>
      <h1 class="h2 fw-bold">Create account</h1>
      <p class="text-muted small mb-0">Earn points on purchases and get receipts by email at checkout.</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow">
      <div class="card-body p-4">
        <form method="post" action="<?= htmlspecialchars($base) ?>/account/register">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">First name</label>
              <input class="form-control" name="first_name" required placeholder="Ada" value="<?= htmlspecialchars((string)($form['first_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Last name</label>
              <input class="form-control" name="last_name" required placeholder="Lovelace" value="<?= htmlspecialchars((string)($form['last_name'] ?? '')) ?>">
            </div>
          </div>
          <div class="mb-3 mt-1">
            <label class="form-label fw-semibold">Email</label>
            <input class="form-control" type="email" name="email" required autocomplete="email" placeholder="you@example.com" value="<?= htmlspecialchars((string)($form['email'] ?? '')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control" type="tel" name="phone" autocomplete="tel" placeholder="514-555-0100" value="<?= htmlspecialchars((string)($form['phone'] ?? '')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input class="form-control" type="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Min. 6 characters">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Confirm password</label>
            <input class="form-control" type="password" name="password_confirm" required minlength="6" autocomplete="new-password" placeholder="Repeat password">
          </div>
          <button type="submit" class="btn btn-primary w-100">Create account</button>
          <p class="text-center text-muted small mt-3 mb-0">
            <a href="<?= htmlspecialchars($base) ?>/account/login">Back to sign in</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
