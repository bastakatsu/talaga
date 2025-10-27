<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../admin_db.php";

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// preserve session admin id separately so it won't be overwritten when loading a subject for edit
$session_admin_id = $_SESSION["id"];

/**
 * Return classes handled by an admin from admin_classes table.
 * Each element: ['class_id' => int, 'class_name' => string]
 */
function getClassesByAdmin($mysqli, int $adminId): array {
    $sql = "SELECT DISTINCT c.class_id, CONCAT(c.level, ' ', c.section) AS class_name
            FROM classes c
            JOIN admin_classes ac ON ac.class_id = c.class_id
            WHERE ac.admin_id = ?
            ORDER BY c.level, c.section";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    $classes = [];
    while ($r = $res->fetch_assoc()) {
        $classes[] = $r;
    }
    $stmt->close();
    return $classes;
}

// Quick AJAX endpoint: return classes handled by a given admin (based on admin_classes table)
if (isset($_GET['action']) && $_GET['action'] === 'get_classes' && isset($_GET['admin_id'])) {
    $adminId = (int)$_GET['admin_id'];
    header('Content-Type: application/json');
    echo json_encode(getClassesByAdmin($mysqli, $adminId));
    exit;
}

$admin_id = $session_admin_id; // legacy variable kept for compatibility with older code

// Fetch all classes (full list, used if needed elsewhere)
$all_classes_query = $mysqli->query("SELECT class_id, CONCAT(level, ' ', section) AS class_name FROM classes ORDER BY level, section");
$all_classes = [];
while ($row = $all_classes_query->fetch_assoc()) {
    $all_classes[$row['class_id']] = $row['class_name'];
}

// Fetch all admins for assignment
$admins_query = $mysqli->query("SELECT admin_id, admin_name FROM admins ORDER BY admin_name");
$all_admins = [];
while ($row = $admins_query->fetch_assoc()) {
    $all_admins[$row['admin_id']] = $row['admin_name'];
}

// Handle form submission for saving subject
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_id = isset($_POST['subject_id']) ? $_POST['subject_id'] : '';
    $assigned_admin_id = isset($_POST['assigned_admin']) ? (int)$_POST['assigned_admin'] : 0;

    // Determine subject name
    if (isset($_POST['subject']) && $_POST['subject'] === 'new') {
        $subject = trim($_POST['new_subject']);
    } else {
        $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    }

    // Note: form uses class_id to match server-side variable
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

    // Validate admin exists
    if (!array_key_exists($assigned_admin_id, $all_admins)) {
        echo "<script>alert('Invalid Admin! Please select a valid admin.');</script>";
    } else {
        // Validate that the chosen admin handles the chosen class (check admin_classes table)
        $chk = $mysqli->prepare("SELECT 1 FROM admin_classes WHERE admin_id = ? AND class_id = ? LIMIT 1");
        $chk->bind_param("ii", $assigned_admin_id, $class_id);
        $chk->execute();
        $chk_res = $chk->get_result();
        $chk->close();

        if ($chk_res->num_rows == 0) {
            echo "<script>alert('Selected admin does not handle the selected class. Please choose a valid class for the chosen admin.');</script>";
        } else {
            // Check if subject already exists in this class (when creating)
            $subject_check_query = $mysqli->prepare("SELECT * FROM subjects WHERE subject = ? AND class_id = ?");
            $subject_check_query->bind_param("si", $subject, $class_id);
            $subject_check_query->execute();
            $subject_check_result = $subject_check_query->get_result();

            if ($subject_check_result->num_rows > 0 && !$subject_id) {
                echo "<script>alert('This subject is already assigned to the selected class.');</script>";
            } else {
                // Generate unique subject code
                $code_query = $mysqli->prepare("SELECT subject_code FROM subjects WHERE subject = ? ORDER BY subject_code DESC LIMIT 1");
                $code_query->bind_param("s", $subject);
                $code_query->execute();
                $result = $code_query->get_result();

                $new_subject_code = "";
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $last_code = $row['subject_code'];
                    if (preg_match('/^([^\d]+)?(\d+)$/', $last_code, $matches)) {
                        $prefix = $matches[1] ?? $subject;
                        $last_number = (int)($matches[2] ?? 100);
                        $new_subject_code = $prefix . ($last_number + 1);
                    } else {
                        $new_subject_code = $subject . '101';
                    }
                } else {
                    $new_subject_code = $subject . '101';
                }
                $code_query->close();

                // Ensure unique subject code
                $check_code_query = $mysqli->prepare("SELECT subject_code FROM subjects WHERE subject_code = ?");
                $check_code_query->bind_param("s", $new_subject_code);
                $safety = 0;
                while (true) {
                    $check_code_query->execute();
                    $check_result = $check_code_query->get_result();
                    if ($check_result->num_rows == 0) break;
                    if (preg_match('/^([^\d]+)?(\d+)$/', $new_subject_code, $m)) {
                        $p = $m[1] ?? $subject;
                        $n = (int)($m[2] ?? 100);
                        $n++;
                        $new_subject_code = $p . $n;
                    } else {
                        $new_subject_code .= '1';
                    }
                    $safety++;
                    if ($safety > 1000) break;
                }
                $check_code_query->close();

                if ($subject_id) {
                    // Update existing subject
                    $stmt = $mysqli->prepare("UPDATE subjects SET subject_code=?, subject=?, class_id=?, admin_id=? WHERE subject_id=?");
                    $stmt->bind_param("ssiii", $new_subject_code, $subject, $class_id, $assigned_admin_id, $subject_id);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Insert new subject
                    $stmt = $mysqli->prepare("INSERT INTO subjects (subject_code, subject, class_id, admin_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $new_subject_code, $subject, $class_id, $assigned_admin_id);
                    $stmt->execute();
                    $inserted_subject_id = $stmt->insert_id;
                    $stmt->close();

                    // Enroll all students in this class to the new subject
                    $students_query = $mysqli->prepare("SELECT student_id FROM students WHERE class_id = ?");
                    $students_query->bind_param("i", $class_id);
                    $students_query->execute();
                    $students_result = $students_query->get_result();

                    while ($student = $students_result->fetch_assoc()) {
                        $insert_subject_query = $mysqli->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
                        $insert_subject_query->bind_param("ii", $student['student_id'], $inserted_subject_id);
                        $insert_subject_query->execute();
                        $insert_subject_query->close();
                    }
                    $students_query->close();
                }

                $_SESSION['message'] = "Subject successfully created!";
                $_SESSION['msg_type'] = "success";
                header("Location: Managesubject.php?success=1");
                exit();
            }
            $subject_check_query->close();
        }
    }
}

