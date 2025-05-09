<?php
session_start();
require_once __DIR__ . '/config2.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = [];

    if (empty($_POST['username'])) {
        $errors[] = "Username is required";
    }

    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Insert into users table
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, created_at)
                VALUES (?, ?, ?, 'enthusiast', NOW())
            ");
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                $_POST['username'],
                $_POST['email'],
                $hashedPassword
            ]);
            $userId = $pdo->lastInsertId();

            // Insert into enthusiasts table
            $stmt = $pdo->prepare("
                INSERT INTO enthusiasts (user_id)
                VALUES (?)
            ");
            $stmt->execute([$userId]);
            $enthusiastId = $pdo->lastInsertId();

            // Insert into enthusiastinfo table
            $stmt = $pdo->prepare("
                INSERT INTO enthusiastinfo (enthusiast_id, fullname, shipping_address, phone_number)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $enthusiastId,
                $_POST['fullname'] ?? null,
                $_POST['shipping_address'] ?? null,
                $_POST['phone_number'] ?? null
            ]);

            // Insert into artpreferences table
            $stmt = $pdo->prepare("
                INSERT INTO artpreferences (
                    enthusiast_id, mediums, styles, budget_min, budget_max, 
                    artist1, artist2, artist3
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $enthusiastId,
                $_POST['mediums'] ?? null,
                $_POST['styles'] ?? null,
                $_POST['budget_min'] ?? 0,
                $_POST['budget_max'] ?? 0,
                $_POST['artist1'] ?? null,
                $_POST['artist2'] ?? null,
                $_POST['artist3'] ?? null
            ]);

            $pdo->commit();
            $_SESSION['message'] = "Enthusiast added successfully";
            header("Location: enthusiasts.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error adding enthusiast: " . $e->getMessage();
            header("Location: enthusiasts.php");
            exit();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: enthusiasts.php");
        exit();
    }
}

// Display form with styled buttons
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Enthusiast</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Nunito', sans-serif;
        }
        
        /* BUTTON STYLES */
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: all 0.5s ease;
            z-index: 0;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(120, 202, 197, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(77, 184, 178, 0.4);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 207, 155, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(150, 131, 63, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <h1>Add New Enthusiast</h1>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username*</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email*</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password*</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname">
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Add Enthusiast</button>
                <button type="reset" class="btn btn-secondary">Reset Form</button>
            </div>
        </form>
    </div>
</body>
</html>