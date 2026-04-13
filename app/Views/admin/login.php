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
</head>
<body class="bg-light min-vh-100 d-flex flex-column justify-content-center py-4">
<main class="main-content w-100">
  <div class="container py-5" style="max-width: 480px;">
    <div class="card border-0 shadow">
      <div class="card-body p-4">
        <h1 class="h3 fw-bold mb-3">Admin login</h1>
        <p class="text-muted small mb-4">Use your admin account credentials to access dashboard pages.</p>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($base) ?>/admin/login">
          <div class="mb-3">
            <label class="form-label fw-semibold">Admin email</label>
            <input class="form-control" type="email" name="email" required autocomplete="username">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input class="form-control" type="password" name="password" required autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
      </div>
    </div>
  </div>
</main>
</body>
</html>
