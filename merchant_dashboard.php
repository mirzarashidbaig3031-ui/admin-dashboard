<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$merchant_id = $_SESSION['user_id'];

// --- Fetch merchant info ---
$query = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$query->bind_param("i", $merchant_id);
$query->execute();
$result = $query->get_result();
$merchant = $result->fetch_assoc();
$query->close();

// --- Handle new user registration ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 3)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;

        // Link user to merchant
        $link = $conn->prepare("INSERT INTO merchant_users (merchant_id, user_id) VALUES (?, ?)");
        $link->bind_param("ii", $merchant_id, $new_user_id);
        $link->execute();
        $link->close();

        $msg = "✅ User registered successfully!";
    } else {
        $msg = "❌ Failed to register user (email may already exist)";
    }

    $stmt->close();
}

// --- Fetch users under this merchant ---
$user_query = $conn->prepare("SELECT u.id, u.name, u.email FROM users u 
    INNER JOIN merchant_users mu ON u.id = mu.user_id 
    WHERE mu.merchant_id=?");
$user_query->bind_param("i", $merchant_id);
$user_query->execute();
$user_result = $user_query->get_result();
$total_users = $user_result->num_rows;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Merchant Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            display: flex;
        }

        .sidebar {
            width: 220px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a,
        .sidebar button {
            display: block;
            color: white;
            padding: 12px 10px;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            background: none;
            width: 82%;
            text-align: left;
            cursor: pointer;
            background: #34495e;
            margin-top: 5px;
            border-radius: 5px;
            margin-left: 20px;
            margin-right: 10px;
        }

        .sidebar .logout-btn {
            background: #34495e;
            margin-right: 40px;
            /* smaller width */
            text-align: center;
            padding-right: 0px;
            padding-left: 0px;


        }

        .sidebar a:hover,
        .sidebar button:hover {
            background: #06ac90ff;
        }

        .content {
            margin-left: 240px;
            padding: 40px;
            width: 100%;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        th {
            background: #3498db;
            color: white;
        }

        .btn {
            background: #27ae60;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #2ecc71;
        }

        .logout-btn {
            background: #e74c3c;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* --- Popup Modal --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            width: 350px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 {
            text-align: center;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .modal-content button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .close {
            float: right;
            font-size: 20px;
            cursor: pointer;
            color: red;
        }


        .sidebar button.sidebar-btn {
            width: 90% !important;
            /* same as links */

            background: #34495e;


        }

        .sidebar button.sidebar-btn:hover {
            background: #06ac90ff;
        }
    </style>

</head>

<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="merchant_dashboard.php">🏠 Merchant</a>

        <button class="sidebar-btn" onclick="openModal()">➕ Register New User</button>


        <a href="#">👥 Total Users: <?= $total_users ?></a>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>


    <div class="content">
        <div class="card">
            <h2>Welcome, <?= htmlspecialchars($merchant['name']) ?> 👋</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($merchant['email']) ?></p>

            <?php if (isset($msg)): ?>
                <p style="color:green;"><?= $msg ?></p>
            <?php endif; ?>

            <h3>Users Registered Under You:</h3>
            <?php if ($total_users > 0): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                    <?php while ($row = $user_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>No users registered under this merchant yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ✅ Popup for Register New User -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Register New User</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Enter Name" required>
                <input type="email" name="email" placeholder="Enter Email" required>
                <input type="password" name="password" placeholder="Enter Password" required>
                <button type="submit" name="register_user">Register</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('registerModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        window.onclick = function (event) {
            let modal = document.getElementById('registerModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>