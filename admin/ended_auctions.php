<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

try {
    $query = "SELECT p.*, u.username as seller_name, 
                     (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as final_price
              FROM products p
              JOIN users u ON p.seller_id = u.id
              WHERE p.end_time <= NOW()
              ORDER BY p.end_time DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $ended_auctions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in ended_auctions.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching ended auctions. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ended Auctions - Luxury Auction Admin</title>
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

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: var(--color-primary);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow:  0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-content {
            padding: 20px;
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin-bottom: 10px;
        }

        .card-info {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            justify-content: center;
        }

        .card-actions .viewbtn {
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
            width: 100%;
        }

        .card-actions .viewbtn:hover {
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
    <?php include 'admin_nav.php'; ?>
    <div class="main-content">
        <h1 class="animated">Ended Auctions</h1>
        
        <?php if (isset($error_message)): ?>
            <p class="error animated"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <div class="search-container animated">
            <input type="text" id="searchInput" placeholder="Search by title or seller" aria-label="Search ended auctions">
        </div>
        
        <?php if (empty($ended_auctions)): ?>
            <p class="animated">No ended auctions at this time.</p>
        <?php else: ?>
            <div class="card-container" id="auctionsContainer">
                <?php foreach ($ended_auctions as $auction): ?>
                    <div class="card animated">
                        <?php if (!empty($auction['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" class="card-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        <div class="card-content">
                            <h2 class="card-title"><?php echo htmlspecialchars($auction['title']); ?></h2>
                            <p class="card-info">Seller: <?php echo htmlspecialchars($auction['seller_name']); ?></p>
                            <p class="card-info">Start Time: <?php echo $auction['start_time']; ?></p>
                            <p class="card-info">End Time: <?php echo $auction['end_time']; ?></p>
                            <p class="card-info">Final Price: $<?php echo number_format($auction['final_price'], 2); ?></p>
                            <div class="card-actions">
                                <a href="../public/auction.php?id=<?php echo $auction['id']; ?>" class="viewbtn">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const auctionsContainer = document.getElementById('auctionsContainer');
        const cards = auctionsContainer.getElementsByClassName('card');

        const animateElements = document.querySelectorAll('.animated');
        animateElements.forEach((el, index) => {
            el.style.animationDelay = `${0.1 * (index + 1)}s`;
        });

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < cards.length; i++) {
                const title = cards[i].querySelector('.card-title').textContent.toLowerCase();
                const seller = cards[i].querySelector('.card-info').textContent.toLowerCase();

                if (title.includes(searchTerm) || seller.includes(searchTerm)) {
                    cards[i].style.display = '';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        });
    });
    </script>
</body>
</html>