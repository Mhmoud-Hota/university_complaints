<?php
// complaint.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_type = sanitize($_POST['complaint_type']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $student_id = $_SESSION['user_id'];
    
    $image_path = null;
    
    // معالجة رفع الصورة
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO complaints 
                              (student_id, complaint_type, description, location, image_path) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $complaint_type, $description, $location, $image_path]);
        
        header("Location: dashboard.php?success=complaint_added");
        exit();
    } catch(PDOException $e) {
        header("Location: dashboard.php?error=database_error");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>