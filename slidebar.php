<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("location: ../index.php");
}

// Connect to DB
$mysqli = new mysqli("localhost", "root", "", "try");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Fetch admin data
$admin_id = $_SESSION['id'];
$admin_name = $email = "";

$sql = "SELECT name, email FROM sadmins WHERE id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($admin_name, $email);
    $stmt->fetch();
    $stmt->close();
    $_SESSION['name'] = $admin_name;
} else {
    die("Failed to prepare statement.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/slide.css" rel="stylesheet">
    <link rel="icon" href="/admin/assets/logo.png" type="image/png">
    
  


</head>

  <header id="header" class="header d-flex flex-column justify-content-center">
        <i class="header-toggle d-xl-none bi bi-list"></i>
        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="sadmin_dash.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'sadmin_dash.php') ? 'active' : '' ?>"><i class="bi bi-house navicon"></i><span>Dashboard</span></a></li>
                <li><a href="teachers.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'teachers.php') ? 'active' : '' ?>"><i class="bi bi-box"></i><span>Manage Teachers</span></a></li>
                <li><a href="Managesubject.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'Managesubject.php') ? 'active' : '' ?>"><i class="bi bi-book"></i><span>Manage Course</span></a></li>
                <li><a href="student_list.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'student_list.php') ? 'active' : '' ?>"><i class="bi bi-people"></i><span>Manage Students</span></a></li>
                <li><a href="exam.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'exam.php') ? 'active' : '' ?>"><i class="bi bi-clipboard-check"></i><span>Set an Exam</span></a></li>
                <li><a href="scores.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'scores.php') ? 'active' : '' ?>"><i class="bi bi-clipboard-data"></i><span>Students Score</span></a></li>
                <li><a href="status.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'status.php') ? 'active' : '' ?>"><i class="bi bi-person-badge"></i><span>Students Status</span></a></li>
               <li><a href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person navicon"></i><span>Profile</span></a></li>
                <li><a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right navicon"></i><span>Logout</span></a></li>

            </ul>
        </nav>
    </header>

<div class="modal fade profile-modal" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content d-flex align-items-center profile-card">
            <!-- Profile Details Section -->
            <div class="profile-text" id="profileDetails">
                <p class="position text-center"><strong>Admin</strong></p> <!-- Centered position text -->
                <p class="name"><b>Name:</b> <?php echo htmlspecialchars($admin_name); ?></p> <!-- Left-aligned -->
                <p class="description"><b>Email:</b> <?php echo htmlspecialchars($email); ?></p> 
            </div>
    </div>
  </div>
</div>



<!-- Logout Modal (unchanged) -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" id="saveLogoutButton">Logout</button>
               


                </button>

            </div>
        </div>
    </div>
</div>



<!-- Preloader (unchanged) -->
<div id="preloader">
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  </div>
<script>
      document.getElementById("saveLogoutButton").addEventListener("click", function() {
    window.location.href = "../logout.php";
});
</script>


<!-- Vendor JS Files (unchanged) -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/typed.js/typed.umd.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>


<!-- Main JS File -->
<script src="assets/js/main.js"></script>
<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>