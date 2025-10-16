<?php
session_start();
include "db.php";

// Redirect if not logged in
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
  header("Location: admin_login.php");
  exit;
}

$message = "";

// ================= HANDLE FORM ACTIONS =================

// --- Update user ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'], $_POST['name'], $_POST['email']) && !isset($_POST['old_password'])) {
  $id = intval($_POST['id']);
  $name = $_POST['name'];
  $email = $_POST['email'];

  $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
  $stmt->bind_param("ssi", $name, $email, $id);
  if ($stmt->execute()) {
    $message = "✅ User updated successfully!";
  } else {
    $message = "❌ Error updating user: " . $conn->error;
  }
  $stmt->close();
}

// --- Delete user ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id']) && !isset($_GET['search'])) {
  $id = intval($_GET['id']);

  // First delete child rows from merchant_users
  $conn->query("DELETE FROM merchant_users WHERE merchant_id=$id");

  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    $message = "✅ User deleted successfully!";
  } else {
    $message = "❌ Error deleting user: " . $conn->error;
  }
  $stmt->close();
}

// --- Change password ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password'], $_POST['id'])) {
  $id = intval($_POST['id']);
  $old_password = $_POST['old_password'];
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];

  if ($new_password !== $confirm_password) {
    $message = "❌ Passwords do not match!";
  } else {
    // Get existing password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($old_password, $user['password'])) {
      $hashed = password_hash($new_password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
      $stmt->bind_param("si", $hashed, $id);
      if ($stmt->execute()) {
        $message = "✅ Password updated successfully!";
      } else {
        $message = "❌ Error updating password: " . $conn->error;
      }
      $stmt->close();
    } else {
      $message = "❌ Old password is incorrect!";
    }
  }
}

// ================= FILTER + PAGINATION =================

// Role filter
$role = isset($_GET['role']) ? intval($_GET['role']) : 0;
$roleNames = [1 => "Admins", 2 => "Merchants", 3 => "Users"];
$roleTitle = isset($roleNames[$role]) ? $roleNames[$role] : "All Users";

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
  $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : "";
$where = "1=1";
$params = [];
$types = "";

if ($role > 0) {
  $where .= " AND role_id=?";
  $params[] = $role;
  $types .= "i";
}
if (!empty($search)) {
  $where .= " AND (name LIKE ? OR email LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $types .= "ss";
}

// Count
$sqlCount = "SELECT COUNT(*) as total FROM users WHERE $where";
$stmtCount = $conn->prepare($sqlCount);
if ($types)
  $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total_rows = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();
$total_pages = ceil($total_rows / $limit);

// Fetch
$sql = "SELECT id, name, email FROM users WHERE $where LIMIT ?, ?";
$paramsWithLimit = $params;
$typesWithLimit = $types . "ii";
$paramsWithLimit[] = $offset;
$paramsWithLimit[] = $limit;

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>

<head>
  <title><?= $roleTitle ?> Table</title>
  <style>
    body {
      font-family: Arial;
      background: #f4f4f9;
      padding: 20px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .msg {
      margin: 10px 0;
      padding: 8px;
      border-radius: 5px;
      background: #eafaf1;
      color: green;
    }

    .msg.error {
      background: #fdecea;
      color: red;
    }

    .search-box {
      display: flex;
      gap: 8px;
      margin-bottom: 10px;
    }

    .search-box input {
      padding: 6px;
      border-radius: 5px;
      border: 1px solid #ccc;
      flex: 1;
    }

    .search-box button {
      padding: 6px 15px;
      border: none;
      border-radius: 5px;
      background: #3498db;
      color: white;
      cursor: pointer;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 10px;
    }

    th,
    td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: center;
    }

    th {
      background: #3498db;
      color: white;
    }

    button {
      padding: 5px 10px;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }

    .del-btn {
      background: red;
      color: white;
    }

    .upd-btn {
      background: orange;
      color: white;
    }

    .pwd-btn {
      background: #2ecc71;
      color: white;
    }

    .pagination {
      margin-top: 15px;
      text-align: center;
    }

    .pagination a {
      padding: 6px 12px;
      border: 1px solid #3498db;
      border-radius: 5px;
      text-decoration: none;
      color: #3498db;
      margin: 2px;
    }

    .pagination a.active {
      background: #3498db;
      color: white;
    }
  </style>
