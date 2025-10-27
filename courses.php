<?php
include '../admin_db.php';  // Include the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the class data from the form
    $class_id = isset($_POST['id']) ? $_POST['id'] : '';  // For edit, class_id will be set
    $level = $_POST['level'];  // Level of the class
    $section = $_POST['section'];  // Section of the class

    // Check if the combination of level and section already exists
    $check_query = "SELECT * FROM classes WHERE level = ? AND section = ?";
    $stmt = $mysqli->prepare($check_query);
    $stmt->bind_param("ss", $level, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Section already exists for the given level, don't create the class
        echo "<script>alert('Error: A class with this level and section already exists.');</script>";
        exit();  // Stop further execution
    }

    // Prepare SQL query based on whether we are updating or inserting a class
    if ($class_id) {
        // Update existing class
        $query = "UPDATE classes SET level=?, section=? WHERE class_id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ssi", $level, $section, $class_id);
    } else {
        // Insert new class
        $query = "INSERT INTO classes (level, section) VALUES (?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ss", $level, $section);
    }

    if ($stmt->execute()) {
        header("Location: courses.php?success=1");  // Redirect on success
        exit();
    } else {
        echo "<script>alert('Error saving class: " . $mysqli->error . "');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Class</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h3><?php echo isset($class_id) ? "Edit" : "Add"; ?> Class</h3>
        <form action="" method="POST">
            <input type="hidden" name="id" value="<?php echo isset($class_id) ? $class_id : ''; ?>">

            <div class="form-group">
                <label>Year Level</label>
                <input type="text" class="form-control" name="level" value="<?php echo isset($level) ? $level : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Section</label>
                <input type="text" class="form-control" name="section" value="<?php echo isset($section) ? $section : ''; ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</body>

</html>
