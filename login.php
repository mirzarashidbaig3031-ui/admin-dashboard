<?php
session_start();
include "db.php"; // ✅ Make sure db.php connects correctly

// ✅ Reset session if already logged in
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ Step 1: Prepare SQL and handle possible failure
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ SQL prepare failed: " . $conn->error);
    }

    // ✅ Step 2: Bind and execute safely
    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        die("❌ SQL execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // ✅ Step 3: Handle login logic
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role_id'] = $row['role_id'];
            $_SESSION['msg'] = "✅ Login successful!";

            // ✅ Redirect based on role
            switch ($row['role_id']) {
                case 1:
                    header("Location: manage_user.php");
                    break;
                case 2:
                    header("Location: merchant_dashboard.php");
                    break;
                case 3:
                    header("Location: user_dashboard.php");
                    break;
                default:
                    $_SESSION['msg'] = "❌ Unknown role!";
                    header("Location: login.php");
                    break;
            }
            exit();

        } else {
            $_SESSION['msg'] = "❌ Invalid email or password!";
        }
    } else {
        $_SESSION['msg'] = "❌ Invalid email or password!";
    }

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            background: #f2f2f2;
        }

        .login-box {
            width: 350px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-box input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .login-box button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-box button.logout {
            background: #dc3545;
        }

        .login-box button:hover {
            opacity: 0.9;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 20px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>Login</h2>

        <form method="POST">
            <input type="text" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <!-- ✅ Register Button -->
        <form action="register.html" method="GET">
            <button type="submit"
                style="background: #28a745; color: white; width: 100%; padding: 10px; border: none; border-radius: 6px; margin-top: 10px; cursor: pointer;">
                Register
            </button>
        </form>

        <?php if (isset($_SESSION['email'])): ?>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout">Logout</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Popup -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('messageModal')">&times;</span>
            <h3 id="popupText"></h3>
        </div>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("popupText").innerText = "<?= $_SESSION['msg'] ?>";
                document.getElementById("messageModal").style.display = "flex";
            });
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <script>
        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }
    </script>
</body>

</html>