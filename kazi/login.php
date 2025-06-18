<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wakazilink";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('client','worker') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Handle form submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        // Registration form submitted
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $user_type = $_POST['user_type'];
        
        // Validate inputs
        if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Email already registered";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $fullname, $email, $phone, $hashed_password, $user_type);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['login'])) {
        // Login form submitted
        $email = $_POST['login_email'];
        $password = $_POST['login_password'];
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = "Email and password are required";
        } else {
            // Check user credentials
            $stmt = $conn->prepare("SELECT id, fullname, password, user_type FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Start session and set user data
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['email'] = $email;
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WakaziLink - Empowering Local Skills</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2a6df4;
            --secondary: #f39c12;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --success: #27ae60;
            --danger: #e74c3c;
            --gray: #7f8c8d;
            --light-gray: #bdc3c7;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
        }
        
        .logo i {
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--white);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Auth Container */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 2rem;
        }
        
        .auth-box {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1a4abf 100%);
            color: var(--white);
            padding: 1.5rem;
            text-align: center;
        }
        
        .auth-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .auth-tabs {
            display: flex;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 0.25rem;
            margin: 1rem auto 0;
            max-width: 300px;
        }
        
        .auth-tab {
            flex: 1;
            padding: 0.5rem;
            text-align: center;
            cursor: pointer;
            border-radius: 30px;
            transition: var(--transition);
        }
        
        .auth-tab.active {
            background-color: var(--white);
            color: var(--primary);
            font-weight: 600;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 109, 244, 0.2);
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .radio-option input {
            margin: 0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1rem;
            color: var(--gray);
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Mobile Navigation */
        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background-color: var(--white);
                flex-direction: column;
                padding: 2rem;
                box-shadow: var(--shadow);
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .mobile-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Header/Navigation -->
    <header>
        <nav class="navbar">
            <a href="#" class="logo">
                <i class="fas fa-hands-helping"></i>
                <span>WakaziLink</span>
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#categories">Categories</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="#" class="btn btn-outline" id="loginBtn">Log In</a>
                <a href="#" class="btn btn-primary" id="signupBtn">Sign Up</a>
            </div>
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <!-- Auth Container -->
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>Welcome to WakaziLink</h2>
                <p>Join us to empower local skills in Kenya</p>
                <div class="auth-tabs">
                    <div class="auth-tab active" id="loginTab">Log In</div>
                    <div class="auth-tab" id="signupTab">Sign Up</div>
                </div>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form class="auth-form active" id="loginForm" method="POST">
                    <div class="form-group">
                        <label for="login_email">Email</label>
                        <input type="email" id="login_email" name="login_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input type="password" id="login_password" name="login_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-block">Log In</button>
                    
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="#" id="switchToSignup">Sign Up</a></p>
                        <p><a href="#">Forgot Password?</a></p>
                    </div>
                </form>
                
                <!-- Registration Form -->
                <form class="auth-form" id="signupForm" method="POST">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. 0750450254" required>
                    </div>
                    
                    <div class="form-group">
                        <label>I am a:</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="client" name="user_type" value="client" checked>
                                <label for="client">Client</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="worker" name="user_type" value="worker">
                                <label for="worker">Worker</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-secondary btn-block">Create Account</button>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="#" id="switchToLogin">Log In</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle between login and signup forms
        const loginTab = document.getElementById('loginTab');
        const signupTab = document.getElementById('signupTab');
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        const switchToSignup = document.getElementById('switchToSignup');
        const switchToLogin = document.getElementById('switchToLogin');
        const loginBtn = document.getElementById('loginBtn');
        const signupBtn = document.getElementById('signupBtn');
        
        function showLogin() {
            loginTab.classList.add('active');
            signupTab.classList.remove('active');
            loginForm.classList.add('active');
            signupForm.classList.remove('active');
        }
        
        function showSignup() {
            signupTab.classList.add('active');
            loginTab.classList.remove('active');
            signupForm.classList.add('active');
            loginForm.classList.remove('active');
        }
        
        loginTab.addEventListener('click', showLogin);
        signupTab.addEventListener('click', showSignup);
        switchToSignup.addEventListener('click', (e) => {
            e.preventDefault();
            showSignup();
        });
        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            showLogin();
        });
        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showLogin();
        });
        signupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showSignup();
        });
        
        // Mobile navigation toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const navLinks = document.getElementById('navLinks');
        
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
        
        // Form validation
        const signupFormEl = document.getElementById('signupForm');
        signupFormEl.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>