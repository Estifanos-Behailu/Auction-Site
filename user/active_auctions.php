<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/validation.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT p.*, 
                     MAX(b.amount) as user_max_bid,
                     (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as highest_bid
              FROM products p
              JOIN bids b ON p.id = b.product_id
              WHERE p.end_time > NOW()
              AND b.bidder_id = ?
              GROUP BY p.id
              ORDER BY p.end_time ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_active_auctions = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in active_auctions.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching active auctions. Please try again later.";
}
?>

php

Copy
<?php
// PHP code remains exactly the same up to the DOCTYPE
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Auctions - Vintage Auction</title>
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
            --color-warning: #ff9800;
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

        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-item {
            background-color: var(--color-primary);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
            padding: 20px;
        }

        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-item h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin: 15px 0;
        }

        .product-item p {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-bottom: 15px;
        }

        .bid-info {
            background-color: var(--color-secondary);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .outbid-warning {
            color: var(--color-warning);
            font-weight: bold;
            padding: 10px;
            background-color: rgba(255, 152, 0, 0.1);
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }

        .view-auction-btn {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            text-align: center;
            width: 90%;
        }

        .view-auction-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .error-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--color-danger);
        }

        .info-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            background-color: rgba(201, 165, 92, 0.1);
            color: var(--color-accent);
        }

        .no-image {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-secondary);
            color: var(--color-text-muted);
            font-size: 1rem;
            border-radius: 8px;
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
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1 class="animated">Active Auctions</h1>

        <?php if (isset($error_message)): ?>
            <p class="error-message animated"><?php echo $error_message; ?></p>
        <?php elseif (empty($user_active_auctions)): ?>
            <p class="info-message animated">You don't have any active bids at the moment.</p>
        <?php else: ?>
            <div class="product-list">
                <?php foreach ($user_active_auctions as $auction): ?>
                    <div class="product-item animated">
                        <?php if (!empty($auction['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                        <div class="bid-info">
                            <p>Your Highest Bid: $<?php echo number_format($auction['user_max_bid'], 2); ?></p>
                            <p>Current Highest Bid: $<?php echo number_format($auction['highest_bid'], 2); ?></p>
                        </div>
                        <?php if ($auction['user_max_bid'] < $auction['highest_bid']): ?>
                            <p class="outbid-warning">You've been outbid!</p>
                        <?php endif; ?>
                        <p>Auction End Time: <?php echo htmlspecialchars($auction['end_time']); ?></p>
                        <a href="../public/auction.php?id=<?php echo urlencode($auction['id']); ?>" class="view-auction-btn">View Auction</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const animateElements = document.querySelectorAll('.animated');
        animateElements.forEach((el, index) => {
            el.style.animationDelay = `${0.1 * (index + 1)}s`;
        });
    });
    </script>
</body>
</html>