
<?php
require_once "config/db.php";
require_once "config/auth.php";
$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// DELETE STUDENT
if (isset($_POST['delete_student'])) {
    $id = (int)$_POST['student_id'];

    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $id);

    try {
        $stmt->execute();
        $message = "Student deleted successfully!";
    } catch (mysqli_sql_exception $ex) {
        $message = "Cannot delete this student because an assignment exists.";
        $messageType = "error";
    }

    $stmt->close();
}

// ADD OR UPDATE STUDENT
if (isset($_POST['save_student'])) {
    $id = (int)($_POST['student_id'] ?? 0);
    $code = trim($_POST['student_code']);
    $name = trim($_POST['name']);
    $gender = $_POST['gender'];
    $class = trim($_POST['class']);
    $phone = trim($_POST['parent_phone']);
    $address = trim($_POST['address']);
    $status = $_POST['transport_status'];

    if (
        $code === "" || $name === "" || $class === "" ||
        $phone === "" || $address === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif (!preg_match('/^[0-9+ ]{9,15}$/', $phone)) {
        $message = "Enter a valid parent phone number.";
        $messageType = "error";
    } elseif (!in_array($gender, ["Male", "Female"], true)) {
        $message = "Select a valid gender.";
        $messageType = "error";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $message = "Select a valid transport status.";
        $messageType = "error";
    } else {
        try {
            if ($id > 0) {
                $stmt = $conn->prepare(
                    "UPDATE students SET
                    student_code=?, name=?, gender=?, class=?,
                    parent_phone=?, address=?, transport_status=?
                    WHERE student_id=?"
                );

                $stmt->bind_param(
                    "sssssssi",
                    $code, $name, $gender, $class,
                    $phone, $address, $status, $id
                );

                $stmt->execute();
                $message = "Student updated successfully!";

            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO students
                    (student_code, name, gender, class,
                    parent_phone, address, transport_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssss",
                    $code, $name, $gender, $class,
                    $phone, $address, $status
                );

                $stmt->execute();
                $message = "Student added successfully!";
            }

            $stmt->close();

        } catch (mysqli_sql_exception $ex) {
            $message = "Error: Student code may already exist.";
            $messageType = "error";
        }
    }
}

// GET STUDENT FOR EDITING
$editStudent = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM students WHERE student_id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editStudent = $result->fetch_assoc();

    $stmt->close();
}

// SEARCH STUDENTS
$search = trim($_GET['search'] ?? "");

if ($search !== "") {
    $keyword = "%$search%";

    $stmt = $conn->prepare(
        "SELECT * FROM students
        WHERE student_code LIKE ?
        OR name LIKE ?
        OR class LIKE ?
        ORDER BY student_id DESC"
    );

    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    $students = $stmt->get_result();

} else {
    $students = $conn->query(
        "SELECT * FROM students ORDER BY student_id DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - GTS Transport</title>

    <script src="js/validation.js" defer></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #073b74;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 18px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
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

        nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 7px;
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
            color: #cbd5e1;
            line-height: 1.7;
            border-top: 1px solid #ffffff30;
            padding-top: 18px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        header {
            background: white;
            padding: 22px 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px #0000000d;
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
            background: #e0edff;
            color: #073b74;
            padding: 10px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        .form-container,
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px #0000000d;
        }

        .form-container h2,
        .table-container h2 {
            color: #073b74;
            margin-bottom: 22px;
            font-size: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 22px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
            color: #334155;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            outline: none;
            font-size: 14px;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #073b74;
            box-shadow: 0 0 0 2px #073b7415;
        }

        button,
        .btn-cancel {
            border: none;
            padding: 11px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #073b74;
            color: white;
        }

        .btn-primary:hover {
            background: #052b55;
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            margin-left: 5px;
        }

        .btn-cancel:hover {
            background: #475569;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            max-width: 650px;
        }

        .search-form input {
            flex: 1;
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

        th {
            background: #073b74;
            color: white;
            padding: 13px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .edit-btn {
            background: #15803d;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-right: 4px;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
            padding: 8px 12px;
        }

        .edit-btn:hover {
            background: #166534;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
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
            color: #64748b;
            padding: 20px;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
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

            .search-form button,
            .search-form .btn-cancel {
                margin-left: 0;
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
        <a class="active" href="students.php">Students</a>
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
            <h1>Student Management</h1>
            <p>Manage school transport students</p>
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
            <?php echo $editStudent ? "Edit Student" : "Add New Student"; ?>
        </h2>

        <form method="POST" action="students.php" onsubmit="return validateStudentForm()">

            <input type="hidden" name="student_id"
                value="<?php echo e($editStudent['student_id'] ?? 0); ?>">

            <div class="form-grid">

                <div>
                    <label>Student Code</label>
                    <input type="text" name="student_code"
                        value="<?php echo e($editStudent['student_code'] ?? ''); ?>"
                        placeholder="e.g. GTS001" required>
                </div>

                <div>
                    <label>Student Name</label>
                    <input type="text" name="name"
                        value="<?php echo e($editStudent['name'] ?? ''); ?>"
                        placeholder="Enter full name" required>
                </div>

                <div>
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($editStudent['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($editStudent['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div>
                    <label>Class</label>
                    <input type="text" name="class"
                        value="<?php echo e($editStudent['class'] ?? ''); ?>"
                        placeholder="e.g. L5 SOD" required>
                </div>

                <div>
                    <label>Parent Phone</label>
                    <input type="tel" name="parent_phone"
                        value="<?php echo e($editStudent['parent_phone'] ?? ''); ?>"
                        placeholder="e.g. 0788123456" required>
                </div>

                <div>
                    <label>Address</label>
                    <input type="text" name="address"
                        value="<?php echo e($editStudent['address'] ?? ''); ?>"
                        placeholder="Enter address" required>
                </div>

                <div>
                    <label>Transport Status</label>
                    <select name="transport_status" required>
                        <option value="Active" <?php echo ($editStudent['transport_status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($editStudent['transport_status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="save_student" class="btn-primary">
                <?php echo $editStudent ? "Update Student" : "Save Student"; ?>
            </button>

            <?php if ($editStudent): ?>
                <a href="students.php" class="btn-cancel">Cancel</a>
            <?php endif; ?>

        </form>
    </section>

    <section class="table-container">

        <h2>All Students</h2>

        <form method="GET" class="search-form">
            <input type="text" name="search"
                value="<?php echo e($search); ?>"
                placeholder="Search by name, code or class">

            <button type="submit" class="btn-primary">Search</button>
            <a href="students.php" class="btn-cancel">Clear</a>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Code</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Class</th>
                        <th>Parent Phone</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($students && $students->num_rows > 0): ?>

                    <?php while ($row = $students->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo e($row['student_id']); ?></td>
                        <td><?php echo e($row['student_code']); ?></td>
                        <td><?php echo e($row['name']); ?></td>
                        <td><?php echo e($row['gender']); ?></td>
                        <td><?php echo e($row['class']); ?></td>
                        <td><?php echo e($row['parent_phone']); ?></td>
                        <td><?php echo e($row['address']); ?></td>
                        <td><?php echo e($row['transport_status']); ?></td>

                        <td>
                            <a class="edit-btn"
                               href="students.php?edit=<?php echo (int)$row['student_id']; ?>">
                                Edit
                            </a>

                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('Are you sure you want to delete this student?')">

                                <input type="hidden" name="student_id"
                                    value="<?php echo (int)$row['student_id']; ?>">

                                <button type="submit"
                                    name="delete_student" class="delete-btn">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9">No students found.</td>
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

</body>
</html>
