<?php 
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../admin_db.php";

$admin_id = $_SESSION["id"]; 
include 'student_register.php'; // Keeps your previous function for student registration

// Retrieve student number and default password for modal display
$student_number = $_SESSION['student_number'] ?? '';
$default_password = $_SESSION['default_password'] ?? '';

$query = "
    SELECT 
        s.student_id, 
        s.student_number, 
        CONCAT(s.student_fn, ' ', s.student_ln) AS fullname, 
        s.student_email, 
        c.level, 
        c.section, 
        GROUP_CONCAT(sub.subject_code ORDER BY sub.subject_code ASC) AS subject_codes,
        GROUP_CONCAT(sub.subject_id ORDER BY sub.subject_code ASC) AS subject_ids
    FROM students s
    INNER JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN admin_classes ac ON c.class_id = ac.class_id
    LEFT JOIN student_subjects ss ON s.student_id = ss.student_id
    LEFT JOIN subjects sub ON ss.subject_id = sub.subject_id
    GROUP BY s.student_id, c.level, c.section
    ORDER BY fullname ASC
";

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
  <title>AFK | Manage Students</title>

  <link href="assets/css/classes.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="icon" href="/admin/assets/logo.png" type="image/png">


  <style>
    .modal-content {
        background: #cce5e8 !important;
        border-radius: 15px;
        border: none;
        outline: 3px solid #e2ac25;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.55);
    }

    .modal-header {
        border-bottom: none;
        padding: 20px 30px 0;
    }

    .modal-title {
        color: #FFCC00 ;
        font-size: 25px;
        font-weight: bold;
        width: 100%;
        text-align: center;
    }

    .modal-body {
        padding: 20px 30px;
    }

    .form-field {
        margin-bottom: 20px;
        position: relative;
    }

    .form-field input, .form-field select {
        width: 100%;
        padding: 13px 35px;
        border: 1px solid #ddd;
        border-radius: 15px;
        outline: none;
        font-size: 14px;
        box-sizing: border-box;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        color: #1a4147;
    }

    .form-field input:focus, .form-field select:focus {
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        transform: scale(1.02);
        border-color: #0D768E;
    }

    .form-field input[type="file"] {
        padding: 10px;
    }

    .modal-footer {
        border-top: none;
        padding: 0 30px 20px;
    }

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

    .close {
        font-size: 28px;
        color: #1a4147;
        opacity: 0.7;
        transition: all 0.3s ease;
        
    }

    .close:hover {
        opacity: 1;
        color: #0D768E;
    }

    #closeModal {
      position: absolute;
      right: 10px;
      top: 20%;
      transform: translateY(-50%);
      font-size: 22px;
    }
    .custom-bg {
  background-color: #1a4147 !important; /* replace with your desired color */
}

.align-right {
  display: flex;
  justify-content: flex-end;
}

.my-custom-short-button {
  background-color: #dc3545 !important;
}

        /* Loading Overlay */

        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background:rgba(204, 229, 232, 0.47); /* Use $body-bg with transparency */
            z-index: 2000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-family: sans-serif;
        }

        /* Pencil Animation */
        @keyframes pencil-animation {
            0% { transform: rotate(135deg); }
            20% { transform: rotate(315deg); }
            45% { transform: translateX(300px) rotate(315deg); }
            55% { transform: translateX(300px) rotate(495deg); }
            100% { transform: rotate(495deg); }
        }

        .pencil {
            position: relative;
            width: 300px;
            height: 40px;
            transform-origin: center;
            transform: rotate(135deg);
            animation: pencil-animation 10s infinite;
        }

        .pencil__ball-point {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background: black;
            height: 10px;
            width: 10px;
            border-radius: 50px;
        }

        .pencil__cap {
            position: absolute;
            left: 0px;
            top: 50%;
            transform: translateY(-50%);
            clip-path: polygon(20% 40%, 100% 0%, 100% 100%, 20% 60%);
            background:rgb(241, 190, 131);
            width: 12%;
            height: 100%;
        }

        .pencil__cap-base {
            position: absolute;
            left: 12%;
            top: 0;
            height: 100%;
            width: 20px;
            background:#e2ac25 ;
        }

        .pencil__middle {
            position: absolute;
            left: calc(12% + 20px);
            top: 0;
            height: 100%;
            width: 70%;
            background: #e2ac25;;
        }

        .pencil__eraser {
            position: absolute;
            left: calc(12% + 70% + 20px);
            top: 0;
            height: 100%;
            width: 11%;
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
            background: rgb(150, 13, 13);
        }

        @keyframes line-animation {
            20% { transform: scaleX(0); }
            45% { transform: scaleX(0.6); }
            55% { transform: scaleX(0.6); }
            100% { transform: scaleX(0); }
        }

        .line {
            position: relative;
            top: 80px;
            right: 103px;
            height: 10px;
            width: 1000px;
            z-index: -1;
            border-radius: 50px;
            background: #1a4147;
            transform: scaleX(0);
            transform-origin: center;
            animation: line-animation 10s infinite;
        }

        .loading-overlay h2 {
            position: relative;
            top: 90px;
            right: 75px;
            font-size: 18px;
            color:rgb(26, 65, 71);
            font-family: 'Poppins', sans-serif;
        }
        .class-filter-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
}

