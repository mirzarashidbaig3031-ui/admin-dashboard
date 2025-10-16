<?php
session_start();
include "db.php";

// Optional: only allow admins to call these endpoints
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action === 'update') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and email required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $id);
        $ok = $stmt->execute();

        if ($ok) {
            echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user id']);
            exit;
        }

        // if you have related tables (merchant_users), remove links if needed
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if ($ok) {
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'change_password') {
        $id = intval($_POST['id']);
        $old = isset($_POST['old']) ? $_POST['old'] : '';
        $new = isset($_POST['new']) ? $_POST['new'] : '';

        if (empty($old) || empty($new)) {
            echo json_encode(['status' => 'error', 'message' => 'Old and new password required']);
            exit;
        }

        // fetch current hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        $currentHash = $user['password'];
        if (!password_verify($old, $currentHash)) {
            echo json_encode(['status' => 'error', 'message' => 'Old password is incorrect']);
            exit;
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $newHash, $id);
        $ok = $stmt->execute();
        if ($ok) {
            echo json_encode(['status' => 'success', 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password update failed: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // unknown action
    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    exit;
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    exit;
}
