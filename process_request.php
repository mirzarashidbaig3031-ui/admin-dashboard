<?php
session_start();
include "db.php";

// ✅ Admin check
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'], $_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // ✅ Get user_id and amount for this request
    $stmt = $conn->prepare("SELECT user_id, amount FROM deposit_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($user_id, $amount);
    $stmt->fetch();
    $stmt->close();

    if ($action === "approve") {
        // 1️⃣ Mark as approved
        $update = $conn->prepare("UPDATE deposit_requests SET status='approved' WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();
        $update->close();

        // 2️⃣ Update wallet table
        $check = $conn->prepare("SELECT id FROM wallets WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $res = $check->get_result();
        $walletExists = $res->num_rows > 0;
        $check->close();

        if ($walletExists) {
            $stmt2 = $conn->prepare("UPDATE wallets SET total_deposit = total_deposit + ?, updated_at = NOW() WHERE user_id = ?");
            $stmt2->bind_param("di", $amount, $user_id);
        } else {
            $stmt2 = $conn->prepare("INSERT INTO wallets (user_id, total_deposit, total_withdraw, created_at, updated_at)
                                     VALUES (?, ?, 0, NOW(), NOW())");
            $stmt2->bind_param("id", $user_id, $amount);
        }

        if (!$stmt2->execute()) {
            die("SQL Error: " . $stmt2->error);
        }
        $stmt2->close();

    } elseif ($action === "reject") {
        $stmt = $conn->prepare("UPDATE deposit_requests SET status='rejected' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: admin_request.php");
exit;
?>