</head>

<body>
  <div class="header">
    <h2><?= $roleTitle ?> Table</h2>
    <form method="GET" class="search-box">
      <input type="hidden" name="role" value="<?= $role ?>">
      <input type="text" name="search" placeholder="Search by Name or Email" value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>
  </div>

  <?php if ($message): ?>
    <div class="msg <?= strpos($message, '❌') !== false ? 'error' : '' ?>"><?= $message ?></div>
  <?php endif; ?>

  <table>
    <tr>
      <th>Wallet Balance</th>
      <th>Username</th>
      <th>Email</th>
      <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
      <?php
      // ✅ Calculate wallet balance
      $user_id = $row['id'];
      $balance_sql = "
          SELECT 
              IFNULL(SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END),0)
              -
              IFNULL(SUM(CASE WHEN type='withdraw' THEN amount ELSE 0 END),0) 
              AS balance
          FROM transactions
          WHERE user_id = $user_id
      ";
      $balance_result = $conn->query($balance_sql);
      $balance_row = $balance_result->fetch_assoc();
      $balance = $balance_row['balance'] ?? 0;
      ?>
      <tr>
        <td>💰 <?= number_format($balance, 2) ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td>
          <button class="upd-btn"
            onclick="openUpdatePopup('<?= $row['id'] ?>','<?= htmlspecialchars($row['name']) ?>','<?= htmlspecialchars($row['email']) ?>')">✏️
            Update</button>
          <button class="del-btn" onclick="openDeletePopup('<?= $row['id'] ?>')">🗑 Delete</button>
          <button class="pwd-btn" onclick="openPasswordPopup('<?= $row['id'] ?>')">🔑 Change Password</button>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <!-- Pagination -->
  <div class="pagination">
    <?php if ($page > 1): ?><a href="?role=<?= $role ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">«
        Prev</a><?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="?role=<?= $role ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>"
        class="<?= ($i == $page ? 'active' : '') ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?><a
        href="?role=<?= $role ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next »</a><?php endif; ?>
  </div>

  <!-- ================= POPUPS ================= -->
  <!-- Update -->
  <div id="updatePopup"
    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;width:300px;">
      <h3>Update User</h3>
      <form method="POST" action="table.php">
        <input type="hidden" name="id" id="update_id">
        <label>Name:</label><input type="text" name="name" id="update_name" required><br><br>
        <label>Email:</label><input type="email" name="email" id="update_email" required><br><br>
        <button type="submit">Save</button>
        <button type="button" onclick="closePopup('updatePopup')">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Delete -->
  <div id="deletePopup"
    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;width:300px;text-align:center;">
      <h3>Confirm Delete</h3>
      <form method="GET" action="table.php">
        <input type="hidden" name="id" id="delete_id">
        <button type="submit" style="background:red;color:white;">Yes, Delete</button>
        <button type="button" onclick="closePopup('deletePopup')">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Password -->
  <div id="passwordPopup"
    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;width:300px;">
      <h3>Change Password</h3>
      <form method="POST" action="table.php">
        <input type="hidden" name="id" id="pwd_id">
        <label>Old Password:</label><input type="password" name="old_password" required><br><br>
        <label>New Password:</label><input type="password" name="new_password" required><br><br>
        <label>Confirm Password:</label><input type="password" name="confirm_password" required><br><br>
        <button type="submit">Update</button>
        <button type="button" onclick="closePopup('passwordPopup')">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    function openUpdatePopup(id, name, email) {
      document.getElementById('update_id').value = id;
      document.getElementById('update_name').value = name;
      document.getElementById('update_email').value = email;
      document.getElementById('updatePopup').style.display = 'flex';
    }
    function openDeletePopup(id) {
      document.getElementById('delete_id').value = id;
      document.getElementById('deletePopup').style.display = 'flex';
    }
    function openPasswordPopup(id) {
      document.getElementById('pwd_id').value = id;
      document.getElementById('passwordPopup').style.display = 'flex';
    }
    function closePopup(id) {
      document.getElementById(id).style.display = 'none';
    }
  </script>
</body>

</html>