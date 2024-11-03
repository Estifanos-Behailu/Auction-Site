<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header("Location: ../public/login.php");
    exit();
}

function get_pending_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE start_time > NOW() AND is_approved = 0");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_active_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE start_time <= NOW() AND end_time > NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_ended_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE end_time <= NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_total_users_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

$pending_count = get_pending_auctions_count();
$active_count = get_active_auctions_count();
$ended_count = get_ended_auctions_count();
$users_count = get_total_users_count();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Luxury Auction</title>
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
            flex-grow: 1;
            padding: 2rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all var(--transition-speed) ease;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            font-size: 3rem;
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

        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .summary-item {
            background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-primary) 100%);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .summary-item::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(45deg);
            transition: all var(--transition-speed) ease;
            opacity: 0;
        }

        .summary-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 12px #c9a55c;
        }

        .summary-item:hover::before {
            opacity: 1;
        }

        .summary-item h2 {
            font-family: 'Playfair Display', serif;
            margin-top: 0;
            font-size: 1.5rem;
            color: var(--color-accent);
            position: relative;
            z-index: 1;
        }

        .summary-item p {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
            color: var(--color-text);
            position: relative;
            z-index: 1;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            transition: all var(--transition-speed) ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all var(--transition-speed) ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .welcome-message {
            font-style: italic;
            color: var(--color-text-muted);
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.1rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animated {
            animation: fadeInUp 0.6s ease forwards;
        }

        @media (max-width: 768px) {
            .dashboard-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>
    <main class="main-content">
        <h1 class="animated">Admin Dashboard</h1>
        <p class="welcome-message animated" style="animation-delay: 0.2s;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <div class="dashboard-summary">
            <div class="summary-item animated" style="animation-delay: 0.3s;">
                <h2>Pending Auctions</h2>
                <p><?php echo $pending_count; ?></p>
                <a href="pending_auctions.php" class="btn">View Pending</a>
            </div>
            <div class="summary-item animated" style="animation-delay: 0.4s;">
                <h2>Active Auctions</h2>
                <p><?php echo $active_count; ?></p>
                <a href="active_auctions.php" class="btn">View Active</a>
            </div>
            <div class="summary-item animated" style="animation-delay: 0.5s;">
                <h2>Ended Auctions</h2>
                <p><?php echo $ended_count; ?></p>
                <a href="ended_auctions.php" class="btn">View Ended</a>
            </div>
            <div class="summary-item animated" style="animation-delay: 0.6s;">
                <h2>Total Users</h2>
                <p><?php echo $users_count; ?></p>
                <a href="manage_users.php" class="btn">Manage Users</a>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const animatedElements = document.querySelectorAll('.animated');
            animatedElements.forEach((el, index) => {
                el.style.opacity = '0';
                setTimeout(() => {
                    el.style.opacity = '1';
                }, 100 * (index + 1));
            });
        });

        // Add parallax effect to summary items
        document.addEventListener('mousemove', (e) => {
            const cards = document.querySelectorAll('.summary-item');
            const mouseX = e.clientX;
            const mouseY = e.clientY;

            cards.forEach((card) => {
                const rect = card.getBoundingClientRect();
                const cardX = rect.left + rect.width / 2;
                const cardY = rect.top + rect.height / 2;

                const angleX = (mouseY - cardY) / 30;
                const angleY = (cardX - mouseX) / 30;

                card.style.transform = `rotateX(${angleX}deg) rotateY(${angleY}deg) scale(1.05)`;
            });
        });

        // Reset card rotation when mouse leaves
        document.addEventListener('mouseleave', () => {
            const cards = document.querySelectorAll('.summary-item');
            cards.forEach((card) => {
                card.style.transform = 'rotateX(0) rotateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>