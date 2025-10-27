<?php
session_start();
include '../admin_db.php';

if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // First, delete related answers
    $delete_answers_query = "DELETE FROM user_answers WHERE student_id = ?";
    $stmt = $mysqli->prepare($delete_answers_query);
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Error preparing statement for deleting answers.";
        $_SESSION['msg_type'] = "danger";
        header("Location: student_list.php");
        exit();
    }

    // Delete related status
    $delete_status_query = "DELETE FROM status WHERE student_id = ?";
    $stmt = $mysqli->prepare($delete_status_query);
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Error preparing statement for deleting status.";
        $_SESSION['msg_type'] = "danger";
        header("Location: student_list.php");
        exit();
    }

    // Delete related scores
    $delete_score_query = "DELETE FROM score WHERE student_id = ?";
    $stmt = $mysqli->prepare($delete_score_query);
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Error preparing statement for deleting scores.";
        $_SESSION['msg_type'] = "danger";
        header("Location: student_list.php");
        exit();
    }

    // Delete related student_subjects
    $delete_subjects_query = "DELETE FROM student_subjects WHERE student_id = ?";
    $stmt = $mysqli->prepare($delete_subjects_query);
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Error preparing statement for deleting student subjects.";
        $_SESSION['msg_type'] = "danger";
        header("Location: student_list.php");
        exit();
    }

    // Now delete the student
    $delete_student_query = "DELETE FROM students WHERE student_id = ?";
    $stmt = $mysqli->prepare($delete_student_query);
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Student deleted successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "❌ Error deleting student.";
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Error preparing statement for deleting student.";
        $_SESSION['msg_type'] = "danger";
    }

    // Redirect to the student list
    header("Location: student_list.php");
    exit();
}
?>