
<?php
require_once "config/db.php";
require_once "config/auth.php";
$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// DELETE BUS
if (isset($_POST['delete_bus'])) {
    $id = (int)$_POST['bus_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM buses WHERE bus_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $message = "Bus deleted successfully!";
    } catch (mysqli_sql_exception $ex) {
        $message = "This bus cannot be deleted because it has an assignment.";
        $messageType = "error";
    }
}

// ADD OR UPDATE BUS
if (isset($_POST['save_bus'])) {

    $id = (int)($_POST['bus_id'] ?? 0);
    $number = trim($_POST['bus_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $model = trim($_POST['model'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($number === "" || $model === "") {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif ($capacity <= 0) {
        $message = "Bus capacity must be a positive number.";
        $messageType = "error";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $message = "Please select a valid bus status.";
        $messageType = "error";
    } else {

        try {
            if ($id > 0) {

                $stmt = $conn->prepare(
                    "UPDATE buses SET bus_number=?, capacity=?,
                    model=?, status=? WHERE bus_id=?"
                );

                $stmt->bind_param(
                    "sissi",
                    $number, $capacity, $model, $status, $id
                );

                $stmt->execute();
                $stmt->close();

                $message = "Bus updated successfully!";

            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO buses
                    (bus_number, capacity, model, status)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "siss",
                    $number, $capacity, $model, $status
                );

                $stmt->execute();
                $stmt->close();

                $message = "Bus registered successfully!";
            }

        } catch (mysqli_sql_exception $ex) {
            $message = "Error: Bus number may already exist.";
            $messageType = "error";
        }
    }
}

// EDIT BUS
$editBus = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM buses WHERE bus_id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editBus = $result->fetch_assoc();

    $stmt->close();
}

// VIEW BUSES
$buses = $conn->query(
    "SELECT * FROM buses ORDER BY bus_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bus Management - GTS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #073b74;
            color: white;
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 5px;
            font-size: 22px;
        }

        .school {
            text-align: center;
            color: #cbd5e1;
            margin-top: 5px;
            margin-bottom: 30px;
        }

        nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 15px;
            margin: 6px 0;
            border-radius: 6px;
            transition: 0.3s;
        }

        nav a:hover,
        nav a.active {
            background: #f4b400;
            color: #073b74;
            font-weight: bold;
        }

        .sidebar-footer {
            margin-top: auto;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
            color: #cbd5e1;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        header h1 {
            margin: 0;
            color: #073b74;
        }

        header p {
            margin-bottom: 0;
            color: #64748b;
        }

        .admin {
            background: #073b74;
            color: white;
            padding: 10px 18px;
            border-radius: 20px;
            font-weight: bold;
        }

        .form-container,
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .form-container h2,
        .table-container h2 {
            color: #073b74;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #073b74;
            box-shadow: 0 0 4px rgba(7,59,116,0.25);
        }

        button,
        .btn-primary,
        .btn-cancel,
        .edit-btn,
        .delete-btn {
            display: inline-block;
            border: none;
            padding: 10px 16px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-primary {
            background: #073b74;
            color: white;
        }

        .btn-primary:hover {
            background: #052c57;
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            margin-left: 8px;
        }

        .edit-btn {
            background: #15803d;
            color: white;
            margin-right: 5px;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        .edit-btn:hover,
        .delete-btn:hover,
        .btn-cancel:hover {
            opacity: 0.85;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #073b74;
            color: white;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .alert {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .sidebar-footer {
                margin-top: 20px;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
        <a class="active" href="buses.php">Buses</a>
        <a href="drivers.php">Drivers</a>
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
            <h1>Bus Management</h1>
            <p>Manage school transportation buses</p>
        </div>

        <div class="admin">Administrator</div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="alert <?php echo e($messageType); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <section class="form-container">

        <h2>
            <?php echo $editBus ? "Edit Bus" : "Register New Bus"; ?>
        </h2>

        <form method="POST" action="buses.php"
              onsubmit="return validateBusForm()">

            <input type="hidden" name="bus_id"
                value="<?php echo e($editBus['bus_id'] ?? 0); ?>">

            <div class="form-grid">

                <div>
                    <label>Bus Number</label>
                    <input type="text" name="bus_number"
                        placeholder="e.g. RAD 123A"
                        value="<?php echo e($editBus['bus_number'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Bus Capacity</label>
                    <input type="number" name="capacity"
                        min="1"
                        placeholder="e.g. 30"
                        value="<?php echo e($editBus['capacity'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Bus Model</label>
                    <input type="text" name="model"
                        placeholder="e.g. Toyota Coaster"
                        value="<?php echo e($editBus['model'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Bus Status</label>
                    <select name="status" required>
                        <option value="Active"
                            <?php echo ($editBus['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>
                            Active
                        </option>

                        <option value="Inactive"
                            <?php echo ($editBus['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <button type="submit" name="save_bus" class="btn-primary">
                <?php echo $editBus ? "Update Bus" : "Save Bus"; ?>
            </button>

            <?php if ($editBus): ?>
                <a href="buses.php" class="btn-cancel">Cancel</a>
            <?php endif; ?>

        </form>

    </section>

    <section class="table-container">

        <h2>Registered Buses</h2>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bus Number</th>
                        <th>Capacity</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($buses && $buses->num_rows > 0): ?>

                    <?php while ($row = $buses->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo e($row['bus_id']); ?></td>
                        <td><?php echo e($row['bus_number']); ?></td>
                        <td><?php echo e($row['capacity']); ?> Students</td>
                        <td><?php echo e($row['model']); ?></td>
                        <td><?php echo e($row['status']); ?></td>

                        <td>
                            <a class="edit-btn"
                               href="buses.php?edit=<?php echo (int)$row['bus_id']; ?>">
                                Edit
                            </a>

                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('Delete this bus?')">

                                <input type="hidden" name="bus_id"
                                    value="<?php echo (int)$row['bus_id']; ?>">

                                <button type="submit"
                                    name="delete_bus" class="delete-btn">
                                    Delete
                                </button>

                            </form>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">No buses registered yet.</td>
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
    function validateBusForm() {
        const capacity = document.querySelector('input[name="capacity"]').value;

        if (Number(capacity) <= 0) {
            alert("Bus capacity must be greater than zero.");
            return false;
        }

        return true;
    }
</script>

</body>
</html>
