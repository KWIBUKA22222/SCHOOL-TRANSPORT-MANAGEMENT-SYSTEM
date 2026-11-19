
<?php
require_once "config/db.php";
require_once "config/auth.php";
$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// DELETE ASSIGNMENT
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: assignments.php?deleted=1");
        exit;
    } else {
        $message = "Assignment could not be deleted.";
        $messageType = "error";
    }
    $stmt->close();
}

if (isset($_GET["deleted"])) {
    $message = "Assignment deleted successfully!";
}

// ADD OR UPDATE ASSIGNMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_assignment"])) {

    $assignment_id = (int)($_POST["assignment_id"] ?? 0);
    $student_id = (int)($_POST["student_id"] ?? 0);
    $bus_id = (int)($_POST["bus_id"] ?? 0);
    $route_id = (int)($_POST["route_id"] ?? 0);
    $pickup_point = trim($_POST["pickup_point"] ?? "");
    $assignment_date = trim($_POST["assignment_date"] ?? "");

    if (
        $student_id <= 0 ||
        $bus_id <= 0 ||
        $route_id <= 0 ||
        $pickup_point === "" ||
        $assignment_date === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        $dateObject = DateTime::createFromFormat("Y-m-d", $assignment_date);
        $validDate = $dateObject && $dateObject->format("Y-m-d") === $assignment_date;

        $studentCheck = $conn->prepare(
            "SELECT student_id FROM students WHERE student_id = ?"
        );
        $studentCheck->bind_param("i", $student_id);
        $studentCheck->execute();
        $studentExists = $studentCheck->get_result()->num_rows > 0;
        $studentCheck->close();

        $busCheck = $conn->prepare(
            "SELECT bus_id FROM buses WHERE bus_id = ?"
        );
        $busCheck->bind_param("i", $bus_id);
        $busCheck->execute();
        $busExists = $busCheck->get_result()->num_rows > 0;
        $busCheck->close();

        $routeCheck = $conn->prepare(
            "SELECT route_id FROM routes WHERE route_id = ?"
        );
        $routeCheck->bind_param("i", $route_id);
        $routeCheck->execute();
        $routeExists = $routeCheck->get_result()->num_rows > 0;
        $routeCheck->close();

        if (!$validDate) {
            $message = "Please enter a valid assignment date.";
            $messageType = "error";
        } elseif (!$studentExists || !$busExists || !$routeExists) {
            $message = "The selected student, bus or route does not exist.";
            $messageType = "error";
        } else {
            if ($assignment_id > 0) {
                $stmt = $conn->prepare(
                    "UPDATE assignments
                     SET student_id = ?, bus_id = ?, route_id = ?,
                         pickup_point = ?, assignment_date = ?
                     WHERE assignment_id = ?"
                );

                $stmt->bind_param(
                    "iiissi",
                    $student_id,
                    $bus_id,
                    $route_id,
                    $pickup_point,
                    $assignment_date,
                    $assignment_id
                );
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO assignments
                     (student_id, bus_id, route_id, pickup_point, assignment_date)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiiss",
                    $student_id,
                    $bus_id,
                    $route_id,
                    $pickup_point,
                    $assignment_date
                );
            }

            if ($stmt->execute()) {
                header("Location: assignments.php?saved=1");
                exit;
            } else {
                $message = "Error saving assignment: " . $stmt->error;
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

if (isset($_GET["saved"])) {
    $message = "Assignment saved successfully!";
}

// GET ASSIGNMENT FOR EDITING
$editAssignment = null;

if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM assignments WHERE assignment_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editAssignment = $result->fetch_assoc();

    $stmt->close();
}

// GET STUDENTS
$students = $conn->query(
    "SELECT student_id, student_code, name
     FROM students ORDER BY name ASC"
);

// GET BUSES
$buses = $conn->query(
    "SELECT bus_id, bus_number, capacity
     FROM buses ORDER BY bus_number ASC"
);

// GET ROUTES
$routes = $conn->query(
    "SELECT route_id, route_name, starting_point, destination
     FROM routes ORDER BY route_name ASC"
);

// GET ALL ASSIGNMENTS
$assignments = $conn->query(
    "SELECT
        a.assignment_id,
        a.pickup_point,
        a.assignment_date,
        s.student_code,
        s.name AS student_name,
        b.bus_number,
        r.route_name,
        r.starting_point,
        r.destination
     FROM assignments a
     INNER JOIN students s ON a.student_id = s.student_id
     INNER JOIN buses b ON a.bus_id = b.bus_id
     INNER JOIN routes r ON a.route_id = r.route_id
     ORDER BY a.assignment_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assignments - GTS Transport</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #073b74, #05284f);
    position: fixed;
    top: 0;
    left: 0;
    padding: 25px 15px;
    color: white;
}

.sidebar h2 {
    text-align: center;
    color: #f4b400;
    font-size: 21px;
    margin-bottom: 35px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px 16px;
    margin: 7px 0;
    border-radius: 7px;
    transition: 0.3s;
}

.sidebar a:hover,
.sidebar a.active {
    background: #f4b400;
    color: #073b74;
    font-weight: bold;
}

.main-content {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    border-left: 5px solid #f4b400;
}

.welcome h1 {
    color: #073b74;
    margin-bottom: 8px;
}

.welcome p {
    color: #64748b;
}

.form-container,
.table-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
}

