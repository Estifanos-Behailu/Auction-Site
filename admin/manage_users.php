<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_ban'])) {
        $user_id = $_POST['user_id'];
        $is_banned = $_POST['is_banned'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_banned, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "User ban status updated successfully.";
        } else {
            $error_message = "Failed to update user ban status.";
        }
    }
}

try {
    $query = "SELECT * FROM users ORDER BY username ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in manage_users.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching users. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Luxury Auction Admin</title>
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
        }

        .main-content {
            padding: 2rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: var(--color-primary);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--color-secondary);
        }

        th {
            background-color: var(--color-secondary);
            color: var(--color-accent);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:hover {
            background-color: rgba(201, 165, 92, 0.1);
            transition: all var(--transition-speed) ease;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all var(--transition-speed) ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .success, .error {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--color-success);
        }

        .error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--color-danger);
        }

        #searchInput {
            width: 100%;
            max-width: 500px;
            padding: 12px;
            border: 1px solid var(--color-accent);
            background-color: var(--color-secondary);
            color: var(--color-text);
            border-radius: 25px;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            margin: 0 auto;
            display: block;
        }


        #searchInput:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--color-accent);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>
    <div class="main-content">
        <h1 class="animated">Manage Users</h1>
        
        <?php if (isset($success_message)): ?>
            <p class="success animated"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="error animated"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <input type="text" id="searchInput" placeholder="Search by username or email" class="animated">
        
        <?php if (empty($users)): ?>
            <p class="animated">No users found.</p>
        <?php else: ?>
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['is_banned'] ? 'Banned' : 'Active'; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_banned" value="<?php echo $user['is_banned']; ?>">
                                    <button type="submit" name="toggle_ban" class="btn">
                                        <?php echo $user['is_banned'] ? 'Unban User' : 'Ban User'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('usersTable');
        const rows = table.getElementsByTagName('tr');

        const animateElements = document.querySelectorAll('.animated');
        animateElements.forEach((el, index) => {
            el.style.animationDelay = `${0.1 * (index + 1)}s`;
        });

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 1; i < rows.length; i++) {
                const username = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();

                if (username.includes(searchTerm) || email.includes(searchTerm)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    });
    </script>
</body>
</html>