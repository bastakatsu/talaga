<?php 
include_once "slidebar.php";

// Fetch admins
$admin_query = $mysqli->query("SELECT admin_name, admin_email, admin_pic FROM admins");

// Fetch students
$student_query = $mysqli->query("
    SELECT s.student_number, CONCAT(s.student_fn, ' ', s.student_ln) AS fullname, 
           c.level, c.section, s.student_pic
    FROM students s
    INNER JOIN classes c ON s.class_id = c.class_id
");
?>

<div class="container" style="margin-top:20px; display:flex; gap:30px; flex-wrap:wrap;">

    <!-- Admin Box -->
    <div class="admin-box" style="flex:1; min-width:300px; padding:20px; border:1px solid #ccc; border-radius:10px; background:#f9f9f9;">
        <h3>Admins</h3>
        <?php if ($admin_query->num_rows > 0): ?>
            <?php while($admin = $admin_query->fetch_assoc()): ?>
                <div style="display:flex; align-items:center; margin-bottom:15px;">
                   <img src="../admin/admin_pic/<?= htmlspecialchars($admin['admin_pic']); ?>" alt="Admin Pic" style="width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px;">
                    <div>
                        <strong><?= htmlspecialchars($admin['admin_name']); ?></strong><br>
                        <small><?= htmlspecialchars($admin['admin_email']); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No admins found</p>
        <?php endif; ?>
    </div>

    <!-- Student Box -->
    <div class="student-box" style="flex:2; min-width:400px; padding:20px; border:1px solid #ccc; border-radius:10px; background:#f9f9f9;">
        <h3>Students</h3>
        <?php if ($student_query->num_rows > 0): ?>
            <?php while($student = $student_query->fetch_assoc()): ?>
                <div style="display:flex; align-items:center; margin-bottom:15px;">
                    <img src="../student/student_profile/<?= htmlspecialchars($student['student_pic']); ?>" alt="Student Pic" style="width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px;">
                    <div>
                        <strong><?= htmlspecialchars($student['fullname']); ?></strong><br>
                        <small>Student No: <?= htmlspecialchars($student['student_number']); ?> | Class: <?= htmlspecialchars($student['level'] . '-' . $student['section']); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No students found</p>
        <?php endif; ?>
    </div>

</div>