// Fetch subject for editing
$subject_id = isset($_GET['id']) ? $_GET['id'] : '';
$subject_admin_for_edit = null;
$subject_class_for_edit = null;
if ($subject_id) {
    $stmt = $mysqli->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($qry = $result->fetch_assoc()) {
        foreach ($qry as $k => $v) {
            $$k = $v; // may set $admin_id and $class_id from subjects row
        }
        // Capture the admin/class from the loaded subject for use in JS
        $subject_admin_for_edit = isset($admin_id) ? (int)$admin_id : null;
        $subject_class_for_edit = isset($class_id) ? (int)$class_id : null;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Subject | AFK</title>
    <link rel="icon" href="/admin/assets/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="assets/css/mngsub.css" rel="stylesheet">
</head>

<body class="starter-page-page">
<?php require_once 'slidebar.php'; ?>

<main class="main">
    <div class="page-title" data-aos="fade">
        <div class="container">
            <h1>Manage Subject</h1>
        </div>
    </div>

    <div class="container section-title" data-aos="fade-up">
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos(strtolower($message), 'success') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="class-card">
            <h3>Select Subject and Assign Admin</h3>
            <form action="" method="POST">
                <input type="hidden" name="subject_id" value="<?php echo isset($subject_id) ? htmlspecialchars($subject_id) : ''; ?>">

                <div class="form-row">
                    <!-- Subject Selection -->
                    <div class="form-section">
                        <label for="subject">Subject Name</label>
                        <div class="dropdown-wrapper">
                            <select class="form-control" name="subject" id="subject" required>
                                <option value="" disabled <?php echo !isset($subject) ? 'selected' : ''; ?>>-- Select Subject --</option>
                                <?php
                                $subject_qry = $mysqli->query("SELECT subject_id, subject FROM subjects GROUP BY subject");
                                while ($row = $subject_qry->fetch_assoc()):
                                ?>
                                    <option value="<?php echo htmlspecialchars($row['subject']); ?>" <?php echo isset($subject) && $subject == $row['subject'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['subject']); ?>
                                    </option>
                                <?php endwhile; ?>
                                <option value="new" <?php echo (isset($subject) && $subject === 'new') ? 'selected' : ''; ?>>Add New Subject</option>
                            </select>
                            <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <!-- New Subject Input -->
                    <div class="form-section" id="new-subject-container" style="display: none;">
                        <label for="new-subject">New Subject Name</label>
                        <input type="text" class="form-control" name="new_subject" id="new-subject" placeholder="Enter new subject name" maxlength="40">
                    </div>

                    <!-- Admin Assignment (choose admin first) -->
                    <div class="form-section">
                        <label for="assigned_admin">Assign Admin</label>
                        <div class="dropdown-wrapper">
                            <select class="form-control" name="assigned_admin" id="assigned_admin" required onchange="onAdminChange(this.value)">
                                <option value="" disabled <?php echo !isset($subject_admin_for_edit) ? 'selected' : ''; ?>>--- Select Admin ---</option>
                                <?php foreach ($all_admins as $id => $name): ?>
                                    <option value="<?php echo (int)$id; ?>" <?php echo (isset($subject_admin_for_edit) && $subject_admin_for_edit == $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <!-- Class Selection (populated from admin_classes for chosen admin) -->
                    <div class="form-section">
                        <label for="class_id">Class</label>
                        <div class="dropdown-wrapper">
                            <select class="form-control" name="class_id" id="select-class" required disabled>
                                <option value="" disabled selected>--- Choose an admin first ---</option>
                                <!-- Options will be populated via JS when an admin is chosen -->
                            </select>
                            <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-save">Save</button>
            </form>
        </div>
    </div>
</main>

<script>
    // Toggle visibility of new subject input
    var subjectSelect = document.querySelector('select[name="subject"]');
    function toggleNewSubject() {
        var newSubjectContainer = document.getElementById('new-subject-container');
        if (!subjectSelect) return;
        newSubjectContainer.style.display = subjectSelect.value === 'new' ? 'block' : 'none';
    }
    if (subjectSelect) {
        subjectSelect.addEventListener('change', toggleNewSubject);
        toggleNewSubject();
    }

    // Update class dropdown based on selected admin (AJAX fetch)
    async function onAdminChange(adminId, preselectClassId = null) {
        var classSelect = document.getElementById('select-class');
        // clear existing options
        classSelect.innerHTML = '';
        if (!adminId) {
            classSelect.disabled = true;
            classSelect.innerHTML = '<option value=\"\" disabled selected>--- Choose an admin first ---</option>';
            return;
        }
        classSelect.disabled = true;
        // show a loading option
        var loadingOption = document.createElement('option');
        loadingOption.text = 'Loading classes...';
        loadingOption.disabled = true;
        loadingOption.selected = true;
        classSelect.appendChild(loadingOption);

        try {
            var resp = await fetch(window.location.pathname + '?action=get_classes&admin_id=' + encodeURIComponent(adminId));
            if (!resp.ok) throw new Error('Network response was not ok');
            var data = await resp.json();
            classSelect.innerHTML = ''; // clear loading option
            if (Array.isArray(data) && data.length > 0) {
                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.text = '--- Select Class ---';
                placeholder.disabled = true;
                placeholder.selected = true;
                classSelect.appendChild(placeholder);

                data.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.class_id;
                    opt.text = item.class_name;
                    if (preselectClassId && parseInt(preselectClassId) === parseInt(item.class_id)) {
                        opt.selected = true;
                    }
                    classSelect.appendChild(opt);
                });
                classSelect.disabled = false;
            } else {
                var noOpt = document.createElement('option');
                noOpt.value = '';
                noOpt.text = 'No classes found for this admin';
                noOpt.disabled = true;
                noOpt.selected = true;
                classSelect.appendChild(noOpt);
                classSelect.disabled = true;
            }
        } catch (err) {
            classSelect.innerHTML = '<option value=\"\" disabled selected>Error loading classes</option>';
            classSelect.disabled = true;
            console.error(err);
        }
    }

    // If editing existing subject, pre-populate class dropdown for the subject's assigned admin
    (function() {
        var presetAdmin = <?php echo isset($subject_admin_for_edit) ? (int)$subject_admin_for_edit : 'null'; ?>;
        var presetClass = <?php echo isset($subject_class_for_edit) ? (int)$subject_class_for_edit : 'null'; ?>;
        if (presetAdmin) {
            // ensure the admin select has the preset selected (it should from server-side),
            // then fetch classes and preselect the preset class
            onAdminChange(presetAdmin, presetClass);
        }
    })();

    // Auto-hide alerts
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    }
</script>
</body>
</html>