<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header("Location: ../public/login.php");
    exit();
}

// Function to get all reports
function get_all_reports() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT r.*, u.username, p.title as item_title, p.is_approved
        FROM item_reports r
        JOIN users u ON r.user_id = u.id
        JOIN products p ON r.item_id = p.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to send a message to a user
function send_message_to_user($report_id, $message) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO admin_messages (report_id, message, sent_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("is", $report_id, $message);
    return $stmt->execute();
}

// Function to update auction status
function update_auction_status($item_id, $status) {
    global $conn;
    $stmt = $conn->prepare("
        UPDATE products
        SET is_approved = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $status, $item_id);
    return $stmt->execute();
}

// Function to delete messages related to a report
function delete_report_messages($report_id) {
    global $conn;
    $stmt = $conn->prepare("
        DELETE FROM admin_messages
        WHERE report_id = ?
    ");
    $stmt->bind_param("i", $report_id);
    return $stmt->execute();
}

// Function to delete a report
function delete_report($report_id) {
    global $conn;
    $stmt = $conn->prepare("
        DELETE FROM item_reports
        WHERE id = ?
    ");
    $stmt->bind_param("i", $report_id);
    return $stmt->execute();
}

$reports = get_all_reports();

// Initialize report counts
$report_counts = [
    'pending' => 0,
    'in_review' => 0,
    'resolved' => 0
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_message'])) {
        $report_id = $_POST['report_id'];
        $message = $_POST['message'];
        if (send_message_to_user($report_id, $message)) {
            $success_message = "Message sent successfully.";
        } else {
            $error_message = "Failed to send message.";
        }
    } elseif (isset($_POST['update_status'])) {
        $item_id = $_POST['item_id'];
        $status = $_POST['status'];
        $report_id = $_POST['report_id'];
        if (update_auction_status($item_id, $status)) {
            $success_message = "Auction status updated successfully.";
            if ($status == 2) { // Assuming '2' is the status for 'Completed'
                unset($error_message); // Remove error message
                if (delete_report_messages($report_id) && delete_report($report_id)) {
                    $report_counts['resolved']++;
                    // Refresh the reports list
                    $reports = get_all_reports();
                }
            }
        } else {
            $error_message = "Failed to update auction status.";
        }
    }
}

// Count reports by status
foreach ($reports as $report) {
    $report_counts[$report['status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Report Management - Luxury Auction</title>
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
            --color-viewed: #4caf50; /* New color for viewed reports */
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

        .report-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-start; /* Align reports to the left */
        }

        .report-button {
            background: linear-gradient(135deg, var(--color-accent) 0%, #e0c179 100%);
            color: var(--color-primary);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-button.viewed {
            background-color: var(--color-viewed); /* Change color for viewed reports */
        }

        .report-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .report-details {
            background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-primary) 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: none;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .report-title {
            font-size: 1.2rem;
            color: var(--color-accent);
        }

        .report-meta {
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }

        .report-content {
            margin-bottom: 1rem;
        }

        .report-actions {
            display: flex;
            gap: 1rem;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--color-accent);
            border-radius: 5px;
            background-color: var(--color-secondary);
            color: var(--color-text);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all var(--transition-speed) ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .status-counter {
            display: flex;
            justify-content: space-around;
            margin-bottom: 2rem;
        }

        .status-item {
            text-align: center;
            background-color: var(--color-secondary);
            padding: 1rem;
            border-radius: 10px;
            transition: all var(--transition-speed) ease;
        }

        .status-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .status-count {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-accent);
        }

        .status-label {
            color: var(--color-text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .success-message, .error-message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success-message {
            background-color: var(--color-success);
        }

        .error-message {
            background-color: var(--color-danger);
        }

        .status-toggle {
            display: flex;
            justify-content: space-between;
            background-color: var(--color-secondary);
            border-radius: 25px;
            overflow: hidden;
        }

        .status-toggle label {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }

        .status-toggle input[type="radio"] {
            display: none;
        }

        .status-toggle input[type="radio"]:checked + label {
            background-color: var(--color-accent);
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>
    <main class="main-content">
        <h1>Admin Report Management</h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="status-counter">
            <div class="status-item">
                <div class="status-count"><?php echo $report_counts['pending']; ?></div>
                <div class="status-label">Pending</div>
            </div>
            <div class="status-item">
                <div class="status-count"><?php echo $report_counts['in_review']; ?></div>
                <div class="status-label">In Review</div>
            </div>
            <div class="status-item">
                <div class="status-count"><?php echo $report_counts['resolved']; ?></div>
                <div class="status-label">Resolved</div>
            </div>
        </div>

        <div class="report-list">
            <?php foreach ($reports as $index => $report): ?>
                <button class="report-button" data-report-id="<?php echo $report['id']; ?>">
                    <?php echo htmlspecialchars($report['username']); ?> #<?php echo $report['id']; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($reports as $index => $report): ?>
            <div id="report-<?php echo $report['id']; ?>" class="report-details">
                <div class="report-header">
                    <span class="report-title">Report #<?php echo htmlspecialchars($report['id']); ?></span>
                    <span class="report-meta">
                        Reported by: <?php echo htmlspecialchars($report['username']); ?> |
                        Date: <?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?>
                    </span>
                </div>
                <div class="report-content">
                    <p><strong>Item:</strong> <?php echo htmlspecialchars($report['item_title']); ?></p>
                    <p><strong>Issue Type:</strong> <?php echo htmlspecialchars($report['issue_type']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?></p>
                </div>
                <div class="report-actions">
                    <form action="" method="POST">
                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                        <div class="form-group">
                            <textarea name="message" placeholder="Enter message for user" class="form-textarea" required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn">Send Message</button>
                    </form>
                    <form action="" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $report['item_id']; ?>">
                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                        <div class="form-group">
                            <div class="status-toggle">
                                <input type="radio" id="status-active-<?php echo $report['id']; ?>" name="status" value="1" <?php echo $report['is_approved'] == 1 ? 'checked' : ''; ?>>
                                <label for="status-active-<?php echo $report['id']; ?>">Active</label>
                                <input type="radio" id="status-completed-<?php echo $report['id']; ?>" name="status" value="2" <?php echo $report['is_approved'] == 2 ? 'checked' : ''; ?>>
                                <label for="status-completed-<?php echo $report['id']; ?>">Completed</label>
                                <input type="radio" id="status-paused-<?php echo $report['id']; ?>" name="status" value="0" <?php echo $report['is_approved'] == 0 ? 'checked' : ''; ?>>
                                <label for="status-paused-<?php echo $report['id']; ?>">Paused</label>
                            </div>
                        </div>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportButtons = document.querySelectorAll('.report-button');
        const reportDetails = document.querySelectorAll('.report-details');

        reportButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reportId = this.getAttribute('data-report-id');
                reportDetails.forEach(detail => {
                    if (detail.id === `report-${reportId}`) {
                        detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
                        this.classList.add('viewed'); // Mark the report as viewed
                    } else {
                        detail.style.display = 'none';
                    }
                });
            });
        });

        // Add animation to buttons and form elements
        const animatedElements = document.querySelectorAll('.btn, .form-input, .form-textarea, .status-toggle label');
        animatedElements.forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'translateY(-2px)';
                el.style.boxShadow = '0 4px 8px rgba(201, 165, 92, 0.3)';
            });
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'translateY(0)';
                el.style.boxShadow = 'none';
            });
        });

        // Hide messages after 3 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(message => {
                message.style.display = 'none';
            });
        }, 3000);
    });
    </script>
</body>
</html>