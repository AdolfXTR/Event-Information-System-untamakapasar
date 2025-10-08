<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    if (is_student()) {
        redirect('../student/dashboard.php');
    } elseif (is_sao_staff()) {
        redirect('../sao-staff/dashboard.php');
    } elseif (is_admin()) {
        redirect('../admin/dashboard.php');
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $student_id = sanitize_input($_POST['student_id']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
    }
    
    // Check if student ID already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Student ID already registered";
        }
        $stmt->close();
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        $hashed_password = hash_password($password);
        $verification_token = generate_token();
        $user_type = 'student';
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, student_id, email, password, user_type, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $first_name, $last_name, $student_id, $email, $hashed_password, $user_type, $verification_token);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Log the registration
            log_activity($conn, $user_id, 'User Registration', 'New student account created');
            
            $success = true;
            set_message('success', 'Registration successful! You can now login.');
            
            // Redirect to login after 2 seconds
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Event Information System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .slideshow-container {
            position: fixed;
            width: 100%;
            height: 100vh;
            z-index: -1;
            top: 0;
            left: 0;
        }

        .slide {
            display: none;
            width: 100%;
            height: 100vh;
            object-fit: cover;
            animation: fadeIn 1.5s;
        }

        .slide.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0.4; }
            to { opacity: 1; }
        }

        .overlay {
            position: fixed;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
            top: 0;
            left: 0;
        }

        .header {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .school-info h1 {
            font-size: 1.8rem;
            color: #1a365d;
            margin-bottom: 5px;
        }

        .school-info p {
            color: #666;
            font-size: 0.95rem;
        }

        .main-content {
            position: relative;
            z-index: 5;
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            animation: slideInUp 0.8s;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .form-card h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }

        .form-card > p {
            color: #666;
            margin-bottom: 30px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-danger ul {
            margin: 0;
            padding-left: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.3rem;
            user-select: none;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.95rem;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            .school-info h1 {
                font-size: 1.4rem;
            }

            .form-card {
                padding: 30px 20px;
            }

            .form-card h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Slideshow Background -->
    <div class="slideshow-container">
        <img src="../assets/images/slide1.jpg" class="slide active" alt="Campus">
        <img src="../assets/images/slide2.jpg" class="slide" alt="Events">
        <img src="../assets/images/slide3.jpg" class="slide" alt="Students">
        <img src="../assets/images/slide4.jpg" class="slide" alt="Activities">
        <img src="../assets/images/slide5.jpg" class="slide" alt="School Life">
    </div>
    <div class="overlay"></div>

    <!-- Header with Logo -->
    <header class="header">
        <div class="header-content">
            <img src="../assets/images/logo.png" alt="School Logo" class="logo">
            <div class="school-info">
                <h1>Event Information System</h1>
                <p>Student Affairs Office - Stay Connected with Campus Events</p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-card">
            <a href="../index.html" class="back-link">← Back to Home</a>
            
            <h2>Create Your Account</h2>
            <p>Register to access SAO events</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> Registration completed. Redirecting to login...
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>"
                           placeholder="Enter first name">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>"
                           placeholder="Enter last name">
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" required 
                           value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>"
                           placeholder="Enter student ID">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required 
                               minlength="6" placeholder="Create password">
                        <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                    </div>
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm password">
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">👁️</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Register</button>
                
                <div class="form-footer">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Slideshow functionality
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slide');

        function showSlides() {
            slides.forEach(slide => slide.classList.remove('active'));
            slideIndex++;
            if (slideIndex > slides.length) {
                slideIndex = 1;
            }
            slides[slideIndex - 1].classList.add('active');
            setTimeout(showSlides, 4000);
        }

        showSlides();

        // Password toggle
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>