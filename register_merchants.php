<?php
session_start();
include "db.php"; // database connection

// Optional: only admin can access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    // header("Location: admin_login.php");
    // exit;
}

// Fetch all merchants (role_id = 2)
$sql = "SELECT id, name, email, referral_code FROM users WHERE role_id = 2 ORDER BY id DESC";
$merchants = $conn->query($sql);

// If a merchant_id is selected
$selected_users = [];
$merchant_name = "";
if (isset($_GET['merchant_id'])) {
    $merchant_id = intval($_GET['merchant_id']);

    // Get merchant name
    $m_query = $conn->prepare("SELECT name FROM users WHERE id = ? AND role_id = 2");
    $m_query->bind_param("i", $merchant_id);
    $m_query->execute();
    $m_result = $m_query->get_result();
    if ($m_result->num_rows > 0) {
        $merchant_row = $m_result->fetch_assoc();
        $merchant_name = $merchant_row['name'];
    }

    // ✅ FIX: use correct table name merchant_users
    $sql_users = "
        SELECT u.id, u.name, u.email 
        FROM merchant_users mu
        INNER JOIN users u ON mu.user_id = u.id
        WHERE mu.merchant_id = ?
    ";
    $stmt = $conn->prepare($sql_users);
    if ($stmt) {
        $stmt->bind_param("i", $merchant_id);
        $stmt->execute();
        $selected_users = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Registered Merchants</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }

        .container {
            width: 900px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #28a745;
            color: #fff;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .no-data {
            text-align: center;
            margin-top: 20px;
            color: #888;
        }

        .btn {
            padding: 6px 12px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .users-box {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Registered Merchants</h2>
        <?php if ($merchants->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Merchant Name</th>
                    <th>Email</th>
                    <th>Referral Code</th> <!-- ✅ New Column -->
                    <th>Action</th>
                </tr>
                <?php while ($row = $merchants->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['referral_code']) ?></td> <!-- ✅ Show Referral Code -->
                        <td>
                            <a href="?merchant_id=<?= $row['id'] ?>" class="btn">View Users</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No merchants registered yet.</p>
        <?php endif; ?>

        <!-- Show selected merchant's users -->
        <?php if (isset($_GET['merchant_id'])): ?>
            <div class="users-box">
                <h3>Users under Merchant: <?= htmlspecialchars($merchant_name) ?></h3>
                <?php if ($selected_users && $selected_users->num_rows > 0): ?>
                    <table>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                        <?php while ($user = $selected_users->fetch_assoc()): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p class="no-data">No users found under this merchant.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>