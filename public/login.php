<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header("Location: ../user/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned'] == 1) {
                $error = "Your account has been banned. Please contact the administrator.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'] == 1;

                if ($_SESSION['is_admin']) {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred during login. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vintage Auction</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-background: #1a1a1a;
            --color-primary: #2c2c2c;
            --color-secondary: #3a3a3a;
            --color-accent: #c9a55c;
            --color-text: #ffffff;
            --color-text-muted: #a0a0a0;
            --color-success: #4caf50;
            --color-danger: #f44336;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease forwards;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            padding-bottom: 15px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--color-accent);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-accent);
            background-color: var(--color-secondary);
            color: var(--color-text);
            border-radius: 25px;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }

        input:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--color-accent);
        }

        button {
            width: 100%;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--color-danger);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--color-text-muted);
        }

        .register-link a {
            color: var(--color-accent);
            text-decoration: none;
            transition: color var(--transition-speed) ease;
        }

        .register-link a:hover {
            color: #e0c179;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <div class="login-container">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>