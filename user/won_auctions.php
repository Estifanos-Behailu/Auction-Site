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
    $query = "SELECT p.*, b.amount as winning_bid
              FROM products p
              JOIN bids b ON p.id = b.product_id
              WHERE b.bidder_id = ? AND b.amount = (
                  SELECT MAX(amount)
                  FROM bids
                  WHERE product_id = p.id
              ) AND p.end_time < NOW()
              ORDER BY p.end_time DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $won_auctions = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in won_auctions.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching won auctions. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Won Auctions - Vintage Auction</title>
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

        #searchInput:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--color-accent);
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
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1 class="animated">Won Auctions</h1>

        <?php if (isset($error_message)): ?>
            <p class="error animated"><?php echo $error_message; ?></p>
        <?php elseif (empty($won_auctions)): ?>
            <p class="animated">You haven't won any auctions yet.</p>
        <?php else: ?>
            <div class="search-container animated">
                <input type="text" id="searchInput" placeholder="Search by title" aria-label="Search won auctions">
            </div>
            <div class="product-list" id="auctionsContainer">
                <?php foreach ($won_auctions as $auction): ?>
                    <div class="product-item animated">
                        <?php if (!empty($auction['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                        <p>Winning Bid: $<?php echo number_format($auction['winning_bid'], 2); ?></p>
                        <p>Auction End Time: <?php echo htmlspecialchars($auction['end_time']); ?></p>
                        <a href="../public/auction.php?id=<?php echo urlencode($auction['id']); ?>" class="view-auction-btn">View Auction</a>
                    </div>
                <?php endforeach; ?>
            </div>
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