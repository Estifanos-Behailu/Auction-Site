<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

function end_auctions() {
    global $conn;
    
    // Get all auctions that have ended but not yet processed
    $stmt = $conn->prepare("SELECT id FROM products WHERE end_time <= NOW() AND winner_id IS NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $auction_id = $row['id'];
        $winner = get_auction_winner($auction_id);
        
        if ($winner) {
            // Update the product with the winner
            $update_stmt = $conn->prepare("UPDATE products SET winner_id = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $winner['bidder_id'], $auction_id);
            $update_stmt->execute();
            
            // Notify the winner and seller
            notify_auction_end($auction_id, $winner['bidder_id'], $winner['amount']);
        }
    }
}

function get_auction_winner($auction_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT bidder_id, amount FROM bids WHERE product_id = ? ORDER BY amount DESC, created_at ASC LIMIT 1");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Run the end_auctions function
end_auctions();
