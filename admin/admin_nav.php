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

    .admin-nav {
        background-color: var(--color-primary);
        padding: 15px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .admin-nav ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
    }

    .admin-nav li {
        margin: 0 15px;
    }

    .admin-nav a {
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

    .admin-nav a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: all var(--transition-speed) ease;
    }

    .admin-nav a:hover::before {
        left: 100%;
    }

    .admin-nav a:hover, .admin-nav a.active {
        background-color: var(--color-accent);
        color: var(--color-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
    }

    @media (max-width: 768px) {
        .admin-nav ul {
            flex-direction: column;
            align-items: center;
        }
        .admin-nav li {
            margin: 10px 0;
        }
    }
</style>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php" <?php echo ($_SERVER['PHP_SELF'] == "/admin/dashboard.php") ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="pending_auctions.php" <?php echo ($_SERVER['PHP_SELF'] == "/admin/pending_auctions.php") ? 'class="active"' : ''; ?>>Pending Auctions</a></li>
        <li><a href="active_auctions.php" <?php echo ($_SERVER['PHP_SELF'] == "/admin/active_auctions.php") ? 'class="active"' : ''; ?>>Active Auctions</a></li>
        <li><a href="ended_auctions.php" <?php echo ($_SERVER['PHP_SELF'] == "/admin/ended_auctions.php") ? 'class="active"' : ''; ?>>Ended Auctions</a></li>
        
        <li><a href="manage_users.php" <?php echo ($_SERVER['PHP_SELF'] == "/admin/manage_users.php") ? 'class="active"' : ''; ?>>Manage Users</a></li>
        <li><a href="../public/logout.php">Logout</a></li>
    </ul>
</nav>