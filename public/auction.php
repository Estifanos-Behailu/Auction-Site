<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/validation.php';

// Get the auction ID from the URL
$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle favoriting
if (isset($_POST['toggle_favorite']) && is_logged_in() && !is_admin()) {
    $user_id = $_SESSION['user_id'];
    $is_favorited = toggle_favorite($user_id, $auction_id);
    $success_message = $is_favorited ? "Auction added to favorites." : "Auction removed from favorites.";
}

// Handle auction deletion
if (isset($_POST['delete_auction']) && is_logged_in()) {
    try {
        $check_owner_query = "SELECT seller_id FROM products WHERE id = ?";
        $check_stmt = $conn->prepare($check_owner_query);
        $check_stmt->bind_param("i", $auction_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $auction = $result->fetch_assoc();

        if (!$auction || $auction['seller_id'] != $_SESSION['user_id']) {
            throw new Exception("You don't have permission to delete this auction.");
        }

        $check_bids_query = "SELECT COUNT(*) as bid_count FROM bids WHERE product_id = ?";
        $bid_stmt = $conn->prepare($check_bids_query);
        $bid_stmt->bind_param("i", $auction_id);
        $bid_stmt->execute();
        $bid_result = $bid_stmt->get_result();
        $bid_count = $bid_result->fetch_assoc()['bid_count'];

        if ($bid_count > 0) {
            throw new Exception("Cannot delete auction with existing bids.");
        }

        $delete_query = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $auction_id);
        $delete_stmt->execute();

        if ($delete_stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Auction deleted successfully.";
            header("Location: ../user/dashboard.php");
            exit();
        } else {
            throw new Exception("Failed to delete the auction.");
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch auction details
try {
    $query = "SELECT p.*, u.username as seller_name,
                     (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as highest_bid
              FROM products p
              JOIN users u ON p.seller_id = u.id
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $auction = $result->fetch_assoc();

    if (!$auction) {
        throw new Exception("Auction not found");
    }

    $is_owner = is_logged_in() && $_SESSION['user_id'] == $auction['seller_id'];
    $is_favorited = is_logged_in() && !is_admin() ? is_favorite($_SESSION['user_id'], $auction_id) : false;
    $auction_ended = strtotime($auction['end_time']) < time();
    $is_admin = is_admin();

    if ($is_admin) {
        $bids_query = "SELECT b.*, u.username 
                       FROM bids b 
                       JOIN users u ON b.bidder_id = u.id 
                       WHERE b.product_id = ? 
                       ORDER BY b.amount DESC";
        $bids_stmt = $conn->prepare($bids_query);
        $bids_stmt->bind_param("i", $auction_id);
        $bids_stmt->execute();
        $all_bids = $bids_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $seller_query = "SELECT * FROM users WHERE id = ?";
        $seller_stmt = $conn->prepare($seller_query);
        $seller_stmt->bind_param("i", $auction['seller_id']);
        $seller_stmt->execute();
        $seller_info = $seller_stmt->get_result()->fetch_assoc();
    }

} catch (Exception $e) {
    error_log("Error in auction.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching auction details. Please try again later.";
}

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid_amount'])) {
    if (!is_logged_in()) {
        $error_message = "You must be logged in to place a bid.";
    } elseif ($auction_ended) {
        $error_message = "This auction has ended.";
    } else {
        $bid_amount = floatval($_POST['bid_amount']);
        if ($bid_amount <= ($auction['highest_bid'] ?? $auction['start_price'])) {
            $error_message = "Your bid must be higher than the current highest bid.";
        } else {
            if (place_bid($auction_id, $_SESSION['user_id'], $bid_amount)) {
                $success_message = "Your bid has been placed successfully!";
                $auction = get_auction_details($auction_id);
                $current_bid = $auction['current_bid'];
                $user_highest_bid = get_user_highest_bid($auction_id, $_SESSION['user_id']);
            } else {
                $error_message = "There was an error placing your bid. Please try again.";
            }
        }
    }
}

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/auction-site/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($auction['title']); ?> - Vintage Auction</title>
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
        background-color: var(--color-background);
        font-family: 'Roboto', sans-serif;
        color: var(--color-text);
        margin: 0;
        padding: 0;
    }

    .notifications {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        width: 80%;
        max-width: 600px;
        text-align: center;
    }

    .success, .error {
        margin-bottom: 10px;
        padding: 15px;
        border-radius: 5px;
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .main-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 2rem;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
        border-radius: 10px;
        margin: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 1200px;
        margin: 40px auto;
    }

    .auction-title {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: var(--color-accent);
        margin-bottom: 1rem;
        animation: slideIn 1s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .auction-image-container {
        width: 100%;
        max-width: 600px;
        margin-bottom: 2rem;
    }

    .auction-image {
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform var(--transition-speed) ease;
    }

    .auction-details-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        width: 100%;
        max-width: 800px;
    }

    .auction-detail-card {
        background-color: var(--color-primary);
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease-in-out;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .auction-detail-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(201, 165, 92, 0.3);
        background: linear-gradient(135deg, var(--color-accent), var(--color-primary));
    }

    .auction-detail-card p {
        margin: 0.5rem 0;
        color: var(--color-text-muted);
    }

    .auction-detail-card strong {
        color: var(--color-text);
    }

    form {
        margin-top: 2rem;
    }

    input[type="number"] {
        width: 97%;
        padding: 12px;
        border: 1px solid var(--color-accent);
        background-color: var(--color-secondary);
        color: var(--color-text);
        border-radius: 25px;
        margin-bottom: 1rem;
    }

    button {
        display: inline-block;
        background: linear-gradient(to right, var(--color-accent), #e0c179);
        color: var(--color-primary);
        padding: 12px 24px;
        border-radius: 25px;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all var(--transition-speed) ease;
        width: 100%;
        margin-bottom: 1rem;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
    }

    .favorite-button {
        background: transparent;
        border: 2px solid var(--color-accent);
        color: var(--color-accent);
    }

    .unfavorite-button {
        background: var(--color-accent);
        color: var(--color-primary);
    }

    .delete-button {
        background: linear-gradient(to right, var(--color-danger), #ff7961);
    }

    .back-button {
        display: inline-block;
        color: var(--color-accent);
        text-decoration: none;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .error {
        background-color: rgba(244, 67, 54, 0.1);
        color: var(--color-danger);
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .success {
        background-color: rgba(76, 175, 80, 0.1);
        color: var(--color-success);
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        margin-top: 3.25rem;
    }

    .admin-nav {
        display: flex;
        justify-content: center;
        background-color: var(--color-secondary);
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .admin-nav ul {
        list-style: none;
        padding: 0;
        display: flex;
        gap: 2rem;
    }

    .admin-nav a {
        color: var(--color-accent);
        text-decoration: none;
        font-weight: 600;
        transition: color var(--transition-speed) ease;
    }

    .admin-nav a:hover {
        color: var(--color-text);
    }

    @media (max-width: 768px) {
        .main-content {
            flex-direction: column;
        }

        .auction-image-container,
        .auction-details-container {
            flex: 1;
        }

        .auction-details-container {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <?php 
    if ($is_admin) {
        echo '<nav class="admin-nav">
            <ul>
                <li><a href="../admin/dashboard.php">Dashboard</a></li>
                <li><a href="../admin/pending_auctions.php">Pending Auctions</a></li>
                <li><a href="../admin/manage_users.php">Manage Users</a></li>
                <li><a href="../public/logout.php">Logout</a></li>
            </ul>
        </nav>';
    } else {
        include '../includes/nav.php';
    }
    ?>

    <div class="notifications">
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <h1 class="auction-title"><?php echo htmlspecialchars($auction['title']); ?></h1>
        <?php if ($is_admin): ?>
            <a href="../admin/pending_auctions.php" class="back-button">← Back to Pending Auctions</a>
        <?php else: ?>
            <a href="javascript:history.back()" class="back-button">← Back</a>
        <?php endif; ?>

        <?php if (isset($auction)): ?>
            <div class="auction-image-container">
                <?php if (!empty($auction['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="auction-image">
                <?php else: ?>
                    <div class="no-image">No Image Available</div>
                <?php endif; ?>
            </div>
            <div class="auction-details-container">
                <div class="auction-detail-card">
                    <p><strong>Seller:&nbsp;&nbsp;&nbsp;</strong> <?php echo htmlspecialchars($auction['seller_name']); ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>Description:&nbsp;&nbsp;&nbsp;</strong> <?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>Start Price:&nbsp;&nbsp;&nbsp;</strong> $<?php echo number_format($auction['start_price'], 2); ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>Current Highest Bid:</strong> $<?php echo number_format($auction['highest_bid'] ?? $auction['start_price'], 2); ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>Start Time:</strong> <?php echo $auction['start_time']; ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>End Time:</strong> <?php echo $auction['end_time']; ?></p>
                </div>
                <div class="auction-detail-card">
                    <p><strong>Status:</strong> <?php echo $auction_ended ? 'Ended' : 'Active'; ?></p>
                </div>
                <?php if ($is_admin): ?>
                    <div class="auction-detail-card">
                        <p><strong>All Bids:</strong></p>
                        <ul>
                            <?php foreach ($all_bids as $bid): ?>
                                <li><?php echo htmlspecialchars($bid['username']); ?>: $<?php echo number_format($bid['amount'], 2); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in() && !is_admin() && !$is_owner): ?>
                <form method="POST" action="">
                    <input type="hidden" name="toggle_favorite" value="1">
                    <button type="submit" class="favorite-button <?php echo $is_favorited ? 'unfavorite-button' : ''; ?>">
                        <?php echo $is_favorited ? 'Remove from Favorites' : 'Add to Favorites'; ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$auction_ended): ?>
                <?php if (is_logged_in()): ?>
                    <?php if ($is_owner): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this auction?');">
                            <input type="hidden" name="delete_auction" value="1">
                            <button type="submit" class="delete-button">Delete Auction</button>
                        </form>
                    <?php elseif (!is_admin()): ?>
                        <form method="POST" action="">
                            <label for="bid_amount">Your Bid:</label>
                            <input type="number" id="bid_amount" name="bid_amount" min="<?php echo ($auction['highest_bid'] ?? $auction['start_price']) + 0.01; ?>" step="0.01" required>
                            <button type="submit">Place Bid</button>
                        </form>
                        <?php if (isset($user_highest_bid) && $user_highest_bid > 0): ?>
                            <p>Your highest bid: $<?php echo number_format($user_highest_bid, 2); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Please <a href="login.php">log in</a> to place a bid or favorite this auction.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>This auction has ended.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>