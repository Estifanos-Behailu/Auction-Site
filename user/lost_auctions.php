<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total count of lost auctions for pagination
function get_total_lost_auctions($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        JOIN (
            SELECT product_id, MAX(amount) as amount
            FROM bids
            GROUP BY product_id
        ) highest_bid ON p.id = highest_bid.product_id
        JOIN bids user_bid ON p.id = user_bid.product_id AND user_bid.bidder_id = ?
        WHERE p.end_time <= NOW()
          AND p.seller_id != ?
          AND highest_bid.amount > user_bid.amount
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'];
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$lost_auctions = get_user_lost_auctions($user_id, $per_page, $offset);

$total_lost_auctions = get_total_lost_auctions($user_id);
$total_pages = ceil($total_lost_auctions / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Auctions - Vintage Auction</title>
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

        .search-container {
            margin-bottom: 20px;
            text-align: center;
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
        }

        .product-item h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin-bottom: 10px;
        }

        .product-item p {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-bottom: 15px;
        }

        .view-auction-btn {
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
        }

        .view-auction-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link, .current-page {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }

        .page-link {
            background-color: var(--color-secondary);
            color: var(--color-text);
        }

        .current-page {
            background-color: var(--color-accent);
            color: var(--color-primary);
        }

        .page-link:hover {
            background-color: var(--color-accent);
            color: var(--color-primary);
        }

        .error {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--color-danger);
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
        <h1 class="animated">Lost Auctions</h1>

        <?php if (isset($error_message)): ?>
            <p class="error animated"><?php echo $error_message; ?></p>
        <?php elseif (empty($lost_auctions)): ?>
            <p class="animated">You haven't lost any auctions yet.</p>
        <?php else: ?>
            <div class="search-container animated">
                <input type="text" id="searchInput" placeholder="Search by title" aria-label="Search lost auctions">
            </div>
            <div class="product-list" id="auctionsContainer">
                <?php foreach ($lost_auctions as $auction): ?>
                    <div class="product-item animated">
                        <?php if (!empty($auction['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                        <p>Highest Bid: $<?php echo number_format($auction['highest_bid'], 2); ?></p>
                        <p>Auction End Time: <?php echo date('Y-m-d H:i:s', strtotime($auction['end_time'])); ?></p>
                        <a href="../public/auction.php?id=<?php echo urlencode($auction['id']); ?>" class="view-auction-btn">View Auction</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination animated">
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const auctionsContainer = document.getElementById('auctionsContainer');
        const items = auctionsContainer.getElementsByClassName('product-item');

        const animateElements = document.querySelectorAll('.animated');
        animateElements.forEach((el, index) => {
            el.style.animationDelay = `${0.1 * (index + 1)}s`;
        });

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < items.length; i++) {
                const title = items[i].querySelector('h3').textContent.toLowerCase();

                if (title.includes(searchTerm)) {
                    items[i].style.display = '';
                } else {
                    items[i].style.display = 'none';
                }
            }
        });
    });
    </script>
</body>
</html>