<?php
// Include database connection
require_once "../admin_db.php";

// Initialize variables
$adminName = $email = $password = $confirmPassword = "";
$adminNameErr = $emailErr = $passwordErr = $confirmPasswordErr = $adminPicErr = "";
$adminPicFilename = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate admin name
    if (empty(trim($_POST["admin_name"]))) {
        $adminNameErr = "Admin name is required.";
    } elseif (strlen(trim($_POST["admin_name"])) < 2) {
        $adminNameErr = "Admin name must be at least 2 characters long.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", trim($_POST["admin_name"]))) {
        $adminNameErr = "Admin name can only contain letters.";
    } else {
        $adminName = ucwords(strtolower(trim($_POST["admin_name"])));
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $emailErr = "Email is required.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format.";
        } else {
            // Check if email already exists
            $sql = "SELECT admin_id FROM admins WHERE admin_email = ?";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $emailErr = "This email is already registered.";
                }
                $stmt->close();
            }
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $passwordErr = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $passwordErr = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirmPasswordErr = "Please confirm your password.";
    } else {
        $confirmPassword = trim($_POST["confirm_password"]);
        if ($password !== $confirmPassword) {
            $confirmPasswordErr = "Passwords do not match.";
        }
    }

    // Validate and upload profile picture (required)
    if (isset($_FILES["admin_pic"]) && $_FILES["admin_pic"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["admin_pic"]["tmp_name"];
        $fileName = basename($_FILES["admin_pic"]["name"]);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileExtension, $allowedExtensions)) {
            $adminPicFilename = uniqid("admin_", true) . "." . $fileExtension;
            $destination = "../admin/admin_pic/" . $adminPicFilename;

            if (!move_uploaded_file($fileTmpPath, $destination)) {
                $adminPicErr = "Failed to upload profile picture.";
            }
        } else {
            $adminPicErr = "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    } else {
        $adminPicErr = "Profile picture is required.";
    }

    // Final insertion if no errors
    if (empty($adminNameErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr) && empty($adminPicErr)) {
        $sql = "INSERT INTO admins (admin_name, admin_email, admin_pass, admin_pic) VALUES (?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $adminName, $email, $hashedPassword, $adminPicFilename);
            if ($stmt->execute()) {
                header("location: ../index.php");
                exit;
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration | AFK</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        body {
            background: #1a4147;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            gap: 20px;
        }

        .card-container {
            width: 900px;
            height: 600px;
            background: url('./assets/bg.png') no-repeat right center;
            background-size: cover;
            border-radius: 15px;
            display: flex;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.55);
            outline: 3px solid #e2ac25;
        }

        .register-section {
            width: 40%;
            background: #cce5e8;
            padding: 30px;
            border-radius: 15px 0 0 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .section-title {
            width: 100%;
            padding: 0 0 20px 0;
            text-align: center;
        }

        .main-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #e2ac25;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .form-field {
            margin-bottom: 15px;
            position: relative;
            width: 100%;
        }

        .form-field input {
            width: 100%;
            padding: 13px 35px;
            border: 1px solid #ddd;
            border-radius: 15px;
            outline: none;
            font-size: 14px;
            box-sizing: border-box;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .form-field input:focus {
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
            border-color: #0D768E;
        }

        .form-field i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #0D768E;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            background-color: #0D768E;
            color: whitesmoke;
            border: 2px solid #1a4147;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #1a4147;
            border-color: #e2ac25;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .text-center {
            text-align: center;
            margin-top: 20px;
        }

        .text-center a {
            color: rgb(0, 166, 255);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .text-center a:hover {
            color: rgb(0, 176, 207);
        }
    </style>
</head>
<body>
    <div class="card-container" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="100">
        <div class="register-section">
            <div class="section-title">
                <div class="main-title">Admin Registration</div>
            </div>

            <form class="user" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-field">
                    <i class="fas fa-user"></i>
                    <input type="text" name="admin_name" placeholder="Admin Name" value="<?php echo htmlspecialchars($adminName); ?>" class="<?php echo (!empty($adminNameErr)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $adminNameErr; ?></span>
                </div>

                <div class="form-field">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" class="<?php echo (!empty($emailErr)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $emailErr; ?></span>
                </div>

                <div class="form-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" class="<?php echo (!empty($passwordErr)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $passwordErr; ?></span>
                </div>

                <div class="form-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Repeat Password" class="<?php echo (!empty($confirmPasswordErr)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $confirmPasswordErr; ?></span>
                </div>

                <div class="form-field">
                    <i class="fas fa-image"></i>
                    <input type="file" name="admin_pic" accept="image/*" required>
                    <span class="invalid-feedback"><?php echo $adminPicErr; ?></span>
                </div>

                <button type="submit" class="btn">Register</button>

                <div class="text-center">
                    <a href="../index.php">Already have an account? Login!</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 100,
            easing: 'ease-out-cubic'
        });
    </script>
</body>
</html>