.class-filter-btn {
    padding: 8px 16px;
    border-radius: 50px;
    border: 2px solid #0D768E;
    background-color: #fff;
    color: #0D768E;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.class-filter-btn:hover {
    background-color: #0D768E;
    color: #fff;
}

.class-filter-btn.active {
    background-color: #0D768E;
    color: #fff;
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
        <h1>Manage Students</h1>
      </div>
    </div>

    <!-- Section Title and Content -->
    <div class="container section-title" data-aos="fade-up">
      <div class="container-fluid">

        <!-- Add Student Button (OUTSIDE the card now) -->
        <div class="row mb-4">
          <div class="col-md-6 d-flex align-items-center">
          <button type="submit"data-toggle="modal" data-target="#addStudentModal" class="add-class-btn" id="showStudentModal">
                <i class="fas fa-plus"></i> Add Student
              </button>
            
           
        
          </div>
          <div class="class-filter-container mb-3">
    <button class="class-filter-btn active" data-class="all">All Classes</button>
    <?php
    $stmtClass = $mysqli->prepare("SELECT DISTINCT c.level, c.section FROM classes c JOIN admin_classes ac ON c.class_id = ac.class_id ORDER BY c.level, c.section");
    $stmtClass->execute();
    $classResult = $stmtClass->get_result();
    while ($classRow = $classResult->fetch_assoc()) {
        $classLabel = $classRow['level'] . ' - ' . $classRow['section'];
        echo "<button class='class-filter-btn' data-class='{$classLabel}'>$classLabel</button>";
    }
    $stmtClass->close();
    ?>
</div>

        </div>

        <!-- Card wrapper start -->
        <div class="class-card">
          <div class="card-body">

            <!-- Student List Table -->
            <h3 style="text-align: center; margin-bottom: 20px;">Student List</h3>
            <div class="table-container">
              <table class="styled-table">
                <thead>
                  <tr>
                    <th scope="col">Student No.</th>
                    <th scope="col">Full Name</th>
                    <th scope="col">Class</th>
                    <th scope="col">Email</th>
                    <th scope="col">Subject Codes</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                      <tr>
                        <td data-label="Student No."><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['student_number']))); ?></td>
                        <td data-label="Full Name"><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['fullname']))); ?></td>
                        <td data-label="Class"><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['level'] . " - " . $row['section']))); ?></td>
                        <td data-label="Email"><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['student_email']))); ?></td>
                        <td data-label="Subject Codes">
                          <button class="view-btn" onclick="openModal('subjectModal<?= $row['student_id']; ?>')">
                            View Subjects
                          </button>
                        </td>
                        <td data-label="Actions">
                          <button 
                              type="button" 
                              class="view-btn my-custom-short-button " 
                              id="deleteStudentBtn_<?= $row['student_id']; ?>"
                              onclick="if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) { window.location.href = 'student_del.php?id=<?= $row['student_id']; ?>'; }">
                              Delete
                          </button>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="text-center">No students found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div> 
        </div> 

      </div> 
    </div>
    
    <div class="container" data-aos="fade-up" data-aos-delay="200">
      <p class="note" style="text-align: center; font-size: 12px; color:rgba(26, 65, 71, 0.58); margin-top: 15px; line-height: 1.6;">
        <i>Note : All student accounts are initially created with randomly generated passwords.
        Instructors are advised to remind their students to update their passwords <br>
         through their profile settings to avoid forgetting them in the future.</i>
      </p>
    </div>

  </main>

  <!-- Subject Modal Templates -->
  <?php if ($result->num_rows > 0): ?>
    <?php 
    // Reset result pointer
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()): 
    ?>
      <div id="subjectModal<?= $row['student_id']; ?>" class="modal">
      <div class="modal-content custom-bg">
          <div class="modal-header">
            <span class="btn-close" onclick="closeModal('subjectModal<?= $row['student_id']; ?>')"></span>
          </div>
          <h6 class="modal-title"><?= htmlspecialchars($row['fullname']); ?> Courses</h6>
          <div class="modal-body">
           <b> <p><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['subject_codes']))); ?></p></b>
          </div>
          <div class="modal-footer justify-content-center">
  <button type="button" class="add-class-btn" onclick="closeModal('subjectModal<?= $row['student_id']; ?>')">Close</button>
