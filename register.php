<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ✅ Get referral code from URL (not from input)
    $referral_input = isset($_GET['ref']) ? trim($_GET['ref']) : null;

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: register.html?error=duplicate");
        exit;
    }
    $check->close();

    // Default role_id for normal user
    $role_id = 3;
    $merchant_id = null;

    // ✅ If referral code is in URL, find merchant
    if (!empty($referral_input)) {
        $findMerchant = $conn->prepare("SELECT id FROM users WHERE referral_code = ? AND role_id = 2");
        $findMerchant->bind_param("s", $referral_input);
        $findMerchant->execute();
        $result = $findMerchant->get_result();
        $merchant = $result->fetch_assoc();
        $findMerchant->close();

        if ($merchant) {
            $merchant_id = $merchant['id'];
        }
    }

    // ✅ Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, referred_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $email, $password, $role_id, $referral_input);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();

    // ✅ If referral code is valid, link merchant and user
    if ($merchant_id) {
        $link = $conn->prepare("INSERT INTO merchant_users (merchant_id, user_id) VALUES (?, ?)");
        $link->bind_param("ii", $merchant_id, $user_id);
        $link->execute();
        $link->close();
    }

    // ✅ Redirect to user dashboard
    session_start();
    $_SESSION['user_id'] = $user_id;
    header("Location: user_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Registration Form</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            background: #4caf50;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #45a049;
        }

        /* Popup Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
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
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 {
            margin-bottom: 15px;
        }

        .modal-content button {
            background: #4caf50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background: #45a049;
        }

        .close {
            float: right;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <!-- Success/Error Popup -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('messageModal')">&times;</span>
            <h3 id="popupText"></h3>
            <button id="popupBtn">OK</button>
        </div>
    </div>

    <div class="container">
        <h2>Register</h2>

        <!-- ✅ Form now supports referral from URL -->
        <form action="register.php<?php echo isset($_GET['ref']) ? '?ref=' . $_GET['ref'] : ''; ?>" method="POST">
            <input type="text" name="name" placeholder="Enter Name" required />
            <input type="email" name="email" placeholder="Enter Email" required />
            <input type="password" name="password" placeholder="Enter Password" required />
            <input type="submit" value="Register" />
        </form>
    </div>

    <script>
        function showPopup(message, redirect = null) {
            document.getElementById("popupText").innerText = message;
            document.getElementById("messageModal").style.display = "flex";

            document.getElementById("popupBtn").onclick = function () {
                closeModal("messageModal");
                if (redirect) {
                    window.location.href = redirect;
                }
            };
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        // ✅ Check URL params
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get("success") === "1") {
            showPopup("Successfully Registered!", "table.php");
        } else if (urlParams.get("error") === "duplicate") {
            showPopup("This email is already registered!");
        } else if (urlParams.get("error") === "db") {
            showPopup("Database error. Please try again!");
        }

        // ✅ Clean URL (so popup doesn’t reopen on refresh)
        if (urlParams.has("success") || urlParams.has("error")) {
            window.history.replaceState(
                {},
                document.title,
                window.location.pathname
            );
        }
    </script>
</body>

</html>