<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Form</title>
    <!-- Boostrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lobster+Two:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <script src="/assets/js/notification_popup.js"></script>
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

    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-black">
                <h4>Add Customer</h4>
            </div>
            <div class="card-body">

                <form id="customerForm" action="<?= APP_BASE_URL ?>customers">
                    <div class="row mb-3">
                        <!-- implemented regex for the 4 fields-->
                        <div class="col-md-6">
                            <label class="form-label">Customer First Name</label>
                            <input type="text" class="form-control" id="fname" required
                                pattern="[A-Za-z]{2,20}" title="First Name must Be More Than 2 Characters">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Customer Last Name</label>
                            <input type="text" class="form-control" id="lname" required
                                pattern="[A-Za-z]{2,20}" title="Last Name must Be More Than 2 Characters">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Telephone</label>
                            <input type="text" class="form-control" id="phone" required
                                pattern="\d{10}" title="Enter A 10 digits Number, e.g., 5146917552">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" required
                            pattern=".{5,100}" title="Address Must Be 5-100 Characters Long">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" required
                            pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|ca)$"
                            title="Email must end with .com or .ca">
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
                            <th>Last Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody id="customerTable">
                        <?php foreach ($customers as $customer) { ?>
                            <td><?= $customer["first_name"] ?></td>
                            <td><?= $customer["last_name"] ?></td>
                            <td><?= $customer["phone"] ?></td>
                            <td><?= $customer["address"] ?></td>
                            <td><?= $customer["email"] ?></td>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</body>

</html>