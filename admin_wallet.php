<?php
session_start();
include "db.php";
include "wallet_functions.php";

// ✅ Only Admin allowed
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Fetch all users for dropdown
$user_query = $conn->query("SELECT id, name, email FROM users ORDER BY name ASC");
$users = [];
while ($row = $user_query->fetch_assoc()) {
    $users[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];

    if ($amount > 0) {
        updateWallet($conn, $user_id, $amount, $type);
        $message = "✅ $type of $amount successful!";
    } else {
        $message = "❌ Invalid amount!";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard - Wallet Management</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
        }

        #openWalletBtn {
            display: block;
            margin: 30px auto;
            padding: 12px 25px;
            background: #3498db;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        #openWalletBtn:hover {
            background: #2980b9;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s;
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

        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background: #219150;
        }

        .message {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

    <h1>Admin Dashboard</h1>

    <!-- Dashboard Button -->
    <button id="openWalletBtn">💰 Wallet Management</button>

    <!-- Modal -->
    <div id="walletModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModalBtn">&times;</span>
            <h3>Wallet Management</h3>

            <?php if (!empty($message)): ?>
                <p class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                    <?= $message ?>
                </p>
            <?php endif; ?>

            <form method="post">
                <label>Select User:</label>
                <select name="user_id" required>
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?> (<?= $u['email'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Amount:</label>
                <input type="number" step="0.01" name="amount" required>

                <label>Type:</label>
                <select name="type" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdraw">Withdraw</option>
                </select>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("walletModal");
        const openBtn = document.getElementById("openWalletBtn");
        const closeBtn = document.getElementById("closeModalBtn");

        openBtn.onclick = function () {
            modal.style.display = "flex";
        };

        closeBtn.onclick = function () {
            modal.style.display = "none";
        };

        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        };
    </script>

</body>

</html>