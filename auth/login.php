<?php
// Start session first!
session_start();

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, user_type, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                $error = "Your account has been " . $user['status'] . ". Please contact admin.";
            } elseif (verify_password($password, $user['password'])) {
                // Login successful - Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Log the login activity
                log_activity($conn, $user['user_id'], 'User Login', 'User logged in successfully');
                
                // Redirect based on user type
                if ($user['user_type'] === 'student') {
                    header("Location: ../student/dashboard.php");
                    exit();
                } elseif ($user['user_type'] === 'sao_staff') {
                    header("Location: ../sao-staff/dashboard.php");
                    exit();
                } elseif ($user['user_type'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
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
    <title>Login - Event Information System</title>
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
            max-width: 450px;
            animation: slideInUp 0.8s;
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

        .demo-accounts {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .demo-accounts h4 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1rem;
        }

        .demo-accounts p {
            margin: 5px 0;
            color: #666;
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
            
            <h2>Welcome Back!</h2>
            <p>Login to access your account</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php display_message(); ?>
            
            <form method="POST" action="">
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
                               placeholder="Enter your password">
                        <span class="password-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Login</button>
                
                <div class="form-footer">
                    <a href="forgot-password.php">Forgot Password?</a>
                    <br><br>
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
            
            <div class="demo-accounts">
                <h4>🔑 Demo Accounts</h4>
                <p><strong>Admin:</strong> admin@sao.edu | password</p>
                <p><strong>SAO Staff:</strong> sao@sao.edu | password</p>
                <p><small>Note: Register for a student account</small></p>
            </div>
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
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.password-toggle');
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