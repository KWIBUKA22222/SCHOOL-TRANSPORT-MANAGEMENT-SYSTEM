
<?php
require_once "config/db.php";
require_once "config/auth.php";
$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// DELETE DRIVER
if (isset($_POST['delete_driver'])) {
    $id = (int)$_POST['driver_id'];

    try {
        $stmt = $conn->prepare(
            "DELETE FROM drivers WHERE driver_id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $message = "Driver deleted successfully!";
    } catch (mysqli_sql_exception $ex) {
        $message = "Unable to delete this driver.";
        $messageType = "error";
    }
}

// ADD OR UPDATE DRIVER
if (isset($_POST['save_driver'])) {

    $id = (int)($_POST['driver_id'] ?? 0);
    $code = trim($_POST['driver_code'] ?? "");
    $name = trim($_POST['name'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $license = trim($_POST['license_number'] ?? "");
    $status = $_POST['status'] ?? "";

    if (
        $code === "" || $name === "" ||
        $phone === "" || $license === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "error";

    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $name)) {
        $message = "Enter a valid driver name.";
        $messageType = "error";

    } elseif (!preg_match('/^[0-9+ ]{9,15}$/', $phone)) {
        $message = "Enter a valid phone number.";
        $messageType = "error";

    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $message = "Select a valid driver status.";
        $messageType = "error";

    } else {

        try {
            if ($id > 0) {

                $stmt = $conn->prepare(
                    "UPDATE drivers SET
                    driver_code=?,
                    name=?,
                    phone=?,
                    license_number=?,
                    status=?
                    WHERE driver_id=?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $code,
                    $name,
                    $phone,
                    $license,
                    $status,
                    $id
                );

                $stmt->execute();
                $stmt->close();

                $message = "Driver updated successfully!";

            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO drivers
                    (driver_code, name, phone, license_number, status)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $code,
                    $name,
                    $phone,
                    $license,
                    $status
                );

                $stmt->execute();
                $stmt->close();

                $message = "Driver added successfully!";
            }

        } catch (mysqli_sql_exception $ex) {
            $message = "Error: Driver code or license number may already exist.";
            $messageType = "error";
        }
    }
}

// GET DRIVER FOR EDITING
$editDriver = null;

if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM drivers WHERE driver_id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editDriver = $result->fetch_assoc();

    $stmt->close();
}

// SEARCH DRIVERS
$search = trim($_GET['search'] ?? "");

