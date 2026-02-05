<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php'; // contains $mysqli = new mysqli(...);
$error = '';

//$password = 'Smart@2009'; // your desired password
//$hash = password_hash($password, PASSWORD_DEFAULT);

//echo $hash;


// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = $_POST['username'] ?? '';
    $password   = $_POST['password'] ?? '';
    $redirectTo = $_GET['redirect'] ?? 'form.php';

    // Prepare SQL query
    $stmt = $conn->prepare("SELECT id, username, password, fullname, roll FROM users WHERE username=?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_username, $hash, $fullname, $roll);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hash)) {
            // Set cookies for 1 day
            setcookie('auth_user', $db_username, time() + 86400, "/");
            setcookie('auth_role', $roll, time() + 86400, "/");
            setcookie('auth_name', $fullname, time() + 86400, "/");

            header("Location: $redirectTo");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smartronic | Employee's Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/vacation_styles.css">
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
