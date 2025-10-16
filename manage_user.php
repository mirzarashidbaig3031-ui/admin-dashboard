<?php
session_start();
include "db.php";

// Only allow admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit;
}

// Which page to show
$page = isset($_GET['page']) ? $_GET['page'] : "home";
$message = "";

// ---------------- Handle Registration ----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_type'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_BCRYPT);

    if ($_POST['register_type'] == "admin") {
        $role_id = 1;
    } elseif ($_POST['register_type'] == "merchant") {
        $role_id = 2;
    } elseif ($_POST['register_type'] == "user") {
        $role_id = 3; // user
    }

    // Generate a referral code if merchant
    $referral_code = null;
    if ($role_id == 2) { // 2 = merchant
        // Function to generate referral code
        function generate_referral_code($name)
        {
            $letters = strtoupper(substr(preg_replace("/[^A-Za-z]/", "", $name), 0, 3));
            if (strlen($letters) < 3) {
                $letters = str_pad($letters, 3, 'X');
            }
            return $letters . rand(1000, 9999);
        }

        // Create unique referral code
        do {
            $referral_code = generate_referral_code($name);
            $check = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
            $check->bind_param("s", $referral_code);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();
        } while ($exists);

        // Insert merchant with referral code
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, referral_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $email, $password, $role_id, $referral_code);
    } else {
        // For admin or user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $password, $role_id);
    }


    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id;

        // If normal user, also link with merchant
        if ($role_id == 3 && isset($_POST['merchant_id']) && intval($_POST['merchant_id']) > 0) {
            $merchant_id = intval($_POST['merchant_id']);
            $link = $conn->prepare("INSERT INTO merchant_users (user_id, merchant_id) VALUES (?, ?)");
            $link->bind_param("ii", $newUserId, $merchant_id);
            $link->execute();
            $link->close();

            // ✅ Create wallet entry for new user
            $wallet = $conn->prepare("INSERT INTO wallets (user_id, total_deposit, total_withdraw, created_at, updated_at) VALUES (?, 0, 0, NOW(), NOW())");
            $wallet->bind_param("i", $newUserId);
            $wallet->execute();
            $wallet->close();
        }




        if ($role_id == 2) {
            $message = "✅ Merchant registered successfully! Referral Code: <b>" . htmlspecialchars($referral_code) . "</b>";
        } else {
            $message = ucfirst($_POST['register_type']) . " registered successfully!";
        }


    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// ---------------- Helper functions ----------------
function getUsers($conn, $role = 0, $search = "", $limit = 10, $offset = 0)
{
    $search = trim($search);

    if ($role == 3) {
        // Show users with their assigned merchant
        if ($search !== "") {
            $sql = "SELECT u.id, u.name, u.email, m.name AS merchant_name
                    FROM users u
                    LEFT JOIN merchant_users mu ON u.id = mu.user_id
                    LEFT JOIN users m ON mu.merchant_id = m.id
                    WHERE u.role_id = 3 AND (u.name LIKE ? OR u.email LIKE ?)
                    ORDER BY u.id DESC LIMIT ?,?";
            $like = "%$search%";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $like, $like, $offset, $limit);
        } else {
            $sql = "SELECT u.id, u.name, u.email, m.name AS merchant_name
                    FROM users u
                    LEFT JOIN merchant_users mu ON u.id = mu.user_id
                    LEFT JOIN users m ON mu.merchant_id = m.id
                    WHERE u.role_id = 3
                    ORDER BY u.id DESC LIMIT ?,?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $offset, $limit);
        }
    } elseif ($role > 0 && $search !== "") {
        $sql = "SELECT id, name, email FROM users WHERE role_id=? AND (name LIKE ? OR email LIKE ?) ORDER BY id DESC LIMIT ?,?";
        $like = "%$search%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issii", $role, $like, $like, $offset, $limit);
    } elseif ($role > 0) {
        $sql = "SELECT id, name, email FROM users WHERE role_id=? ORDER BY id DESC LIMIT ?,?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $role, $offset, $limit);
    } elseif ($search !== "") {
        $sql = "SELECT id, name, email FROM users WHERE (name LIKE ? OR email LIKE ?) ORDER BY id DESC LIMIT ?,?";
        $like = "%$search%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $like, $like, $offset, $limit);
    } else {
        $sql = "SELECT id, name, email FROM users ORDER BY id DESC LIMIT ?,?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    }

    $stmt->execute();
    return $stmt->get_result();
}