h2 {
    color: #073b74;
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #334155;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 15px;
    background: white;
}

input:focus,
select:focus {
    outline: none;
    border-color: #073b74;
    box-shadow: 0 0 0 3px rgba(7,59,116,0.1);
}

button,
.btn-primary {
    display: inline-block;
    background: #073b74;
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
}

button:hover,
.btn-primary:hover {
    background: #05284f;
}

.btn-cancel {
    display: inline-block;
    background: #64748b;
    color: white;
    padding: 12px 20px;
    border-radius: 7px;
    text-decoration: none;
    margin-left: 8px;
}

.btn-cancel:hover {
    background: #475569;
}

.alert {
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

thead {
    background: #073b74;
    color: white;
}

th,
td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

tbody tr:hover {
    background: #f8fafc;
}

.edit-btn,
.delete-btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-size: 13px;
    margin: 2px;
}

.edit-btn {
    background: #15803d;
}

.delete-btn {
    background: #dc2626;
}

.edit-btn:hover {
    background: #166534;
}

.delete-btn:hover {
    background: #991b1b;
}

footer {
    text-align: center;
    color: #64748b;
    padding: 20px;
    margin-top: 20px;
}

@media(max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .sidebar h2 {
        margin-bottom: 15px;
    }

    .main-content {
        margin-left: 0;
        padding: 15px;
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

    <a href="dashboard.php">Dashboard</a>
    <a href="students.php">Students</a>
    <a href="buses.php">Buses</a>
    <a href="drivers.php">Drivers</a>
    <a href="routes.php">Routes</a>
    <a href="assignments.php" class="active">Assignments</a>
</div>

<div class="main-content">

    <div class="welcome">
        <h1>Student Transport Assignments</h1>
        <p>Manage students, buses and transport routes.</p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert <?= e($messageType) ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <h2>
            <?= $editAssignment ? "Edit Assignment" : "Add New Assignment" ?>
        </h2>

        <form method="POST" action="assignments.php"
              onsubmit="return validateAssignmentForm();">

            <input type="hidden" name="assignment_id"
                   value="<?= e($editAssignment["assignment_id"] ?? 0) ?>">

            <div class="form-grid">

                <div>
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>

                        <?php if ($students): ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <option value="<?= e($student["student_id"]) ?>"
                                    <?= (
                                        isset($editAssignment["student_id"]) &&
                                        $editAssignment["student_id"] == $student["student_id"]
                                    ) ? "selected" : "" ?>>
                                    <?= e($student["student_code"]) ?> -
                                    <?= e($student["name"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label>Select Bus</label>
                    <select name="bus_id" required>
                        <option value="">-- Select Bus --</option>

                        <?php if ($buses): ?>
                            <?php while ($bus = $buses->fetch_assoc()): ?>
                                <option value="<?= e($bus["bus_id"]) ?>"
                                    <?= (
                                        isset($editAssignment["bus_id"]) &&
                                        $editAssignment["bus_id"] == $bus["bus_id"]
                                    ) ? "selected" : "" ?>>
                                    <?= e($bus["bus_number"]) ?> -
                                    Capacity: <?= e($bus["capacity"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label>Select Route</label>
                    <select name="route_id" required>
                        <option value="">-- Select Route --</option>

                        <?php if ($routes): ?>
                            <?php while ($route = $routes->fetch_assoc()): ?>
                                <option value="<?= e($route["route_id"]) ?>"
                                    <?= (
                                        isset($editAssignment["route_id"]) &&
                                        $editAssignment["route_id"] == $route["route_id"]
                                    ) ? "selected" : "" ?>>
                                    <?= e($route["route_name"]) ?>
                                    (<?= e($route["starting_point"]) ?> -
                                    <?= e($route["destination"]) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label>Pickup Point</label>
                    <input type="text" name="pickup_point"
                           placeholder="Enter pickup point"
                           value="<?= e($editAssignment["pickup_point"] ?? "") ?>"
                           required>
                </div>

                <div>
                    <label>Assignment Date</label>
                    <input type="date" name="assignment_date"
                           value="<?= e($editAssignment["assignment_date"] ?? date("Y-m-d")) ?>"
                           required>
                </div>

            </div>

            <button type="submit" name="save_assignment">
                <?= $editAssignment ? "Update Assignment" : "Save Assignment" ?>
            </button>

            <?php if ($editAssignment): ?>
                <a href="assignments.php" class="btn-cancel">Cancel</a>
            <?php endif; ?>

        </form>
    </div>

    <div class="table-container">
        <h2>All Transport Assignments</h2>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Code</th>
                        <th>Student Name</th>
                        <th>Bus Number</th>
                        <th>Route</th>
                        <th>Pickup Point</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($assignments && $assignments->num_rows > 0): ?>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($assignment["assignment_id"]) ?></td>
                                <td><?= e($assignment["student_code"]) ?></td>
                                <td><?= e($assignment["student_name"]) ?></td>
                                <td><?= e($assignment["bus_number"]) ?></td>
                                <td>
                                    <?= e($assignment["route_name"]) ?>
                                    (<?= e($assignment["starting_point"]) ?> -
                                    <?= e($assignment["destination"]) ?>)
                                </td>
                                <td><?= e($assignment["pickup_point"]) ?></td>
                                <td><?= e($assignment["assignment_date"]) ?></td>
                                <td>
                                    <a href="assignments.php?edit=<?= e($assignment["assignment_id"]) ?>"
                                       class="edit-btn">Edit</a>

                                    <a href="assignments.php?delete=<?= e($assignment["assignment_id"]) ?>"
                                       class="delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this assignment?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No assignments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>
            &copy; <?= date("Y") ?>
            GIKONDO TECHNICAL SECONDARY SCHOOL. All Rights Reserved.
        </p>
    </footer>

</div>

<script>
function validateAssignmentForm() {
    const form = document.querySelector("form");
    const student = form.querySelector('[name="student_id"]').value;
    const bus = form.querySelector('[name="bus_id"]').value;
    const route = form.querySelector('[name="route_id"]').value;
    const pickup = form.querySelector('[name="pickup_point"]').value.trim();
    const date = form.querySelector('[name="assignment_date"]').value;

    if (!student || !bus || !route || !pickup || !date) {
        alert("Please fill in all required fields.");
        return false;
    }

    return true;
}
</script>

</body>
</html>
