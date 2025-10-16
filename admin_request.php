<?php
session_start();
include "db.php";

// ✅ Only admin can access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit;
}

// ✅ Fetch ALL pending requests (no LIMIT)
$sql = "SELECT dr.id, dr.amount, dr.status, dr.created_at, u.name, u.email 
        FROM deposit_requests dr
        JOIN users u ON dr.user_id = u.id
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pending Deposit Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #0984e3;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        a {
            text-decoration: none;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 5px;
        }

        .approve {
            background: #2ecc71;
            color: white;
        }

        .reject {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>

<body>
    <h2>💰 Pending Deposit Requests</h2>
    <table>
        <tr>
            <th>User</th>
            <th>Email</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <a class="approve" href="process_request.php?id=<?= $row['id'] ?>&action=approve">✅ Approve</a>
                        <a class="reject" href="process_request.php?id=<?= $row['id'] ?>&action=reject">❌ Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No pending requests 🎉</td>
            </tr>
        <?php endif; ?>
    </table>
</body>

</html>