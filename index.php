<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';


function get_active_auctions($limit = 10) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.username as seller_name,
        (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as highest_bid
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.start_time <= NOW() AND p.end_time > NOW()
        ORDER BY p.end_time ASC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$active_auctions = get_active_auctions();

// If user is already logged in, redirect to the listing page
if (is_logged_in()) {
    header("Location: user/listing.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Noble Bids - Premium Auction House</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, var(--color-background) 0%, var(--color-primary) 100%);
            color: var(--color-text);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .welcome-container {
            max-width: 1200px;
            padding: 4rem;
            background: rgba(44, 44, 44, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            text-align: center;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201, 165, 92, 0.1);
            margin: 20px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
            padding-bottom: 20px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--color-accent), transparent);
        }

        .tagline {
            font-size: 1.4rem;
            color: var(--color-text-muted);
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 4rem;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all var(--transition-speed) ease;
            min-width: 160px;
        }

        .button.primary {
            background: linear-gradient(135deg, var(--color-accent), #e0c179);
            color: var(--color-primary);
            border: none;
        }

        .button.secondary {
            background: transparent;
            color: var(--color-accent);
            border: 2px solid var(--color-accent);
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(201, 165, 92, 0.2);
        }

        .features {
            margin-top: 4rem;
            padding-top: 4rem;
            border-top: 1px solid rgba(201, 165, 92, 0.2);
        }

        .features h2 {
            font-family: 'Playfair Display', serif;
            color: var(--color-accent);
            font-size: 2rem;
            margin-bottom: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 2rem;
        }

        .feature-item {
            padding: 2rem;
            background: rgba(58, 58, 58, 0.3);
            border-radius: 15px;
            transition: all var(--transition-speed) ease;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            background: rgba(58, 58, 58, 0.5);
            box-shadow: 0 8px 12px #c9a55c;
        }

        .feature-item h3 {
            color: var(--color-accent);
            font-family: 'Playfair Display', serif;
            margin-bottom: 1rem;
        }

        .feature-item p {
            color: var(--color-text-muted);
        }

        @media (max-width: 768px) {
            .welcome-container {
                padding: 2rem;
            }

            h1 {
                font-size: 2.5rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1>Noble Bids</h1>
        <p class="tagline">Where Luxury Meets Legacy - Experience Premium Auctions</p>
        
        <div class="cta-buttons">
            <a href="public/login.php" class="button primary">Sign In</a>
            <a href="public/register.php" class="button secondary">Join Now</a>
        </div>
        
        <div class="features">
            <h2>The Noble Experience</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <h3>Curated Excellence</h3>
                    <p>Discover handpicked premium items from trusted sellers worldwide</p>
                </div>
                <div class="feature-item">
                    <h3>Secure Bidding</h3>
                    <p>State-of-the-art security ensuring safe and transparent transactions</p>
                </div>
                <div class="feature-item">
                    <h3>Exclusive Access</h3>
                    <p>Preview upcoming auctions and receive personalized recommendations</p>
                </div>
                <div class="feature-item">
                    <h3>Concierge Service</h3>
                    <p>Dedicated support team for a seamless auction experience</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>