function countUsers($conn, $role = 0, $search = "")
{
    $search = trim($search);
    if ($role > 0 && $search !== "") {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id=? AND (name LIKE ? OR email LIKE ?)";
        $like = "%$search%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $role, $like, $like);
    } elseif ($role > 0) {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $role);
    } elseif ($search !== "") {
        $sql = "SELECT COUNT(*) as total FROM users WHERE (name LIKE ? OR email LIKE ?)";
        $like = "%$search%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $like, $like);
    } else {
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return intval($row['total']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['id']);

    // delete from merchant_users first (if cascade not enabled)
    $stmt = $conn->prepare("DELETE FROM merchant_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // then delete from users
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $msg = "✅ User deleted successfully!";
    } else {
        $msg = "❌ Error deleting user: " . $conn->error;
    }

    $stmt->close();
}






?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Users</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f9;
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            float: left;
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar a,
        .sidebar button {
            display: block;
            padding: 10px;
            margin: 8px 0;
            text-decoration: none;
            background: #34495e;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
            border: none;
            text-align: left;
            cursor: pointer;
        }

        .sidebar a:hover,
        .sidebar button:hover {
            background: #1abc9c;
        }

        .content {
            margin-left: 240px;
            padding: 20px;
        }

        .form-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 420px;
        }

        input,
        select,
        button {
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            margin-top: 20px;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #021c2e;
            color: white;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .btn {
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        .upd-btn {
            background: #f39c12;
            color: white;
        }

        .upd-btn:hover {
            background: #e67e22;
        }

        .del-btn {
            background: #e74c3c;
            color: white;
        }

        .del-btn:hover {
            background: #c0392b;
        }

        .pwd-btn {
            background: #27ae60;
            color: white;
        }

        .pwd-btn:hover {
            background: #1e8449;
        }

        .pagination {
            margin-top: 15px;
        }

        .pagination a {
            padding: 6px 10px;
            margin: 2px;
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3e50;
            font-size: 13px;
        }

        .pagination a.active {
            background: #3498db;
            color: white;
            border-color: #2980b9;
        }

        .pagination a:hover {
            background: #95a5a6;
            color: white;
        }

        .search-box {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .search-box input[type="text"] {
            width: 250px;
            display: inline-block;
        }

        .search-box button {
            width: auto;
            padding: 8px 14px;
        }

        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .popup-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 420px;
            box-sizing: border-box;
            text-align: left;
        }

        .close-btn {
            float: right;
            cursor: pointer;
            font-weight: bold;
            color: red;
        }

        .center {
            text-align: center;
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 350px;
            text-align: center;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
            color: red;
            font-size: 18px;
        }
    </style>
</head>

<body>


    <div class="sidebar">
        <h3>Welcome Admin</h3>
        <a href="manage_user.php">🏠 Back to Dashboard</a>
        <a href="?page=register_admin">Create Admin</a>
        <a href="?page=register_merchant">Create Merchant</a>
        <a href="?page=register_user">Create User (Assign Merchant)</a>
        <a href="?page=view_admins">View Admins</a>
        <a href="?page=view_merchants">View Merchants</a>
        <a href="?page=view_users">View Users</a>
        <a href="admin_wallet.php">💰 Deposit / Withdraw</a>
        <a href="admin_request.php" class="sidebar-btn">📥 Deposit Requests</a>


        <form action="logout_form.php" method="post">
            <button type="submit" style="
            display: block;
            padding: 10px;
            margin: 8px 0;
            text-decoration: none;
            background: #34495e;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: background 0.3s;
        " onmouseover="this.style.background='#1abc9c'" onmouseout="this.style.background='#34495e'">
                🚪 Logout
            </button>
        </form>

        </form>

    </div>

    <div class="content">
        <?php if (!empty($message)): ?>
            <p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($page == "register_admin"): ?>
            <div class="form-box">
                <h3>Register Admin</h3>
                <form method="POST">
                    <input type="hidden" name="register_type" value="admin">
                    <input type="text" name="name" placeholder="Admin Name" required>
                    <input type="email" name="email" placeholder="Admin Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Register Admin</button>
                </form>
            </div>

        <?php elseif ($page == "register_merchant"): ?>
            <div class="form-box">
                <h3>Register Merchant</h3>
                <form method="POST">
                    <input type="hidden" name="register_type" value="merchant">
                    <input type="text" name="name" placeholder="Merchant Name" required>
                    <input type="email" name="email" placeholder="Merchant Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Register Merchant</button>
                </form>
            </div>

        <?php elseif ($page == "register_user"): ?>
            <div class="form-box">
                <h3>Register User (Assign Merchant)</h3>
                <form method="POST">
                    <input type="hidden" name="register_type" value="user">
                    <input type="text" name="name" placeholder="User Name" required>
                    <input type="email" name="email" placeholder="User Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label>Select Merchant:</label>
                    <select name="merchant_id" required>
                        <option value="">-- Select Merchant --</option>
                        <?php
                        $merchants = $conn->query("SELECT id, name FROM users WHERE role_id=2");
                        while ($m = $merchants->fetch_assoc()):
                            ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit">Register User</button>
                </form>
            </div>

        <?php elseif (strpos($page, "view_") === 0): ?>
            <?php
            $role = ($page == "view_admins" ? 1 : ($page == "view_merchants" ? 2 : 3));
            $limit = 10;
            $currentPage = isset($_GET['p']) ? (int) $_GET['p'] : 1;
            if ($currentPage < 1)
                $currentPage = 1;
            $offset = ($currentPage - 1) * $limit;

            $search = isset($_GET['search']) ? trim($_GET['search']) : "";
            $totalUsers = countUsers($conn, $role, $search);
            $totalPages = ($totalUsers > 0) ? ceil($totalUsers / $limit) : 1;
            $users = getUsers($conn, $role, $search, $limit, $offset);
            ?>
            <h2><?= ucfirst(str_replace("view_", "", $page)) ?> Table</h2>
            <form method="GET" class="search-box">
                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by name or email...">
                <button type="submit">Search</button>
            </form>
            <table>
                <tr>
                    <?php if ($page == "view_users"): ?>
                        <th>Wallet Balance</th>
                    <?php else: ?>
                        <th>ID</th>
                    <?php endif; ?>
                    <th>Name</th>
                    <th>Email</th>


                    <?php if ($page == "view_merchants"): ?>
                        <th>Referral Code</th><?php endif; ?>

                    <?php if ($page == "view_users"): ?>
                        <th>Merchant</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <?php if ($page == "view_users"): ?>
                            <?php
                            // ✅ Fetch balance from wallets table
                            $uid = $row['id'];
                            $bal_sql = "SELECT (total_deposit - total_withdraw) AS balance FROM wallets WHERE user_id=?";
                            $stmtBal = $conn->prepare($bal_sql);
                            $stmtBal->bind_param("i", $uid);
                            $stmtBal->execute();
                            $stmtBal->bind_result($balance);
                            $stmtBal->fetch();
                            $stmtBal->close();
                            if ($balance === null)
                                $balance = 0;
                            ?>
                            <td>💰 <?= number_format($balance, 2) ?></td>
                        <?php else: ?>
                            <td><?= $row['id'] ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <?php if ($page == "view_merchants"): ?>
                            <td>
                                <?php
                                $stmt_ref = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
                                $stmt_ref->bind_param("i", $row['id']);
                                $stmt_ref->execute();
                                $stmt_ref->bind_result($ref_code);
                                $stmt_ref->fetch();
                                $stmt_ref->close();
                                echo htmlspecialchars($ref_code);
                                ?>
                            </td>
                        <?php endif; ?>

                        <td class="actions">
                            <button
                                onclick="openUpdatePopup('<?= $row['id'] ?>', '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['email']) ?>')"
                                class="btn upd-btn">✏️ Update</button>
                            <button class="btn del-btn" onclick="openDeletePopup(<?= $row['id'] ?>)">Delete</button>
                            <button onclick="openPasswordPopup('<?= $row['id'] ?>')" class="btn pwd-btn">🔑 Change
                                Password</button>
                            <button onclick="openWalletPopup(<?= $row['id']; ?>, '<?= htmlspecialchars($row['name']); ?>')">
                                💰 Wallet
                            </button>





                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= htmlspecialchars($page) ?>&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                        class="<?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <h1>Admin</h1>
            <p>Select an option from the sidebar.</p>
        <?php endif; ?>





    </div>

    <!-- Popups -->
    <div class="popup" id="updatePopup">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup('updatePopup')">X</span>
            <h3>Update User</h3>
            <form method="POST" action="table.php">
                <input type="hidden" name="id" id="upd_id">
                <label>Name</label>
                <input type="text" name="name" id="upd_name" required>
                <label>Email</label>
                <input type="email" name="email" id="upd_email" required>
                <button type="submit" name="update_user">Update</button>
            </form>
        </div>
    </div>

    <div class="popup" id="deletePopup">
        <div class="popup-content center">
            <span class="close-btn" onclick="closePopup('deletePopup')">X</span>
            <h3>Are you sure you want to delete this user?</h3>
            <form method="POST">
                <!-- This hidden field stores the user ID -->
                <input type="hidden" name="id" id="del_id">

                <button type="submit" name="delete_user" class="btn del-btn">Yes, Delete</button>
                <button type="button" onclick="closePopup('deletePopup')">Cancel</button>
            </form>
        </div>
    </div>



    <div class="popup" id="passwordPopup">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup('passwordPopup')">X</span>
            <h3>Change Password</h3>
            <form method="POST" action="table.php">
                <input type="hidden" name="id" id="pwd_id">
                <label>New Password</label>
                <input type="password" name="new_password" required>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>
    </div>
    <!-- Wallet Popup -->
    <div class="popup" id="walletPopup">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup('walletPopup')">X</span>
            <h3>Manage Wallet</h3>

            <p id="walletUserName" style="font-weight:bold;"></p>

            <form method="POST" action="wallet_functions.php">
                <input type="hidden" name="user_id" id="wallet_user_id">

                <label>Amount:</label>
                <input type="number" name="amount" step="0.01" required>

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

        function openDeletePopup(id) {
            document.getElementById("del_id").value = id; // 👈 puts user ID inside the hidden field
            document.getElementById("deletePopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }


        // Open Password Popup
        function openPasswordPopup(id) {
            document.getElementById("pwd_id").value = id;
            document.getElementById("passwordPopup").style.display = "flex";
        }

        // Open Wallet Popup
        function openWalletPopup(userId, userName) {
            document.getElementById('wallet_user_id').value = userId;
            document.getElementById('walletUserName').textContent = "User: " + userName;
            document.getElementById('walletPopup').style.display = 'flex';
        }

        // Close Popup
        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        // Close popup when clicking outside of it
        window.onclick = function (event) {
            const popups = document.getElementsByClassName('popup');
            for (let i = 0; i < popups.length; i++) {
                if (event.target === popups[i]) {
                    popups[i].style.display = 'none';
                }
            }
        };
    </script>

</body>

</html>