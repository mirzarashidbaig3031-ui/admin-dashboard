<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function updateWallet($conn, $user_id, $amount, $type)
{
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isd", $user_id, $type, $amount);
    $stmt->execute();
    $stmt->close();

    $check = $conn->prepare("SELECT id FROM wallets WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $result = $check->get_result();
    $walletExists = $result->num_rows > 0;
    $check->close();

    if ($walletExists) {
        if ($type == 'deposit') {
            $sql = "UPDATE wallets SET total_deposit = total_deposit + ?, updated_at = NOW() WHERE user_id = ?";
        } elseif ($type == 'withdraw') {
            $sql = "UPDATE wallets SET total_withdraw = total_withdraw + ?, updated_at = NOW() WHERE user_id = ?";
        }
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("di", $amount, $user_id);
    } else {
        if ($type == 'deposit') {
            $sql = "INSERT INTO wallets (user_id, total_deposit, total_withdraw, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("id", $user_id, $amount);
        } elseif ($type == 'withdraw') {
            $sql = "INSERT INTO wallets (user_id, total_deposit, total_withdraw, created_at, updated_at) VALUES (?, 0, ?, NOW(), NOW())";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("id", $user_id, $amount);
        }
    }

    if (!$stmt2->execute()) {
        die("SQL Error: " . $stmt2->error);
    }
    $stmt2->close();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];
    $user_id = intval($_POST['user_id']);

    if ($user_id > 0) {
        updateWallet($conn, $user_id, $amount, $type);
        $message = "✅ Wallet updated successfully!";
    } else {
        $message = "❌ Invalid user ID.";
    }
}
?>