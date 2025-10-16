<?php
include "db.php"; // connect to db_user

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ✅ Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$check) {
        die("SQL Error: " . $conn->error);
    }
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: admin.php?error=duplicate");
        exit;
    }
    $check->close();

    // ✅ Insert Admin with role_id = 1
    $sql = "INSERT INTO users (role_id, name, email, password) VALUES (1, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        header("Location: dashbord.php?success=1");
        exit;
    } else {
        header("Location: admin.php?error=db");
        exit;
    }
}
?>