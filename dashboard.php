
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

    return $result ? $result->fetch_assoc()["total"] : 0;
}

$students = countRecords($conn, "students", "transport_status = 'Active'");
$buses = countRecords($conn, "buses", "status = 'Active'");
$drivers = countRecords($conn, "drivers", "status = 'Active'");
$routes = countRecords($conn, "routes");
$assignments = countRecords($conn, "assignments");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - GTS Transport</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        /* SIDEBAR */
        .sidebar {
            background: #073b74;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 5px;
            font-size: 22px;
        }

        .school {
            color: #cbd5e1;
            text-align: center;
            margin-bottom: 30px;
            font-size: 13px;
        }

        .sidebar a {
            display: block;
            padding: 14px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 8px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #f4b400;
            color: #073b74;
            font-weight: bold;
        }

        .sidebar-footer {
            margin-top: auto;
            color: #cbd5e1;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        /* TOP HEADER */
        .top-header {
            background: white;
            padding: 18px 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .top-header h2 {
            margin: 0;
            color: #073b74;
        }

        .admin {
            background: #073b74;
            color: white;
            padding: 10px 18px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        /* WELCOME SECTION */
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 6px solid #f4b400;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .welcome h1 {
            color: #073b74;
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 25px;
        }

        .welcome p {
            color: #64748b;
            line-height: 1.7;
        }

        /* STATISTICS */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #f4b400;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 15px;
            margin-top: 0;
        }

        .stat-card p {
            color: #073b74;
            font-size: 34px;
            font-weight: bold;
            margin: 15px 0 0;
        }

        /* SYSTEM INFORMATION */
        .information {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            line-height: 2;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .information h2 {
            color: #073b74;
            margin-top: 0;
            border-bottom: 2px solid #f4b400;
            padding-bottom: 10px;
        }

        .information p {
            margin: 8px 0;
        }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 25px;
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
                margin-top: 20px;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .top-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .welcome h1 {
                font-size: 20px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h2>GTS TRANSPORT</h2>
    <p class="school">Management System</p>

    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
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

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="top-header">
        <div>
            <h2>Administration Dashboard</h2>
            <p>School Transport Management System</p>
        </div>

        <div class="admin">Administrator</div>
    </div>

    <!-- WELCOME -->
    <div class="welcome">
        <h1>GIKONDO TECHNICAL SECONDARY SCHOOL</h1>

        <p>
            <strong>School Transport Management System</strong>
        </p>

        <p>
            Welcome to the Administration Dashboard.
            Manage students, buses, drivers, routes and transport assignments
            from one place.
        </p>
    </div>

    <!-- STATISTICS -->
    <div class="stats">

        <div class="stat-card">
            <h3>Students Using Transport</h3>
            <p><?php echo (int)$students; ?></p>
        </div>

        <div class="stat-card">
            <h3>Active Buses</h3>
            <p><?php echo (int)$buses; ?></p>
        </div>

        <div class="stat-card">
            <h3>Active Drivers</h3>
            <p><?php echo (int)$drivers; ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Routes</h3>
            <p><?php echo (int)$routes; ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Assignments</h3>
            <p><?php echo (int)$assignments; ?></p>
        </div>

    </div>

    <!-- SYSTEM INFORMATION -->
    <div class="information">

        <h2>System Information</h2>

        <p>
            <strong>School:</strong>
            GIKONDO TECHNICAL SECONDARY SCHOOL
        </p>

        <p>
            <strong>Location:</strong>
            Nyarugenge District, Kigali, Rwanda
        </p>

        <p>
            <strong>Database:</strong>
            school_transport_db
        </p>

        <p>
            <strong>Technology:</strong>
            PHP, MySQL, HTML, CSS and JavaScript
        </p>

    </div>

    <footer>
        &copy; <?php echo date("Y"); ?>
        GIKONDO TECHNICAL SECONDARY SCHOOL.
        All Rights Reserved.
    </footer>

</div>

</body>
</html>