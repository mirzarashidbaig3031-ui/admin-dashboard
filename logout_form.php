<?php
session_start();
include "db.php"; // connect to user_db

// ✅ If logout button is clicked → destroy session and redirect
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role_id'];
$name = $_SESSION['name'];
?>



<!DOCTYPE html>
<html>

<head>
    <title>Logout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .logout-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logout-box h2 {
            margin-bottom: 15px;
        }

        .logout-box form button {
            padding: 10px 20px;
            background: #e74c3c;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-box form button:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>
    <div class="logout-box">
        <h2>Hello, <?= htmlspecialchars($name) ?> </h2>
        <p>You are logged in as
            <?php if ($role == 1)
                echo "Admin";
            elseif ($role == 2)
                echo "Merchant";
            else
                echo "User"; ?>
        </p>
        <form method="POST">
            <button type="submit">🚪 Logout</button>
        </form>
    </div>
</body>

</html>