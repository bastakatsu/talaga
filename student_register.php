<?php
// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Your registration code here
require "../admin_db.php";
require_once "../mailer.php"; // PHPMailer

$show_modal = false;
$student_number = "";

// Password generator
function generateSecurePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*-_';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

$default_password = generateSecurePassword();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register_student'])) {
    $first_name = ucfirst(trim($_POST['first_name']));
    $last_name = ucfirst(trim($_POST['last_name']));
    $email = strtolower(trim($_POST['email']));
    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : null;
    $profile_pic = null;

    // Validation
    if (!preg_match("/^[A-Za-z]{3,}$/", $first_name) || !preg_match("/^[A-Za-z]{3,}$/", $last_name)) {
        $_SESSION['registration_error'] = "Names must be at least 3 alphabetic characters.";
        header("Location: student_list.php");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/", $email)) {
        $_SESSION['registration_error'] = "Email must be a valid Gmail address.";
        header("Location: student_list.php");
        exit;
    }
    if (!$class_id) {
        $_SESSION['registration_error'] = "Select a valid class.";
        header("Location: student_list.php");
        exit;
    }
    //full name checker to 
$stmt = $mysqli->prepare("SELECT student_id FROM students WHERE student_fn = ? AND student_ln = ?");
$stmt->bind_param("ss", $first_name, $last_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['registration_error'] = "A student with this full name already exists.";
    $stmt->close();
    header("Location: student_list.php");
    exit;
}
$stmt->close();
    // Check if email exists
    $stmt = $mysqli->prepare("SELECT student_id FROM students WHERE student_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['registration_error'] = "Email already registered.";
        $stmt->close();
        header("Location: student_list.php");
        exit;
    }
    $stmt->close();

    // Generate student number
    $student_prefix = "AFK-";
$query = "SELECT MAX(CAST(SUBSTRING(student_number, 5) AS UNSIGNED)) AS max_student_number FROM students";
$result = $mysqli->query($query);
$row = $result->fetch_assoc();

// Increment the max student number by 1
$next_student_number = $row['max_student_number'] + 1;
$student_number = $student_prefix . str_pad($next_student_number, 4, '0', STR_PAD_LEFT);

    // Hash password
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // Handle required profile picture upload
    if (isset($_FILES["profile_pic"]) && !empty($_FILES["profile_pic"]["name"])) {
        $upload_dir = "../student/student_profile/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $profile_pic = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $target_file = $upload_dir . $profile_pic;

        $check = @getimagesize($_FILES["profile_pic"]["tmp_name"]);
        if ($check === false || $_FILES["profile_pic"]["size"] > 2097152) {
            $_SESSION['registration_error'] = "Invalid image or size exceeds 2MB.";
            header("Location: student_list.php");
            exit;
        }

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $_SESSION['registration_error'] = "Only JPG, JPEG, PNG, & GIF files are allowed.";
            header("Location: student_list.php");
            exit;
        }

        if (!move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $_SESSION['registration_error'] = "Failed to upload image.";
            header("Location: student_list.php");
            exit;
        }
    } else {
        $_SESSION['registration_error'] = "Profile picture is required.";
        header("Location: student_list.php");
        exit;
    }

    // Insert student into the database
    $sql = "INSERT INTO students (student_fn, student_ln, student_email, student_pass, student_pic, class_id, student_number)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssis", $first_name, $last_name, $email, $hashed_password, $profile_pic, $class_id, $student_number);

    if ($stmt->execute()) {
        $student_id = $mysqli->insert_id;

        // Assign subjects
        $subject_stmt = $mysqli->prepare("SELECT subject_id FROM subjects WHERE class_id = ?");
        $subject_stmt->bind_param("i", $class_id);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();

        while ($subject = $subject_result->fetch_assoc()) {
            $insert = $mysqli->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
            $insert->bind_param("ii", $student_id, $subject['subject_id']);
            $insert->execute();
        }

        // Assign exam status
        $exam_stmt = $mysqli->prepare("SELECT exam_id FROM exams WHERE subject_code IN (SELECT subject_code FROM subjects WHERE class_id = ?)");
        $exam_stmt->bind_param("i", $class_id);
        $exam_stmt->execute();
        $exam_result = $exam_stmt->get_result();

        while ($exam = $exam_result->fetch_assoc()) {
            $status_sql = "INSERT INTO status (student_id, exam_id, exam_status) VALUES (?, ?, 'Assigned')";
            $status_stmt = $mysqli->prepare($status_sql);
            $status_stmt->bind_param("ii", $student_id, $exam['exam_id']);
            if (!$status_stmt->execute()) {
                error_log("Failed to insert status: " . $status_stmt->error);
            }
            $status_stmt->close();
        }

        // Send email
        try {
            $mail = getMailer();
            $mail->addAddress($email);
            $mail->Subject = "Your Student Account Login Details";
            $mail->Body = "Dear $first_name $last_name,\n\nYour student account has been successfully registered.\n\nStudent Number: $student_number\nUsername (Email): $email\nPassword: $default_password\n\nPlease log in and change your password immediately.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
        }

        $_SESSION['registration_success'] = true;
        $_SESSION['student_number'] = $student_number;
        $_SESSION['default_password'] = $default_password;
        header("Location: student_list.php");
        exit;
    } else {
        $_SESSION['registration_error'] = "Database error: " . $mysqli->error;
        header("Location: student_list.php");
        exit;
    }
}
?>
