<?php 
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_once "../admin_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
    $admin_id = intval($_POST['admin_id']);

    // Start transaction
    $mysqli->begin_transaction();

    try {
        // Delete related subjects
        $mysqli->query("
            DELETE sub FROM subjects sub
            INNER JOIN classes c ON sub.class_id = c.class_id
            WHERE c.admin_id = $admin_id
        ");

        // Delete related classes
        $mysqli->query("DELETE FROM classes WHERE admin_id = $admin_id");

        // Delete from admin_classes (if you use a pivot table)
        $mysqli->query("DELETE FROM admin_classes WHERE admin_id = $admin_id");

        // Delete admin
        $mysqli->query("DELETE FROM admins WHERE admin_id = $admin_id");

        // Commit transaction
        $mysqli->commit();

        header("Location: teachers.php?success=Admin+deleted+successfully");
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error deleting admin: " . $e->getMessage();
    }
    exit;
}
require_once "../admin_db.php";

$admin_id = $_SESSION["id"]; // Get the logged-in admin's ID


$query = "
   SELECT 
    a.admin_id,
    a.admin_name,
    a.admin_email,
    GROUP_CONCAT(DISTINCT CONCAT(c.level, ' - ', c.section) ORDER BY c.level, c.section SEPARATOR ', ') AS classes,
    GROUP_CONCAT(DISTINCT sub.subject_code ORDER BY sub.subject_code SEPARATOR ', ') AS subject_codes
FROM admins a
LEFT JOIN admin_classes ac ON a.admin_id = ac.admin_id
LEFT JOIN classes c ON ac.class_id = c.class_id OR c.admin_id = a.admin_id
LEFT JOIN subjects sub ON sub.class_id = c.class_id
GROUP BY a.admin_id, a.admin_name, a.admin_email
ORDER BY a.admin_name ASC;"
;

// Prepare and execute the query
$stmt = $mysqli->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>AFK | Manage Admins</title>

  <link href="assets/css/classes.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="icon" href="/admin/assets/logo.png" type="image/png">


  <style>
/* Modal Content */
.modal {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.4);
}

.modal.show {
  display: block;
}

.modal-content {
  background: #cce5e8 !important;
  border-radius: 15px;
  border: none;
  outline: 3px solid #e2ac25;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.55);
  position: relative;
  max-width: 600px;
  margin: 100px auto;
  padding: 0;
}

/* Modal Header */
.modal-header {
  border-bottom: none;
  padding: 20px 30px 0;
  position: relative;
}

/* Modal Title */
.modal-title {
  color: #FFCC00;
  font-size: 25px;
  font-weight: bold;
  width: 100%;
  text-align: center;
}

/* Modal Body */
.modal-body {
  padding: 20px 30px;
}

/* Modal Footer */
.modal-footer {
  border-top: none;
  padding: 0 30px 20px;
  text-align: center;
}

/* Buttons inside modal footer */
.modal-footer .btn-primary {
  width: 100%;
  padding: 12px;
  border-radius: 15px;
  font-size: 14px;
  font-weight: bold;
  background-color: #0D768E;
  color: whitesmoke;
  border: 2px solid #1a4147;
  transition: all 0.3s ease;
}

