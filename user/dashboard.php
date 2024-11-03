<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Make sure the user is logged in
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user information
function get_user_info($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$user_info = get_user_info($user_id);
$username = $user_info['username'];

// Get user stats
$active_auctions_count = get_user_active_auctions_count($user_id);
$won_auctions_count = get_user_won_auctions_count($user_id);
$current_bids_count = get_user_current_bids_count($user_id);
$favorite_auctions_count = get_user_favorite_auctions_count($user_id);

// Get user's recent bids
$recent_bids = get_user_recent_bids($user_id, 6);

// Get user's recent auctions
$user_auctions = get_user_recent_auctions($user_id, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Luxury Auction</title>
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 250px)); /* Changed to auto-fit and max width */
            gap: 20px;
            margin-bottom: 2rem;
            justify-content: center; /* Center the grid items */
            width: 100%;
            padding: 0 20px; /* Add padding for smaller screens */
        }
        .stat-box {
            background-color: var(--color-primary);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px #c9a55c;
        }

        .stat-box h3 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            margin-bottom: 10px;
        }

        .stat-box p {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }

        section {
            margin-bottom: 2rem;
        }

        section h2 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            font-size: 1.8rem;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 10px;
        }

        section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--color-accent);
        }

        .auction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .auction-card {
            background-color: var(--color-primary);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
            padding: 20px;
        }

        .auction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .auction-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .auction-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin-bottom: 10px;
        }

        .auction-card p {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-bottom: 10px;
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            text-align: center;
            width: 90%;
            margin-top: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .auction-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        
        <div class="stats-container">
            <div class="stat-box">
                <h3>Active Auctions</h3>
                <p><?php echo $active_auctions_count; ?></p>
            </div>
            <div class="stat-box">
                <h3>Won Auctions</h3>
                <p><?php echo $won_auctions_count; ?></p>
            </div>
            <div class="stat-box">
                <h3>Current Bids</h3>
                <p><?php echo $current_bids_count; ?></p>
            </div>
            <div class="stat-box">
                <h3>Favorite Auctions</h3>
                <p><?php echo $favorite_auctions_count; ?></p>
            </div>
        </div>
        
        <section>
            <h2>Your Recent Bids</h2>
            <?php if (empty($recent_bids)): ?>
                <p>You haven't placed any bids recently.</p>
            <?php else: ?>
                <div class="auction-grid">
                    <?php foreach ($recent_bids as $bid): ?>
                        <div class="auction-card">
                            <img src="<?php echo htmlspecialchars($bid['image_path']); ?>" alt="<?php echo htmlspecialchars($bid['title']); ?>">
                            <h3><?php echo htmlspecialchars($bid['title']); ?></h3>
                            <p class="price">Your Bid: $<?php echo number_format($bid['amount'], 2); ?></p>
                            <p class="end-time">Ends: <?php echo date('Y-m-d H:i:s', strtotime($bid['end_time'])); ?></p>
                            <a href="../public/auction.php?id=<?php echo $bid['product_id']; ?>" class="btn btn-primary">View Auction</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Your Recent Auctions</h2>
            <?php if (empty($user_auctions)): ?>
                <p>You haven't created any auctions recently.</p>
            <?php else: ?>
                <div class="auction-grid">
                    <?php foreach ($user_auctions as $auction): ?>
                        <div class="auction-card">
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                            <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                            <p class="price">Current Bid: $<?php echo number_format($auction['current_bid'] ?? $auction['start_price'], 2); ?></p>
                            <p class="end-time">Ends: <?php echo date('Y-m-d H:i:s', strtotime($auction['end_time'])); ?></p>
                            <p class="status">Status: <?php echo $auction['status']; ?></p>
                            <a href="../public/auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-primary">View Auction</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>