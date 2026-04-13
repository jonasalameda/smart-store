<?php
$pageTitle = $data['pageTitle'] ?? 'Admin login';
$error = $data['error'] ?? null;
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column justify-content-center py-4">
<main class="main-content w-100">
  <div class="container py-5" style="max-width:480px;">
    <div class="text-center mb-4">
      <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3 fw-bold" style="width:3rem;height:3rem;font-size:1.25rem;">S</div>
      <p class="small text-uppercase text-muted fw-semibold mb-1">Staff</p>
      <h1 class="h2 fw-bold">Sign in</h1>
      <p class="text-muted small mb-0">Use the allow-listed admin email and password for your store account.</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow">
      <div class="card-body p-4">
        <form method="post" action="<?= htmlspecialchars($base) ?>/admin/login">
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input class="form-control form-control-lg" type="email" name="email" required autocomplete="username" placeholder="you@example.com">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input class="form-control form-control-lg" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg">Continue</button>
          <p class="text-center text-muted small mt-3 mb-0">
            Shopping as a customer? <a href="<?= htmlspecialchars($base) ?>/account/login">Customer sign in</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
