<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Vintage Auction</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-background: #1a1a1a;
            --color-primary: #2c2c2c;
            --color-secondary: #3a3a3a;
            --color-accent: #c9a55c;
            --color-text: #ffffff;
            --color-text-muted: #a0a0a0;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            margin: 0;
            padding: 0;
        }

        .main-nav {
            background-color: var(--color-primary);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .main-nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        .main-nav li {
            margin: 0 15px;
        }

        .main-nav a {
            color: var(--color-text);
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            padding: 10px 15px;
            border-radius: 25px;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .main-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all var(--transition-speed) ease;
        }

        .main-nav a:hover::before {
            left: 100%;
        }

        .main-nav a:hover, .main-nav a.active {
            background-color: var(--color-accent);
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .content-wrapper {
            padding-top: 100px; /* Adjust this value to match the height of the navigation bar */
        }

        @media (max-width: 768px) {
            .main-nav ul {
                flex-direction: column;
                align-items: center;
            }
            .main-nav li {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <ul>
            <?php if (!is_logged_in()): ?>
                <li><a href="<?php echo $base_url; ?>" class="nav-link">Home</a></li>
                <li><a href="<?php echo $base_url; ?>public/login.php" class="nav-link">Login</a></li>
                <li><a href="<?php echo $base_url; ?>public/register.php" class="nav-link">Register</a></li>
            <?php else: ?>
                <li><a href="<?php echo $base_url; ?>user/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?php echo $base_url; ?>user/listing.php" class="nav-link">Browse Auctions</a></li>
                <li><a href="<?php echo $base_url; ?>user/post_product.php" class="nav-link">Post Auction</a></li>
                <li><a href="<?php echo $base_url; ?>user/won_auctions.php" class="nav-link">Won Auctions</a></li>
                <li><a href="<?php echo $base_url; ?>user/lost_auctions.php" class="nav-link">Lost Auctions</a></li>
                <li><a href="<?php echo $base_url; ?>user/active_auctions.php" class="nav-link">Active Auctions</a></li>
                <li><a href="<?php echo $base_url; ?>user/favorites.php" class="nav-link">Favorites</a></li>
                <li><a href="<?php echo $base_url; ?>public/logout.php" class="nav-link">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="content-wrapper">
        <!-- Your page content goes here -->
    </div>
</body>
</html>