</div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>



  

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Register Student</h5>
          <button type="button" class="btn-close" data-dismiss="modal"></button>

       
          </div>
        <div class="modal-body">
          <div id="registrationError" class="alert alert-danger" style="display: none;"></div>
          <div class="form-field">
            <input type="text" name="first_name" maxlength="20" placeholder="First Name" required>
          </div>
          <div class="form-field">
            <input type="text" name="last_name" maxlength="20" placeholder="Last Name" required>
          </div>
          <div class="form-field">
            <input type="email" name="email" placeholder="Email" required>
          </div>
          <div class="form-field">
            <label style="font-size: 14px; color: #1a4147; margin-bottom: 5px;">Profile Picture</label>
            <input type="file" name="profile_pic" accept="image/*" required>
          </div>
         <div class="form-field">
  <select name="class_id" required>
    <option value="" disabled selected>-- Select Class --</option>
    <?php
    // Use a prepared statement for fetching classes
    $classQuery = "SELECT c.class_id, c.level, c.section 
                   FROM classes c
                   JOIN admin_classes ac ON c.class_id = ac.class_id";
    $stmtClass = $mysqli->prepare($classQuery);
    $stmtClass->execute();
    $classResult = $stmtClass->get_result();
    while ($classRow = $classResult->fetch_assoc()) {
        echo "<option value='{$classRow['class_id']}'>{$classRow['level']} - {$classRow['section']}</option>";
    }
    $stmtClass->close();
    ?>
  </select>
  <p class="note" style="font-size: 12px; color:rgba(26, 65, 71, 0.52); margin-top: 30px;">
    <i>Note: Instructors must collect student information before registration.</i>
  </p>
</div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" name="register_student">Register</button>
        </div>
      </div>
    </form>
  </div>
</div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="pencil">
            <div class="pencil__ball-point"></div>
            <div class="pencil__cap"></div>
            <div class="pencil__cap-base"></div>
            <div class="pencil__middle"></div>
            <div class="pencil__eraser"></div>
        </div>
        <div class="line"></div>
        <h2>Please Wait...</h2>
    </div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Success!</h5>
      </div>
      <div class="modal-body">
        <h5 class="modal-title mb-3" id="successModalLabel" style="font-size: 15px; font-weight: normal; color: #1a4147;">Student is now registred</h5>
        <p><strong style="color: #1a4147">Student Number:</strong> <span style="color: #1a4147"><?= htmlspecialchars($student_number); ?></span></p>
        <p><strong style="color: #1a4147">Default Password:</strong> <span style="color: #1a4147"><?= htmlspecialchars($default_password); ?></span></p>                
      <div class="modal-footer">
      <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>

<script>
$(document).ready(function () {
    <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success'] === true): ?>
        // Hide registration modal and show success modal when registration is successful
        $('#addStudentModal').modal('hide');
        $('#successModal').modal('show');
        <?php unset($_SESSION['registration_success']); // Clear the success session ?>
    <?php elseif (isset($_SESSION['registration_error'])): ?>
        // If there is an error, show it at the top of the registration modal
        $('#registrationError').text("<?= $_SESSION['registration_error']; ?>").show();
        // Do not close the registration modal
        $('#addStudentModal').modal('show');
        <?php unset($_SESSION['registration_error']); // Clear the error session ?>
    <?php endif; ?>

    // Close success modal when OK button is pressed
    $('#successModal .btn-primary').on('click', function () {
        $('#successModal').modal('hide');
    });
});
</script>
  <a href="#" id="scroll-top" class="scroll-top"><i class="bi bi-arrow-up-short"></i></a>

<script>
  window.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('.styled-table tbody');
    const tableContainer = document.querySelector('.table-container');

    if (tableBody && tableBody.rows.length > 10) {
      tableContainer.classList.add('scrollable');
    }

    // ✅ Show loading overlay when the Register Student form is submitted
    const registerForm = document.querySelector('#addStudentModal form');
    if (registerForm) {
      registerForm.addEventListener('submit', function () {
        document.getElementById('loadingOverlay').style.display = 'flex';
      });
    }
  });

  // Modal functions
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
  <script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.class-filter-btn');
    const tableRows = document.querySelectorAll('.styled-table tbody tr');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            const selectedClass = button.getAttribute('data-class');

            tableRows.forEach(row => {
                const rowClass = row.cells[2].innerText.trim(); // Class column
                if (selectedClass === 'all' || rowClass === selectedClass) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>

</body>

</html>