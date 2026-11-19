
<?php
require_once "config/db.php";
require_once "config/auth.php";
function countRecords($conn, $table, $condition = "") {
    $allowed = ["students", "buses", "drivers", "routes", "assignments"];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$table`";

    if ($condition !== "") {
        $sql .= " WHERE $condition";
    }

    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    return $result->fetch_assoc()["total"];
}

$students = countRecords($conn, "students", "transport_status = 'Active'");
$buses = countRecords($conn, "buses");
$drivers = countRecords($conn, "drivers", "status = 'Active'");
$routes = countRecords($conn, "routes");
$assignments = countRecords($conn, "assignments");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GTS - School Transport Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="sidebar">
    <h2>GTS TRANSPORT</h2>
    <p class="school">Management System</p>

    <nav>
        <a class="active" href="index.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="buses.php">Buses</a>
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
            <h1>Dashboard</h1>
            <p>Welcome to School Transport Management System</p>
        </div>
        <div class="admin">Administrator</div>
    </header>

    <section class="welcome">
        <h2>GIKONDO TECHNICAL SECONDARY SCHOOL</h2>
        <p>Nyarugenge District, Kigali City, Rwanda</p>
        <p>Manage students, buses, drivers and transport routes efficiently.</p>
    </section>

    <h2 class="section-title">Transport Overview</h2>

    <section class="stats">

        <div class="stat-card">
            <h3>Students Using Transport</h3>
            <p><?php echo $students; ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Buses</h3>
            <p><?php echo $buses; ?></p>
        </div>

        <div class="stat-card">
            <h3>Active Drivers</h3>
            <p><?php echo $drivers; ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Routes</h3>
            <p><?php echo $routes; ?></p>
        </div>

        <div class="stat-card">
            <h3>Assignments</h3>
            <p><?php echo $assignments; ?></p>
        </div>

    </section>

    <section class="information">
        <h2>System Information</h2>
        <p>
            This system helps GTS manage student transportation,
            bus registration, driver records, routes and assignments.
        </p>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?>
        GIKONDO TECHNICAL SECONDARY SCHOOL.
        All Rights Reserved.
    </footer>

</div>

</body>
</html>
