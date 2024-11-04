<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$errors = [];
$success_message = '';

// Handle accepting admin messages
if (isset($_POST['accept_message'])) {
    $message_id = $_POST['message_id'];
    $stmt = $conn->prepare("UPDATE admin_messages SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $success_message = "Admin message accepted successfully!";
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $success_message]);
        exit();
    }
}

// Function to get admin messages
function getAdminMessages($user_id, $conn) {
    $stmt = $conn->prepare("
        SELECT am.*, ir.item_id, ir.issue_type, ir.description as report_description 
        FROM admin_messages am 
        JOIN item_reports ir ON am.report_id = ir.id 
        WHERE ir.user_id = ? 
        ORDER BY am.sent_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$admin_messages = getAdminMessages($_SESSION['user_id'], $conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['accept_message'])) {
    $item_id = trim($_POST['item_id']);
    $issue_type = $_POST['issue_type'];
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (empty($item_id)) {
        $errors[] = "Item ID is required.";
    }
    if (empty($issue_type)) {
        $errors[] = "Issue type is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }

    // Check if the item exists in the products table
    if (!empty($item_id)) {
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            $errors[] = "Invalid Item ID. The item does not exist.";
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO item_reports (item_id, user_id, issue_type, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $item_id, $user_id, $issue_type, $description);
            
            if ($stmt->execute()) {
                $success_message = "Report submitted successfully!";
            } else {
                throw new Exception("Error submitting report.");
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Define issue types
$issue_types = [
    'incorrect-description' => 'Incorrect or misleading item description',
    'incorrect-photos' => 'Incorrect item photos',
    'not-as-described' => 'Item not as described',
    'not-received' => 'Item not received',
    'damaged' => 'Item damaged upon arrival',
    'shipping-refusal' => 'Seller refuses to ship or deliver item',
    'other' => 'Other'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue - Vintage Auction</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
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

        .toggle-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .toggle-btn {
            padding: 12px 24px;
            background: var(--color-secondary);
            border: 2px solid var(--color-accent);
            color: var(--color-text);
            border-radius: 25px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }

        .toggle-btn.active {
            background: var(--color-accent);
            color: var(--color-primary);
        }

        .content-section {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .content-section.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: var(--color-primary);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-accent);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-accent);
            border-radius: 25px;
            background-color: var(--color-secondary);
            color: var(--color-text);
            font-family: 'Roboto', sans-serif;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            border-radius: 15px;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--color-accent);
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(to right, var(--color-accent), #e0c179);
            color: var(--color-primary);
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(201, 165, 92, 0.3);
        }

        .error-message {
            color: var(--color-danger);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: rgba(244, 67, 54, 0.1);
            text-align: center;
        }

        .success-message {
            color: var(--color-success);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: rgba(76, 175, 80, 0.1);
            text-align: center;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease forwards;
        }

        .message-card {
            background: var(--color-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--color-accent);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: var(--color-accent);
        }

        .message-content {
            margin-bottom: 15px;
        }

        .report-details {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1 class="animated">Issue Management</h1>

        <div class="toggle-container animated">
            <button class="toggle-btn active" data-target="report-form">Report Issue</button>
            <button class="toggle-btn" data-target="admin-messages">Admin Messages</button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error-message animated"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message animated" id="success-message">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <div id="report-form" class="content-section active animated">
            <form action="" method="POST" class="form-container animated">
                <div class="form-group">
                    <label for="item_id">Item ID:</label>
                    <input type="text" id="item_id" name="item_id" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="issue_type">Issue Type:</label>
                    <select id="issue_type" name="issue_type" required class="form-select">
                        <option value="">Select an issue type</option>
                        <?php foreach ($issue_types as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required class="form-textarea" placeholder="Please provide details about the issue..."></textarea>
                </div>
                <button type="submit" class="btn-primary animated">Submit Report</button>
            </form>
        </div>

        <div id="admin-messages" class="content-section animated">
            <?php if (empty($admin_messages)): ?>
                <div class="message-card">
                    <p>No admin messages available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($admin_messages as $message): ?>
                    <div class="message-card animated" data-message-id="<?php echo $message['id']; ?>">
                        <div class="message-header">
                            <span>Date: <?php echo date('F j, Y, g:i a', strtotime($message['sent_at'])); ?></span>
                            <span>Status: <span class="message-status"><?php echo ucfirst($message['status']); ?></span></span>
                        </div>
                        <div class="report-details">
                            <p><strong>Item ID:</strong> <?php echo htmlspecialchars($message['item_id']); ?></p>
                            <p><strong>Issue Type:</strong> <?php echo htmlspecialchars($message['issue_type']); ?></p>
                            <p><strong>Your Report:</strong> <?php echo htmlspecialchars($message['report_description']); ?></p>
                        </div>
                        <div class="message-content">
                            <p><strong>Admin Response:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        </div>
                        <?php if ($message['status'] === 'pending'): ?>
                            <form class="accept-message-form" method="POST">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <button type="button" name="accept_message" class="btn-primary accept-message-btn">Accept Message</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const submitButton = form.querySelector('button[type="submit"]');
        const formInputs = document.querySelectorAll('.form-input, .form-textarea, .form-select');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            // Simulate form submission delay
            setTimeout(() => {
                form.submit();
            }, 1000);
        });

        // Add toggle functionality
        const toggleBtns = document.querySelectorAll('.toggle-btn');
        const contentSections = document.querySelectorAll('.content-section');

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const target = this.dataset.target;
                
                // Update button states
                toggleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update content sections
                contentSections.forEach(section => {
                    if (section.id === target) {
                        section.classList.add('active');
                    } else {
                        section.classList.remove('active');
                    }
                });
            });
        });

        // Add animation to form elements
        const animatedElements = document.querySelectorAll('.animated');
        animatedElements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });

        // Add focus and blur effects to form inputs
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 0 10px rgba(201, 165, 92, 0.5)';
            });

            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });

        // Add hover effect to submit button
        submitButton.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
            this.style.boxShadow = '0 6px 12px rgba(201, 165, 92, 0.4)';
        });

        submitButton.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 4px 8px rgba(201, 165, 92, 0.3)';
        });

        // Handle accept message button click
        const acceptMessageBtns = document.querySelectorAll('.accept-message-btn');
        acceptMessageBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('.accept-message-form');
                const messageId = form.querySelector('input[name="message_id"]').value;
                const messageCard = this.closest('.message-card');

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `accept_message=true&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the message status
                        messageCard.querySelector('.message-status').textContent = 'Accepted';
                        form.remove(); // Remove the form since the message is accepted

                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'success-message animated';
                        successMessage.textContent = "Message accepted successfully!"; // Updated message text
                        document.querySelector('.main-content').prepend(successMessage);

                        setTimeout(() => {
                            successMessage.style.opacity = '0';
                            setTimeout(() => successMessage.remove(), 500);
                        }, 3000);
                    }
                });
            });
        });

        // Automatically hide success messages after 3 seconds
        const successMessage = document.getElementById('success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }, 3000);
        }
    });
    </script>
</body>
</html>