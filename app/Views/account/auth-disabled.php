<?php
$pageTitle = $data['pageTitle'] ?? 'Customer portal unavailable';
$message = $data['message'] ?? 'Login and registration are temporarily disabled.';
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
  <div class="container py-5" style="max-width:560px;">
    <div class="card border-0 shadow">
      <div class="card-body p-4 p-md-5 text-center">
        <h1 class="h3 fw-bold mb-3">Customer portal unavailable</h1>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <a class="btn btn-primary" href="<?= htmlspecialchars($base) ?>/">Return to home</a>
      </div>
    </div>
  </div>
</main>
</body>
</html>
