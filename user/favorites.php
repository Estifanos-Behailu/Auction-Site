<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Clean up ended favorites
cleanup_ended_favorites();

// Handle favorite removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_favorite'])) {
    $product_id = intval($_POST['remove_favorite']);
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    // Redirect to the same page to refresh the list
    header("Location: favorites.php");
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$favorites = get_user_favorites($user_id, $per_page, $offset);
$total_favorites = get_total_user_favorites($user_id);
$total_pages = ceil($total_favorites / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Vintage Auction</title>
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

        .info-message {
            text-align: center;
            color: var(--color-text-muted);
            font-size: 1.1rem;
            margin: 2rem 0;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .grid-item {
            background-color: var(--color-primary);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
            padding: 20px;
            animation: fadeIn 0.5s ease forwards;
        }

        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin: 15px 0 10px;
        }

        .item-description {
            color: var(--color-text-muted);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .item-price, .item-end-time {
            color: var(--color-text);
            font-size: 0.9rem;
            margin: 5px 0;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
        }

        .btn-danger {
            background: linear-gradient(to right, var(--color-danger), #ff7961);
            color: var(--color-text);
            width: 100%;
            height: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 2rem;
        }

        .page-link, .current-page {
            padding: 8px 16px;
            border-radius: 25px;
            background-color: var(--color-primary);
            color: var(--color-text);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }

        .current-page {
            background-color: var(--color-accent);
            color: var(--color-primary);
        }

        .page-link:hover {
            background-color: var(--color-accent);
            color: var(--color-primary);
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
        <h1>My Favorites</h1>
        <?php if (empty($favorites)): ?>
            <p class="info-message">You don't have any active favorites at the moment.</p>
        <?php else: ?>
            <div class="grid-container">
                <?php foreach ($favorites as $auction): ?>
                    <div class="grid-item">
                        <?php if (!empty($auction['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="item-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        <h2 class="item-title"><?php echo htmlspecialchars($auction['title']); ?></h2>
                        <p class="item-description"><?php echo htmlspecialchars(substr($auction['description'], 0, 100)) . '...'; ?></p>
                        <p class="item-price">Current Bid: $<?php echo number_format($auction['current_bid'] ?? $auction['start_price'], 2); ?></p>
                        <p class="item-end-time">Ends: <?php echo date('Y-m-d H:i:s', strtotime($auction['end_time'])); ?></p>
                        <div class="item-actions">
                            <a href="../public/auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-primary">View</a>
                            <form method="POST" style="display: inline; flex: 1;">
                                <input type="hidden" name="remove_favorite" value="<?php echo $auction['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this from your favorites?');">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>