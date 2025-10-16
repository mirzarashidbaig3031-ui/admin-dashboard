<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Fetch user info ---
$query = $conn->prepare("SELECT name, email FROM users WHERE id=?");
if (!$query) {
    die("SQL Error (users): " . $conn->error);
}
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$query->close();

// --- Fetch wallet balance ---
$wallet_query = $conn->prepare("SELECT total_deposit - total_withdraw AS balance FROM wallets WHERE user_id=?");
if (!$wallet_query) {
    die("SQL Error (wallets): " . $conn->error);
}
$wallet_query->bind_param("i", $user_id);
$wallet_query->execute();
$wallet_result = $wallet_query->get_result();
$wallet = $wallet_result->fetch_assoc();
$balance = $wallet ? $wallet['balance'] : 0;
?>
<!DOCTYPE html>
<html>

<head>
    <title>User Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            text-align: center;
            padding-top: 50px;
        }

        .card {
            background: white;
            padding: 30px;
            margin: 0 auto;
            width: 400px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .deposit-btn {
            display: inline-block;
            background-color: #27ae60;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .deposit-btn:hover {
            background-color: #2ecc71;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?> 👋</h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Current Balance:</strong> <?= number_format($balance, 2) ?> PKR</p>
        <br>
        <a href="request_deposit.php" class="deposit-btn">💰 Deposit Balance</a>
    </div>
</body>

</html>