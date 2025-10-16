<?php
session_start();
include "db.php";

// ✅ Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $amount = floatval($_POST['amount']);

    if (empty($email)) {
        $message = "❌ Please enter an email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
    } elseif ($amount <= 0) {
        $message = "❌ Please enter a valid amount.";
    } else {
        // ✅ Insert deposit request into deposit_requests table
        $stmt = $conn->prepare("INSERT INTO deposit_requests (user_id, email, amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isd", $user_id, $email, $amount);

        if ($stmt->execute()) {
            $message = "✅ Deposit request submitted successfully! Waiting for admin approval.";
        } else {
            $message = "❌ Database Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Deposit Request</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 30px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            width: 380px;
            animation: fadeIn 0.6s ease-in-out;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2d3436;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
            color: #555;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }

        input:focus {
            border-color: #0984e3;
            outline: none;
            box-shadow: 0 0 5px rgba(9, 132, 227, 0.5);
        }

        button {
            width: 100%;
            padding: 14px;
            background: #0984e3;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #0652dd;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>💰 Deposit Request</h2>

        <?php if ($message): ?>
            <p class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" placeholder="Enter user email" required>

            <label>Enter Amount:</label>
            <input type="number" name="amount" step="0.01" placeholder="Enter amount" required>

            <button type="submit">Request Deposit</button>
        </form>
    </div>
</body>

</html>