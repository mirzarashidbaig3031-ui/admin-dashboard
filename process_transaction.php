<?php
session_start();
include "db.php"; // DB connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_POST['user_id']);
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);

    if ($amount > 0 && ($type === "deposit" || $type === "withdraw")) {
        $sql = "INSERT INTO transactions (user_id, type, amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isd", $user_id, $type, $amount);

        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Transaction successful!</p>";
        } else {
            echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Invalid input.</p>";
    }
}
?>