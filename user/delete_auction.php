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
$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($auction_id <= 0) {
    $_SESSION['error_message'] = "Invalid auction ID.";
    header("Location: dashboard.php");
    exit();
}

try {
    // Check if the auction belongs to the user and hasn't ended yet
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ? AND end_time > NOW()");
    $stmt->bind_param("ii", $auction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("You don't have permission to delete this auction or it has already ended.");
    }

    // Delete associated bids
    $stmt = $conn->prepare("DELETE FROM bids WHERE product_id = ?");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();

    // Delete the auction
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();

    $_SESSION['success_message'] = "Auction deleted successfully.";
} catch (Exception $e) {
    error_log("Error in delete_auction.php: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: dashboard.php");
exit();
