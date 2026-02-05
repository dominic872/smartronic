<?php
ob_start();
session_start();

$usersFile = 'users.json';
$error = '';

// Get redirect target if present
$redirectTo = $_GET['redirect'] ?? 'form.php'; // default if not set

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                // Set cookies
                setcookie('auth_user', $username, time() + 86400, "/");
                setcookie('auth_role', $user['role'], time() + 86400, "/");
                setcookie('auth_name', $user['name'], time() + 86400, "/");

                // Redirect to the original page
                header("Location: $redirectTo");
                exit();
            }
        }

        $error = "Invalid username or password";
    } else {
        $error = "User data file not found";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    .login-container {
      width: 90%;
      max-width: 400px;
      background: white;
      padding: 24px 20px 32px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      position: relative;
    }

    .logo {
      display: flex;
      justify-content: center;
      margin-bottom: 24px;
    }

    .logo img {
      width: 180px;
      height: auto;
    }

    .login-container label {
      font-weight: 600;
      margin-bottom: 6px;
      display: block;
    }

    .login-container input {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
      box-sizing: border-box;
    }

    .formSubmitBtn {
      background-color: #333;
      color: white;
      font-size: 18px;
      padding: 10px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
    }

    .formSubmitBtn i {
      font-size: 16px;
    }

    @media screen and (max-height: 500px) {
      body {
        align-items: flex-start;
        padding-top: 40px;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="logo">
      <img src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" alt="Smartronic Logo" />
    </div>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required />

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />

      <button type="submit" class="formSubmitBtn">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
    </form>
  </div>

  <script>
    function login() {
      const username = document.getElementById("username").value;
      const password = document.getElementById("password").value;
      // You can add your login logic here, like calling an API or checking credentials
     // alert("Logged in as: " + username);
    }
  </script>
</body>
</html>
