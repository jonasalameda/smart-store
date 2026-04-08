<?php
$customers = $data["customers"] ?? [];
$error = $data["error"] ?? null;
$success = $data["success"] ?? null;
$current_page = 'customers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Form</title>
    <link rel="stylesheet" href="/assets/css/layout/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <script src="/smart-store/public/assets/js/notification_popup.js"></script>
    <!---added inline css for now, later ill seperate it into its own file-->
    <style>
        /* Headings */
        h4,
        h5 {
            font-family: 'Lobster Two', cursive;
        }

        /* Labels */
        .form-label {
            font-family: 'Lobster Two', cursive;
        }

        /* Input fields */
        .form-control {
            font-family: 'Arial', sans-serif;
        }

        /* Button text */
        .btn {
            font-family: 'Lobster Two', cursive;
        }

        /* Table content */
        table {
            font-family: 'Lobster Two', cursive;
        }
    </style>

</head>

<body class="bg-white">

    <?php include __DIR__ . '/admin/header.php'; ?>

    <main class="main-content">
    <div class="container mt-4">




        <div class="card shadow-lg">
            <div class="card-header bg-primary text-black">
                <h4>Add Customer</h4>
            </div>
            <div class="card-body">
                <form id="customerForm" action="<?= APP_BASE_URL ?>/customers" method="post">
                    <div class="row mb-3">
                        <!-- implemented regex for the 4 fields-->
                        <div class="col-md-6">
                            <label class="form-label">*Customer Name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name"
                                >
                        </div>
<!--
                        <div class="col-md-6">
                            <label class="form-label">*Customer Membership</label>
                            <input type="text" class="form-control" name="membership" id="membership" >
                        </div> -->

                        <div class="mb-2">
                            <label class="form-label">*Telephone</label>
                            <input type="text" class="form-control" name="phone" id="phone" >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" id="address" >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">*Email</label>
                        <input type="text" class="form-control" name="email" id="email">
                    </div>

                    <button type="submit" class="btn btn-outline-success">
                        Add Customer
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4 shadow-lg">
            <div class="card-header bg-dark text-white">
                <h5>Customer List</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>First Name</th>
                            <th>Membership Number</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="customerTable">
                        <?php foreach ($customers as $customer) { ?>
                            <tr>
                                <td><?= $customer["name"] ?></td>
                                <td><?= $customer["membership_number"] ?></td>
                                <td><?= $customer["phone"] ?></td>
                                <td><?= $customer["address"] ?></td>
                                <td><?= $customer["email"] ?></td>
                                <td>
                                    <form method="post"
                                        action="<?= APP_BASE_URL ?>/customers/delete/<?= $customer['id'] ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <button type="submit" class="btn btn-outline-danger">
                                            Delete
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
