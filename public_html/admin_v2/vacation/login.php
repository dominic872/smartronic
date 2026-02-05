<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare("SELECT id, password, fullname, roll FROM users WHERE username=?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $fullname, $roll);

    if ($stmt->fetch()) {
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['roll'] = $roll;
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Employee Login</title>
    <link rel="stylesheet" href="vacation_styles.css">
</head>
<body class="login_page">
  <div class="login-container">
    <img src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" alt="Smartronic Logo">
    <h2>Employee Login</h2>
    <?php if (isset($error)) echo '<div class="error">' . $error . '</div>'; ?>
    <form method="POST" autocomplete="off">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
