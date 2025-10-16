<?php
session_start();
include "db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch wallet info
$stmt2 = $conn->prepare("SELECT total_deposit, total_withdraw FROM wallets WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$wallet = $result2->fetch_assoc();
$stmt2->close();

$totalDeposit = $wallet['total_deposit'] ?? 0;
$totalWithdraw = $wallet['total_withdraw'] ?? 0;
$currentBalance = $totalDeposit - $totalWithdraw;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f9;
        }

        /* Sidebar (same as admin) */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            float: left;
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a,
        .sidebar button {
            display: block;
            padding: 10px;
            margin: 8px 0px;
            text-decoration: none;
            background: #34495e;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: background0.3s;
            margin-top: 10;
        }

        .sidebar a:hover,
        .sidebar button:hover {
            background: #1abc9c;
        }

        /* Main content */
        .content {
            margin-left: 240px;
            padding: 30px;
        }

        .dashboard-box {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
        }

        .sidebar h2 {
            color: #f6f4f7ff;
            margin-top: 5px;
        }

        h2 {
            color: #aa54faff;
            margin-top: 5px;
        }

        .balance {
            background: #e3fcef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 20px;
            font-weight: bold;
            color: #2ecc71;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .deposit-btn {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
        }

        .deposit-btn:hover {
            background: #2980b9;
        }

        .logout-btn {
            background: #dc3545;
        }

        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="user_dashboard.php">🏠 Back to Dashboard</a>
        <a href="request_deposit.php">💸 Deposit Request</a>


        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">🚪 Logout</button>
        </form>
    </div>

    <div class="content">
        <div class="dashboard-box">
            <h2>Welcome, <?= htmlspecialchars($user['name']) ?> 👋</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

            <div class="balance">
                💰 Current Balance: PKR <?= number_format($currentBalance, 2) ?>
            </div>

            <div class="btn-container">
                <form action="request_deposit.php" method="GET">
                    <button type="submit" class="deposit-btn">Deposit Now</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>