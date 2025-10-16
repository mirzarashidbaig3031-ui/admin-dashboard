<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
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
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            width: 350px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background: #c0392b;
        }

        .msg {
            text-align: center;
            margin-top: 10px;
            color: green;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Admin Registration</h2>
        <form action="register_admin_action.php" method="POST">
            <input type="text" name="name" placeholder="Enter Admin Name" required>
            <input type="email" name="email" placeholder="Enter Admin Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <input type="submit" value="Register Admin">
        </form>

        <!-- Success / Error Message -->
        <?php if (isset($_GET['success'])): ?>
            <p class="msg">✅ Admin Registered Successfully!</p>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
            <p class="error">❌ Email already exists!</p>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'db'): ?>
            <p class="error">❌ Database error. Try again!</p>
        <?php endif; ?>
    </div>
</body>

</html>