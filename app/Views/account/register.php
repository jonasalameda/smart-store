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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="<?= htmlspecialchars($base) ?>/public/assets/js/notification_popup.js"></script>
  <style>
    h4, h5, .form-label { font-family: 'Lobster Two', cursive; }
    .form-control { font-family: Arial, sans-serif; }
    .btn { font-family: 'Lobster Two', cursive; }
  </style>
</head>
<body class="bg-white min-vh-100 d-flex flex-column justify-content-center py-4">
<main class="main-content w-100">
  <div class="container py-3" style="max-width:720px;">
    <div class="card shadow-lg border-0">
      <div class="card-header bg-primary text-black">
        <h4 class="mb-0">Create account</h4>
      </div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="customerForm" method="post" action="<?= htmlspecialchars($base) ?>/account/register">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="first_name">*Customer Name</label>
              <input type="text" class="form-control" name="first_name" id="first_name"
                     required
                     value="<?= htmlspecialchars((string)($form['first_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">*Telephone</label>
              <input type="text" class="form-control" name="phone" id="phone" required
                     value="<?= htmlspecialchars((string)($form['phone'] ?? '')) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="address">Address</label>
            <input type="text" class="form-control" name="address" id="address"
                   value="<?= htmlspecialchars((string)($form['address'] ?? '')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="email">*Email</label>
            <input type="text" class="form-control" name="email" id="email" required autocomplete="email"
                   value="<?= htmlspecialchars((string)($form['email'] ?? '')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" name="password" id="password" required minlength="6" autocomplete="new-password" placeholder="Min. 6 characters">
          </div>
          <div class="mb-4">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input class="form-control" type="password" name="password_confirm" id="password_confirm" required minlength="6" autocomplete="new-password" placeholder="Repeat password">
          </div>

          <button type="submit" class="btn btn-outline-success">Create account</button>
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
