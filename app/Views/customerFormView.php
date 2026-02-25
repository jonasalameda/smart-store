<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="/assets/js/notification_popup.js"></script>
</head>
<body class="bg-grey">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Add Customer</h4>
        </div>
        <div class="card-body">

            <form id="customerForm">
                <div class="row mb-3">
                     
                    <div class="col-md-6">
                        <label class="form-label">Customer First Name</label>
                        <input type="text" class="form-control" id="fname" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Customer Last Name</label>
                        <input type="text" class="form-control" id="lname" required>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Telephone</label>
                        <input type="text" class="form-control" id="phone" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" required>
                </div>

                <button type="submit" class="btn btn-success">
                    Add Customer
                </button>
            </form>

        </div>
    </div>

    <div class="card mt-4 shadow">
        <div class="card-header bg-dark text-white">
            <h5>Customer List</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody id="customerTable"></tbody>
            </table>
        </div>
    </div>
</div>


</body>
</html>
