<?php
// Database connection is assumed to be established in config.php

// User Authentication Functions
function register_user($username, $email, $password) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    return $stmt->execute();
}

function login_user($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }
    return false;
}

// Remove is_logged_in() function as it's already in auth.php

function logout_user() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    session_destroy();
}

// Auction Functions
function get_all_auctions($limit = 10, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_auction_details($auction_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.username as seller_name,
        (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as current_bid,
        CASE 
            WHEN p.start_time > NOW() THEN 'pending'
            WHEN p.end_time > NOW() THEN 'active'
            ELSE 'ended'
        END as status
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Ensure status is set
    if (!isset($result['status'])) {
        $result['status'] = 'unknown';
    }
    
    if (empty($result['image_url'])) {
        $result['image_url'] = '../assets/images/default_auction_image.jpg';
    } else {
        // The image_url is already a relative path, so we don't need to modify it
        // Just ensure it doesn't have a leading slash
        $result['image_url'] = ltrim($result['image_url'], '/');
    }
    
    return $result;
}

function get_highest_bid($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT MAX(amount) as highest_bid FROM bids WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['highest_bid'] ? floatval($row['highest_bid']) : null;
}

function place_bid($auction_id, $user_id, $amount) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO bids (product_id, bidder_id, amount, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $auction_id, $user_id, $amount);
    return $stmt->execute();
}

function create_auction($title, $description, $category, $start_price, $start_time, $end_time, $image_path, $seller_id) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO products (title, description, category, start_price, start_time, end_time, image_path, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsssi", $title, $description, $category, $start_price, $start_time, $end_time, $image_path, $seller_id);
    return $stmt->execute();
}

// User Dashboard Functions
function get_user_active_auctions_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND end_time > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_user_won_auctions_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM (
            SELECT p.id
            FROM products p
            JOIN (
                SELECT product_id, MAX(amount) as max_bid
                FROM bids
                GROUP BY product_id
            ) max_bids ON p.id = max_bids.product_id
            JOIN bids b ON p.id = b.product_id AND b.amount = max_bids.max_bid
            WHERE p.end_time <= NOW() AND b.bidder_id = ?
            GROUP BY p.id
        ) AS won_auctions
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_user_current_bids_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) FROM bids WHERE bidder_id = ? AND product_id IN (SELECT id FROM products WHERE end_time > NOW())");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_user_favorite_auctions_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_user_recent_bids($user_id, $limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT b.amount, b.product_id, p.title, p.end_time, p.image_path
        FROM bids b 
        JOIN products p ON b.product_id = p.id 
        WHERE b.bidder_id = ? 
        ORDER BY b.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_user_recent_auctions($user_id, $limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.end_time, p.image_path, p.start_price,
        (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as current_bid,
        CASE 
            WHEN p.end_time > NOW() THEN 'Active' 
            WHEN p.end_time <= NOW() AND EXISTS (
                SELECT 1 FROM bids b 
                WHERE b.product_id = p.id 
                GROUP BY b.product_id 
                HAVING MAX(b.amount) = (SELECT MAX(amount) FROM bids WHERE product_id = p.id)
                   AND MAX(b.bidder_id) = ?
            ) THEN 'Won'
            WHEN p.end_time <= NOW() THEN 'Ended' 
        END as status 
        FROM products p
        WHERE p.seller_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("iii", $user_id, $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_user_lost_auctions($user_id, $limit = 10, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.end_time, highest_bid.amount as highest_bid
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
        GROUP BY p.id
        ORDER BY p.end_time DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iiii", $user_id, $user_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Admin Functions
function get_all_users() {
    global $conn;
    $query = "SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function ban_user($user_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

function unban_user($user_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// Add any additional functions you need here

function get_user_favorites($user_id, $limit = 12, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.description, p.start_price, p.end_time, p.image_path,
               (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as current_bid
        FROM products p
        JOIN favorites f ON p.id = f.product_id
        WHERE f.user_id = ? AND p.end_time > NOW()
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_total_user_favorites($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM favorites f
        JOIN products p ON f.product_id = p.id
        WHERE f.user_id = ? AND p.end_time > NOW()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'];
}

function cleanup_ended_favorites() {
    global $conn;
    $stmt = $conn->prepare("
        DELETE f FROM favorites f
        JOIN products p ON f.product_id = p.id
        WHERE p.end_time <= NOW()
    ");
    $stmt->execute();
}

function add_to_favorites($user_id, $product_id) {
    global $conn;
    // Check if already in favorites
    $check_stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND product_id = ?");
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Not in favorites, so add it
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $product_id);
        return $stmt->execute();
    }
    return false; // Already in favorites
}

function is_favorite($user_id, $product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function get_user_highest_bid($auction_id, $user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT MAX(amount) as highest_bid
        FROM bids
        WHERE product_id = ? AND bidder_id = ?
    ");
    $stmt->bind_param("ii", $auction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['highest_bid'] ?? 0;
}

function get_auction_winner($auction_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT bidder_id FROM bids WHERE product_id = ? ORDER BY amount DESC, created_at ASC LIMIT 1");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['bidder_id'] : null;
}

function delete_auction($auction_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND start_time > NOW()");
    $stmt->bind_param("i", $auction_id);
    return $stmt->execute();
}

// Add these functions to your existing functions.php file

function get_pending_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE start_time > NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_active_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE start_time <= NOW() AND end_time > NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_ended_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE end_time <= NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_total_users_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_recent_auctions($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.username as seller_name,
        (SELECT MAX(amount) FROM bids WHERE product_id = p.id) as highest_bid,
        CASE 
            WHEN p.start_time > NOW() THEN 'Pending'
            WHEN p.start_time <= NOW() AND p.end_time > NOW() THEN 'Active'
            ELSE 'Ended'
        END as status
        FROM products p
        JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_recent_users($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT id, username, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


function get_total_pending_auctions_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE start_time > NOW()");
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_username($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['username'];
    }
    return "User"; // Default fallback if username is not found
}

function get_pending_auctions() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.username as seller_name 
                            FROM products p 
                            JOIN users u ON p.seller_id = u.id 
                            WHERE p.start_time > NOW() AND p.is_approved = 0
                            ORDER BY p.start_time ASC");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function approve_auction($auction_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE products SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $auction_id);
    return $stmt->execute();
}

// Only add this function if it doesn't already exist
if (!function_exists('toggle_favorite')) {
    function toggle_favorite($user_id, $product_id) {
        global $conn;
        
        // Check if the product is already favorited
        $check_query = "SELECT * FROM favorites WHERE user_id = ? AND product_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Product is favorited, so unfavorite it
            $delete_query = "DELETE FROM favorites WHERE user_id = ? AND product_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("ii", $user_id, $product_id);
            $delete_stmt->execute();
            return false; // Product is now unfavorited
        } else {
            // Product is not favorited, so favorite it
            $insert_query = "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ii", $user_id, $product_id);
            $insert_stmt->execute();
            return true; // Product is now favorited
        }
    }
}

// Don't add this function, as it already exists
// function is_favorite($user_id, $auction_id) { ... }

