.modal-footer .btn-primary:hover {
  background-color: #1a4147;
  border-color: #e2ac25;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Close Button */
.close, .btn-close {
  font-size: 28px;
  color: #1a4147;
  opacity: 0.7;
  transition: all 0.3s ease;
  cursor: pointer;
}

.close:hover, .btn-close:hover {
  opacity: 1;
  color: #0D768E;
}

/* Optional: Custom background for modal content */
.custom-bg {
  background-color: #1a4147 !important; /* replace with your desired color */
}
</style>
</head>

<body class="starter-page-page">
  <i class="header-toggle d-xl-none bi bi-list"></i>
  <?php require_once 'slidebar.php'; ?>

  <main class="main">
    <div class="page-title" data-aos="fade">
      <div class="container">
        <nav class="breadcrumbs"></nav>
        <h1>Manage Admins</h1>
      </div>
    </div>

    <!-- Section Title and Content -->
    <div class="container section-title" data-aos="fade-up">
      <div class="container-fluid">

        <div class="row mb-4">
        <div class="col-md-6 d-flex align-items-center">
            <a href="register_admin.php" class="add-class-btn" id="showAdminRegister">
            <i class="fas fa-plus"></i> Add Admin
            </a>
        </div>
        </div>


        <!-- Card wrapper start -->
        <div class="class-card">
          <div class="card-body">

            <!-- Student List Table -->
            <h3 style="text-align: center; margin-bottom: 20px;">Admins List</h3>
            <div class="table-container">
              <table class="styled-table">
  <thead>
    <tr>
      <th>Admin Name</th>
      <th>Email</th>
      <th>Class</th>
      <th>Subjects</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['admin_name']); ?></td>
          <td><?= htmlspecialchars($row['admin_email']); ?></td>
          <td>
            <button class="view-btn" onclick="openModal('classModal<?= $row['admin_id']; ?>')">
              View Classes
            </button>
          </td>
          <td>
            <button class="view-btn" onclick="openModal('subjectModal<?= $row['admin_id']; ?>')">
              View Subjects
            </button>
          </td>
          <td>
                <form method="POST" action="teachers.php" onsubmit="return confirm('Are you sure you want to delete this admin and all related data?');">
                    <input type="hidden" name="admin_id" value="<?= $row['admin_id']; ?>">
                    <button type="submit" class="delete-btn" style="background:red; color:white; border:none; padding:5px 10px; border-radius:5px;">
                    Delete
                    </button>
                </form>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="4" class="text-center">No admins found</td></tr>
    <?php endif; ?>
  </tbody>
</table>

            </div>

          </div> 
        </div> 

      </div> 
    </div>
    

  </main>

  <!-- Subject Modal Templates -->
  <?php if ($result->num_rows > 0): ?>
    <?php 
    // Reset result pointer
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()): 
    ?>
      <!-- Class Modal -->
      <div id="classModal<?= $row['admin_id']; ?>" class="modal">
  <div class="modal-content custom-bg">
    <div class="modal-header">
      <span class="btn-close" onclick="closeModal('classModal<?= $row['admin_id']; ?>')"></span>
    </div>
    <h6 class="modal-title"><?= htmlspecialchars($row['admin_name']); ?> Classes</h6>
    <div class="modal-body">
      <b><p><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['classes']))); ?></p></b>
    </div>
    <div class="modal-footer justify-content-center">
      <button type="button" class="add-class-btn" onclick="closeModal('classModal<?= $row['admin_id']; ?>')">Close</button>
    </div>
  </div>
</div>
<!-- Subject Modal -->
<div id="subjectModal<?= $row['admin_id']; ?>" class="modal">
  <div class="modal-content custom-bg">
    <div class="modal-header">
      <span class="btn-close" onclick="closeModal('subjectModal<?= $row['admin_id']; ?>')"></span>
    </div>
    <h6 class="modal-title"><?= htmlspecialchars($row['admin_name']); ?> Subjects</h6>
    <div class="modal-body">
      <b><p><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['subject_codes']))); ?></p></b>
    </div>
    <div class="modal-footer justify-content-center">
      <button type="button" class="add-class-btn" onclick="closeModal('subjectModal<?= $row['admin_id']; ?>')">Close</button>
    </div>
  </div>
</div>

    <?php endwhile; ?>
<?php endif; ?>




<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>

<script>
  // Modal functions for subjects and classes
  function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
  }

  function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
  }

  // Close modal when clicking outside of it
  window.onclick = function (event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
      if (event.target == modals[i]) {
        modals[i].classList.remove('show');
      }
    }
  };
</script>

  <script src="assets/js/main.js"></script>
</body>

</html>