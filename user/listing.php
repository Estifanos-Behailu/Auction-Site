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

// Search and filter logic
$search = isset($_GET['search']) ? validate_input($_GET['search']) : '';
$category = isset($_GET['category']) ? validate_input($_GET['category']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;
$show_my_listings = isset($_GET['show_my_listings']) ? true : false;

if (!validate_numeric($min_price, 0) || !validate_numeric($max_price, 0)) {
    $error_message = "Invalid price range.";
}

try {
    $query = "SELECT p.*, u.username as seller_name, COALESCE(MAX(b.amount), p.start_price) as highest_bid
              FROM products p 
              JOIN users u ON p.seller_id = u.id 
              LEFT JOIN bids b ON p.id = b.product_id
              WHERE p.is_approved = 1";

    if (!$show_my_listings) {
        $query .= " AND p.end_time > NOW() AND p.seller_id != ?";
    } else {
        $query .= " AND p.seller_id = ?";
    }

    $params = array($user_id);
    $types = "i";

    if (!empty($search)) {
        $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }

    if (!empty($category)) {
        $query .= " AND p.category = ?";
        $params[] = $category;
        $types .= "s";
    }

    $query .= " AND p.start_price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd";

    $query .= " GROUP BY p.id ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch categories for the filter
    $categories_query = "SELECT DISTINCT category FROM products WHERE is_approved = 1";
    $categories_result = $conn->query($categories_query);
    $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in listing.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching the listings. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Listings - Vintage Auction</title>
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

        .search-filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--color-primary);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-input,
        .form-select {
            width: 90%;
            padding: 12px;
            border: 1px solid var(--color-accent);
            background-color: var(--color-secondary);
            color: var(--color-text);
            border-radius: 25px;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--color-accent);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--color-text);
            cursor: pointer;
        }

        .btn {
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
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
        }

        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .item-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--color-accent);
            margin-bottom: 10px;
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
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
            border-radius: 10px;
        }

        .item-seller,
        .item-category,
        .item-price,
        .item-end-time {
            color: var(--color-text-muted);
            font-size: 0.9rem;
            margin: 8px 0;
        }

        .error-message,
        .info-message {
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
        }

        @media (max-width: 768px) {
            .search-filter-form {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin: 10px;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1>Auction Listings</h1>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php else: ?>
            <form method="GET" class="search-filter-form">
                <input type="text" name="search" placeholder="Search auctions" value="<?php echo htmlspecialchars($search); ?>" class="form-input">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="min_price" placeholder="Min Price" value="<?php echo $min_price > 0 ? $min_price : ''; ?>" class="form-input">
                <input type="number" name="max_price" placeholder="Max Price" value="<?php echo $max_price < PHP_FLOAT_MAX ? $max_price : ''; ?>" class="form-input">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_my_listings" <?php echo $show_my_listings ? 'checked' : ''; ?>>
                    Show My Listings
                </label>
                <button type="submit" class="btn btn-primary">Search & Filter</button>
            </form>

            <div class="grid-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="grid-item">
                            <h2 class="item-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                            <?php if (!empty($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="item-image">
                            <?php else: ?>
                                <div class="no-image">No Image Available</div>
                            <?php endif; ?>
                            <p class="item-seller">Seller: <?php echo htmlspecialchars($row['seller_name']); ?></p>
                            <p class="item-category">Category: <?php echo htmlspecialchars($row['category']); ?></p>
                            <p class="item-price">Start Price: $<?php echo number_format($row['start_price'], 2); ?></p>
                            <p class="item-price">Current Highest Bid: $<?php echo number_format($row['highest_bid'], 2); ?></p>
                            <p class="item-end-time">End Time: <?php echo htmlspecialchars($row['end_time']); ?></p>
                            <a href="../public/auction.php?id=<?php echo urlencode($row['id']); ?>" class="btn btn-primary">View Auction</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="info-message">No auctions found matching your criteria.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>