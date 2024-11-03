<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

// Define categories
$categories = [
    'Electronics',
    'Fashion',
    'Home & Garden',
    'Sports',
    'Toys & Hobbies',
    'Vehicles',
    'Collectibles & Art',
    'Books',
    'Music',
    'Other'
];

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $start_price = floatval($_POST['start_price']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $seller_id = $_SESSION['user_id'];

    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if (empty($category)) {
        $errors[] = "Category is required.";
    }
    if ($start_price <= 0) {
        $errors[] = "Starting price must be greater than zero.";
    }

    $start_time_obj = new DateTime($start_time);
    $end_time_obj = new DateTime($end_time);
    $now = new DateTime();

    if ($start_time_obj <= $now) {
        $errors[] = "Start time must be in the future.";
    }
    if ($end_time_obj <= $start_time_obj) {
        $errors[] = "End time must be after start time.";
    }
    if ($end_time_obj > $start_time_obj->modify('+30 days')) {
        $errors[] = "Auction duration cannot exceed 30 days.";
    }

    // Process image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array(strtolower($filetype), $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        $errors[] = "Image is required.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Upload image
        $image_path = '../uploads/' . uniqid() . '.' . $filetype;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);

        // Insert auction into database
        $stmt = $conn->prepare("INSERT INTO products (title, description, category, start_price, start_time, end_time, image_path, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsssi", $title, $description, $category, $start_price, $start_time, $end_time, $image_path, $seller_id);
        
        if ($stmt->execute()) {
            $success_message = "Auction posted successfully!";
        } else {
            $errors[] = "Error posting auction. Please try again.";
        }
    }
}

// Set minimum date-time for start_time (current time)
$min_start_time = date('Y-m-d\TH:i', strtotime('+1 hour'));

// Set minimum and maximum date-time for end_time
$min_end_time = date('Y-m-d\TH:i', strtotime('+2 hours'));
$max_end_time = date('Y-m-d\TH:i', strtotime('+30 days'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Auction - Vintage Auction</title>
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

        .form-input,
        .form-textarea{
            width: 97%;
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

        .image-upload-container {
            border: 2px dashed var(--color-accent);
            border-radius: 15px;
            padding: 40px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            text-align: center;
        }

        .image-upload-container.drag-over {
            background-color: rgba(201, 165, 92, 0.1);
            transform: scale(1.02);
        }

        .image-preview img {
            max-width: 300px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="main-content">
        <h1>Post New Auction</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required class="form-input">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required class="form-select">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_price">Starting Price:</label>
                <input type="number" id="start_price" name="start_price" min="0" step="0.01" required class="form-input">
            </div>
            <div class="form-group">
                <label for="start_time">Start Time:</label>
                <input type="datetime-local" id="start_time" name="start_time" min="<?php echo $min_start_time; ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="end_time">End Time:</label>
                <input type="datetime-local" id="end_time" name="end_time" min="<?php echo $min_end_time; ?>" max="<?php echo $max_end_time; ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="image">Image:</label>
                <div class="image-upload-container">
                    <div class="image-preview">
                        <img id="preview-image" src="#" alt="Preview Image" style="display: none;">
                    </div>
                    <input type="file" id="image" name="image" accept="image/*" required class="form-input" style="display: none;">
                    <div class="image-upload-text">Drag and drop an image here or click to upload</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Post Auction</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const imageInput = document.getElementById('image');
        const previewImage = document.getElementById('preview-image');
        const imageUploadContainer = document.querySelector('.image-upload-container');
        const imageUploadText = document.querySelector('.image-upload-text');

        startTimeInput.addEventListener('change', function() {
            const startTime = new Date(this.value);
            const minEndTime = new Date(startTime.getTime() + (60 * 60 * 1000)); // Start time + 1 hour
            endTimeInput.min = minEndTime.toISOString().slice(0, 16);

            if (new Date(endTimeInput.value) < minEndTime) {
                endTimeInput.value = minEndTime.toISOString().slice(0, 16);
            }
        });

        imageUploadContainer.addEventListener('click', function() {
            imageInput.click();
        });

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    imageUploadText.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        imageUploadContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        imageUploadContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
        imageUploadContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            imageInput.files = e.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                imageUploadText.style.display = 'none';
            }
            reader.readAsDataURL(file);
        });
    });
    </script>
</body>
</html>