if ($search !== "") {

    $keyword = "%$search%";

    $stmt = $conn->prepare(
        "SELECT * FROM drivers
        WHERE driver_code LIKE ?
        OR name LIKE ?
        OR phone LIKE ?
        OR license_number LIKE ?
        ORDER BY driver_id DESC"
    );

    $stmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();
    $drivers = $stmt->get_result();

} else {

    $drivers = $conn->query(
        "SELECT * FROM drivers ORDER BY driver_id DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Driver Management - GTS Transport</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #073b74, #05284f);
            padding: 25px 16px;
            display: flex;
            flex-direction: column;
            box-shadow: 3px 0 12px rgba(0,0,0,0.08);
            z-index: 100;
        }

        .sidebar h2 {
            color: white;
            text-align: center;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .school {
            text-align: center;
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 35px;
        }

        .sidebar nav a {
            display: block;
            color: #e2e8f0;
            text-decoration: none;
            padding: 14px 16px;
            margin-bottom: 9px;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #f4b400;
            color: #073b74;
            font-weight: bold;
            transform: translateX(4px);
        }

        .sidebar-footer {
            color: #cbd5e1;
            text-align: center;
            font-size: 11px;
            margin-top: auto;
            line-height: 1.8;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 18px;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        /* HEADER */
        header {
            background: white;
            padding: 23px 28px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
            border-left: 5px solid #f4b400;
        }

        header h1 {
            color: #073b74;
            font-size: 25px;
            margin-bottom: 7px;
        }

        header p {
            color: #64748b;
            font-size: 14px;
        }

        .admin {
            background: #e0ecff;
            color: #073b74;
            padding: 11px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
        }

        /* FORM AND TABLE */
        .form-container,
        .table-container {
            background: white;
            padding: 28px;
            border-radius: 12px;
            margin-bottom: 28px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .form-container h2,
        .table-container h2 {
            color: #073b74;
            font-size: 20px;
            margin-bottom: 23px;
            padding-bottom: 13px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 9px;
        }

        input,
        select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            background: white;
            color: #1e293b;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        select:focus {
            border-color: #073b74;
            box-shadow: 0 0 0 3px rgba(7,59,116,0.10);
        }

        /* BUTTONS */
        .btn-primary {
            background: #073b74;
            color: white;
            border: none;
            padding: 13px 23px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #f4b400;
            color: #073b74;
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            padding: 13px 20px;
            border-radius: 7px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-cancel:hover {
            background: #475569;
        }

        /* SEARCH */
        .search-form {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            max-width: 700px;
        }

        .search-form input {
            flex: 1;
        }

        /* TABLE */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th {
            background: #073b74;
            color: white;
            padding: 15px 13px;
            text-align: left;
            font-size: 13px;
            white-space: nowrap;
        }

        td {
            padding: 14px 13px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eaf2ff;
        }

        /* STATUS */
        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        /* EDIT AND DELETE */
        .edit-btn,
        .delete-btn {
            padding: 9px 13px;
            border: none;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
        }

        .edit-btn {
            background: #15803d;
        }

        .delete-btn {
            background: #dc2626;
        }

        /* ALERTS */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        footer {
            text-align: center;
            padding: 22px;
            color: #64748b;
            font-size: 13px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .sidebar-footer {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-form {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">

    <h2>GTS TRANSPORT</h2>
    <p class="school">Management System</p>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="buses.php">Buses</a>
        <a class="active" href="drivers.php">Drivers</a>
        <a href="routes.php">Routes</a>
        <a href="assignments.php">Assignments</a>
    </nav>

    <div class="sidebar-footer">
        GIKONDO TECHNICAL<br>
        SECONDARY SCHOOL
    </div>

</div>

<div class="main-content">

    <header>
        <div>
            <h1>Driver Management</h1>
            <p>Manage school transport drivers</p>
        </div>

        <div class="admin">Administrator</div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="alert <?php echo e($messageType); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <!-- DRIVER FORM -->
    <section class="form-container">

        <h2>
            <?php echo $editDriver ? "Edit Driver" : "Add New Driver"; ?>
        </h2>

        <form method="POST" action="drivers.php" onsubmit="return validateDriverForm()">

            <input type="hidden" name="driver_id"
                value="<?php echo e($editDriver['driver_id'] ?? 0); ?>">

            <div class="form-grid">

                <div>
                    <label>Driver Code</label>
                    <input
                        type="text"
                        name="driver_code"
                        value="<?php echo e($editDriver['driver_code'] ?? ''); ?>"
                        placeholder="e.g. DR001"
                        required>
                </div>

                <div>
                    <label>Driver Name</label>
                    <input
                        type="text"
                        name="name"
                        value="<?php echo e($editDriver['name'] ?? ''); ?>"
                        placeholder="Enter full name"
                        required>
                </div>

                <div>
                    <label>Phone Number</label>
                    <input
                        type="tel"
                        name="phone"
                        value="<?php echo e($editDriver['phone'] ?? ''); ?>"
                        placeholder="e.g. 0788123456"
                        required>
                </div>

                <div>
                    <label>License Number</label>
                    <input
                        type="text"
                        name="license_number"
                        value="<?php echo e($editDriver['license_number'] ?? ''); ?>"
                        placeholder="Enter license number"
                        required>
                </div>

                <div>
                    <label>Driver Status</label>
                    <select name="status" required>
                        <option value="Active"
                            <?php echo ($editDriver['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>
                            Active
                        </option>

                        <option value="Inactive"
                            <?php echo ($editDriver['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <button type="submit" name="save_driver" class="btn-primary">
                <?php echo $editDriver ? "Update Driver" : "Save Driver"; ?>
            </button>

            <?php if ($editDriver): ?>
                <a href="drivers.php" class="btn-cancel">Cancel</a>
            <?php endif; ?>

        </form>

    </section>

    <!-- DRIVER TABLE -->
    <section class="table-container">

        <h2>All Drivers</h2>

        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                value="<?php echo e($search); ?>"
                placeholder="Search by name, code, phone or license">

            <button type="submit" class="btn-primary">Search</button>

            <a href="drivers.php" class="btn-cancel">Clear</a>

        </form>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Driver Code</th>
                        <th>Driver Name</th>
                        <th>Phone</th>
                        <th>License Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($drivers && $drivers->num_rows > 0): ?>

                    <?php while ($row = $drivers->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo e($row['driver_id']); ?></td>

                            <td><?php echo e($row['driver_code']); ?></td>

                            <td><?php echo e($row['name']); ?></td>

                            <td><?php echo e($row['phone']); ?></td>

                            <td><?php echo e($row['license_number']); ?></td>

                            <td>
                                <?php if ($row['status'] === "Active"): ?>
                                    <span class="status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    class="edit-btn"
                                    href="drivers.php?edit=<?php echo (int)$row['driver_id']; ?>">
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    style="display:inline"
                                    onsubmit="return confirm('Are you sure you want to delete this driver?')">

                                    <input
                                        type="hidden"
                                        name="driver_id"
                                        value="<?php echo (int)$row['driver_id']; ?>">

                                    <button
                                        type="submit"
                                        name="delete_driver"
                                        class="delete-btn">
                                        Delete
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" style="text-align:center;">
                            No drivers found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <footer>
        &copy; <?php echo date("Y"); ?>
        GIKONDO TECHNICAL SECONDARY SCHOOL
    </footer>

</div>

<script>
function validateDriverForm() {
    const form = document.querySelector('.form-container form');

    const name = form.querySelector('[name="name"]').value.trim();
    const phone = form.querySelector('[name="phone"]').value.trim();
    const code = form.querySelector('[name="driver_code"]').value.trim();
    const license = form.querySelector('[name="license_number"]').value.trim();

    if (!code || !name || !phone || !license) {
        alert("Please fill in all required fields.");
        return false;
    }

    if (!/^[a-zA-Z\s'-]+$/.test(name)) {
        alert("Please enter a valid driver name.");
        return false;
    }

    if (!/^[0-9+ ]{9,15}$/.test(phone)) {
        alert("Please enter a valid phone number.");
        return false;
    }

    return true;
}
</script>

</body>
</html>
