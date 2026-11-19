
<?php
require_once "config/db.php";
require_once "config/auth.php";
$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// DELETE ROUTE
if (isset($_POST['delete_route'])) {
    $id = (int)$_POST['route_id'];

    try {
        $stmt = $conn->prepare(
            "DELETE FROM routes WHERE route_id=?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $message = "Route deleted successfully!";

    } catch (mysqli_sql_exception $ex) {
        $message = "This route cannot be deleted because an assignment exists.";
        $messageType = "error";
    }
}

// ADD OR UPDATE ROUTE
if (isset($_POST['save_route'])) {

    $id = (int)($_POST['route_id'] ?? 0);

    $routeName = trim($_POST['route_name'] ?? "");
    $startingPoint = trim($_POST['starting_point'] ?? "");
    $destination = trim($_POST['destination'] ?? "");
    $departureTime = $_POST['departure_time'] ?? "";
    $arrivalTime = $_POST['arrival_time'] ?? "";

    if (
        $routeName === "" ||
        $startingPoint === "" ||
        $destination === "" ||
        $departureTime === "" ||
        $arrivalTime === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "error";

    } elseif ($startingPoint === $destination) {
        $message = "Starting point and destination must be different.";
        $messageType = "error";

    } elseif ($arrivalTime <= $departureTime) {
        $message = "Arrival time must be later than departure time.";
        $messageType = "error";

    } else {
        try {
            if ($id > 0) {

                $stmt = $conn->prepare(
                    "UPDATE routes SET
                    route_name=?,
                    starting_point=?,
                    destination=?,
                    departure_time=?,
                    arrival_time=?
                    WHERE route_id=?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $routeName,
                    $startingPoint,
                    $destination,
                    $departureTime,
                    $arrivalTime,
                    $id
                );

                $stmt->execute();
                $stmt->close();

                $message = "Route updated successfully!";

            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO routes
                    (route_name, starting_point, destination,
                    departure_time, arrival_time)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $routeName,
                    $startingPoint,
                    $destination,
                    $departureTime,
                    $arrivalTime
                );

                $stmt->execute();
                $stmt->close();

                $message = "Route added successfully!";
            }

        } catch (mysqli_sql_exception $ex) {
            $message = "Unable to save route. Please try again.";
            $messageType = "error";
        }
    }
}

// EDIT ROUTE
$editRoute = null;

if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM routes WHERE route_id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editRoute = $result->fetch_assoc();

    $stmt->close();
}

// VIEW ROUTES
$routes = $conn->query(
    "SELECT * FROM routes ORDER BY route_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Route Management - GTS Transport</title>

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
            transition: 0.3s ease;
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

        /* FORM AND TABLE CARDS */
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

        /* FORM GRID */
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

        input {
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

        input:focus {
            border-color: #073b74;
            box-shadow: 0 0 0 3px rgba(7,59,116,0.10);
        }

        input::placeholder {
            color: #94a3b8;
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
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            padding: 13px 20px;
            border-radius: 7px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #475569;
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
            min-width: 750px;
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
            transition: 0.3s;
        }

        .edit-btn {
            background: #15803d;
        }

        .edit-btn:hover {
            background: #166534;
        }

        .delete-btn {
            background: #dc2626;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        /* ALERTS */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
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

        /* FOOTER */
        footer {
            text-align: center;
            padding: 22px;
            color: #64748b;
            font-size: 13px;
        }

        /* RESPONSIVE DESIGN */
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
        <a href="drivers.php">Drivers</a>
        <a class="active" href="routes.php">Routes</a>
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
            <h1>Route Management</h1>
            <p>Manage school transportation routes</p>
        </div>

        <div class="admin">Administrator</div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="alert <?php echo e($messageType); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <!-- ADD OR EDIT ROUTE -->
    <section class="form-container">

        <h2>
            <?php echo $editRoute ? "Edit Route" : "Add New Route"; ?>
        </h2>

        <form method="POST" action="routes.php"
              onsubmit="return validateRouteForm()">

            <input type="hidden" name="route_id"
                value="<?php echo e($editRoute['route_id'] ?? 0); ?>">

            <div class="form-grid">

                <div>
                    <label>Route Name</label>
                    <input type="text" name="route_name"
                        placeholder="e.g. Kigali Route"
                        value="<?php echo e($editRoute['route_name'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Starting Point</label>
                    <input type="text" name="starting_point"
                        placeholder="e.g. Nyabugogo"
                        value="<?php echo e($editRoute['starting_point'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Destination</label>
                    <input type="text" name="destination"
                        placeholder="e.g. GTS School"
                        value="<?php echo e($editRoute['destination'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Departure Time</label>
                    <input type="time" name="departure_time"
                        value="<?php echo e($editRoute['departure_time'] ?? ''); ?>"
                        required>
                </div>

                <div>
                    <label>Arrival Time</label>
                    <input type="time" name="arrival_time"
                        value="<?php echo e($editRoute['arrival_time'] ?? ''); ?>"
                        required>
                </div>

            </div>

            <button type="submit" name="save_route" class="btn-primary">
                <?php echo $editRoute ? "Update Route" : "Save Route"; ?>
            </button>

            <?php if ($editRoute): ?>
                <a href="routes.php" class="btn-cancel">Cancel</a>
            <?php endif; ?>

        </form>

    </section>

    <!-- REGISTERED ROUTES -->
    <section class="table-container">

        <h2>Registered Routes</h2>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Route Name</th>
                        <th>Starting Point</th>
                        <th>Destination</th>
                        <th>Departure Time</th>
                        <th>Arrival Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($routes && $routes->num_rows > 0): ?>

                    <?php while ($row = $routes->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo e($row['route_id']); ?></td>
                        <td><?php echo e($row['route_name']); ?></td>
                        <td><?php echo e($row['starting_point']); ?></td>
                        <td><?php echo e($row['destination']); ?></td>
                        <td><?php echo e($row['departure_time']); ?></td>
                        <td><?php echo e($row['arrival_time']); ?></td>

                        <td>
                            <a class="edit-btn"
                                href="routes.php?edit=<?php echo (int)$row['route_id']; ?>">
                                Edit
                            </a>

                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('Delete this route?')">

                                <input type="hidden" name="route_id"
                                    value="<?php echo (int)$row['route_id']; ?>">

                                <button type="submit"
                                    name="delete_route" class="delete-btn">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" style="text-align:center;">
                            No routes registered yet.
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
function validateRouteForm() {
    const form = document.querySelector('.form-container form');

    const routeName = form.querySelector('[name="route_name"]').value.trim();
    const startingPoint = form.querySelector('[name="starting_point"]').value.trim();
    const destination = form.querySelector('[name="destination"]').value.trim();
    const departure = form.querySelector('[name="departure_time"]').value;
    const arrival = form.querySelector('[name="arrival_time"]').value;

    if (!routeName || !startingPoint || !destination || !departure || !arrival) {
        alert("Please fill in all required fields.");
        return false;
    }

    if (startingPoint.toLowerCase() === destination.toLowerCase()) {
        alert("Starting point and destination must be different.");
        return false;
    }

    if (arrival <= departure) {
        alert("Arrival time must be later than departure time.");
        return false;
    }

    return true;
}
</script>

</body>
</html>
