<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User data from session
$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$user_type = $_SESSION['user_type'];
$email = $_SESSION['email'];

// Database connection (same as in login)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wakazilink";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// For demo purposes - sample data
$recent_jobs = [];
$notifications = [];
$earnings = [];
$top_workers = [];

if ($user_type === 'client') {
    // Sample data for client
    $recent_jobs = [
        ['id' => 1, 'worker' => 'John Kamau', 'service' => 'Plumbing', 'date' => '2025-06-15', 'status' => 'Completed', 'amount' => 2500],
        ['id' => 2, 'worker' => 'Sarah Mwangi', 'service' => 'Tailoring', 'date' => '2025-06-18', 'status' => 'In Progress', 'amount' => 1500],
        ['id' => 3, 'worker' => 'David Ochieng', 'service' => 'Welding', 'date' => '2025-06-20', 'status' => 'Scheduled', 'amount' => 3500]
    ];
    
    $notifications = [
        ['id' => 1, 'message' => 'Your booking with John Kamau has been confirmed', 'time' => '2 hours ago'],
        ['id' => 2, 'message' => 'Sarah Mwangi sent you a message about your dress', 'time' => '1 day ago'],
        ['id' => 3, 'message' => 'David Ochieng has completed the gate repair', 'time' => '3 days ago']
    ];
    
    $top_workers = [
        ['id' => 1, 'name' => 'John Kamau', 'service' => 'Plumber', 'rating' => 4.9, 'jobs' => 127],
        ['id' => 2, 'name' => 'Sarah Mwangi', 'service' => 'Tailor', 'rating' => 4.8, 'jobs' => 94],
        ['id' => 3, 'name' => 'David Ochieng', 'service' => 'Welder', 'rating' => 4.7, 'jobs' => 112]
    ];
} else {
    // Sample data for worker
    $recent_jobs = [
        ['id' => 1, 'client' => 'Mary Wambui', 'service' => 'Plumbing', 'date' => '2025-06-15', 'status' => 'Completed', 'amount' => 2500],
        ['id' => 2, 'client' => 'James Otieno', 'service' => 'Pipe Repair', 'date' => '2025-06-18', 'status' => 'In Progress', 'amount' => 1800],
        ['id' => 3, 'client' => 'Grace Akinyi', 'service' => 'Bathroom Installation', 'date' => '2025-06-20', 'status' => 'Scheduled', 'amount' => 4500]
    ];
    
    $notifications = [
        ['id' => 1, 'message' => 'New booking request from Peter Mwangi', 'time' => '1 hour ago'],
        ['id' => 2, 'message' => 'Mary Wambui rated your service 5 stars', 'time' => '2 days ago'],
        ['id' => 3, 'message' => 'Your payment for James Otieno job is ready', 'time' => '3 days ago']
    ];
    
    $earnings = [
        'week' => 12500,
        'month' => 43200,
        'total' => 187400
    ];
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WakaziLink Dashboard - Empowering Local Skills</title>
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
            --sidebar: #1a2b4f;
            --sidebar-hover: #2a3b5f;
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
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar);
            color: var(--white);
            height: 100vh;
            position: fixed;
            transition: var(--transition);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header .logo {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--white);
        }
        
        .sidebar-header i {
            color: var(--secondary);
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .menu-item {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--light);
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: var(--sidebar-hover);
            border-left: 4px solid var(--secondary);
        }
        
        .menu-item i {
            width: 24px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: var(--transition);
        }
        
        /* Top Navigation */
        .top-nav {
            background-color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 90;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            background-color: var(--light);
            border-radius: 30px;
            padding: 0.5rem 1rem;
            width: 400px;
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 0.3rem;
            outline: none;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .notification {
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, #1a4abf 100%);
            border-radius: 10px;
            padding: 2rem;
            color: var(--white);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            opacity: 0.9;
            max-width: 600px;
        }
        
        .welcome-stats {
            display: flex;
            gap: 2rem;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 1rem 1.5rem;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 1.2rem;
        }
        
        .card-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--light);
        }
        
        .table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
        }
        
        .status-in-progress {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        
        .status-pending {
            background-color: rgba(241, 196, 15, 0.15);
            color: #f1c40f;
        }
        
        .status-scheduled {
            background-color: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }
        
        .notification-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--light);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(42, 109, 244, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon i {
            color: var(--primary);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content p {
            margin-bottom: 0.3rem;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .worker-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--light);
        }
        
        .worker-card:last-child {
            border-bottom: none;
        }
        
        .worker-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--dark);
        }
        
        .worker-info {
            flex: 1;
        }
        
        .worker-info h4 {
            margin-bottom: 0.2rem;
        }
        
        .worker-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .worker-rating {
            color: #f39c12;
        }
        
        /* Worker Dashboard Specific */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 3rem;
            font-weight: bold;
        }
        
        .profile-info h2 {
            margin-bottom: 0.5rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .profile-stat .label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header .logo-text,
            .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 1rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .welcome-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .welcome-stats {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                width: 200px;
            }
            
            .welcome-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .top-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .user-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-hands-helping"></i>
            <div class="logo">WakaziLink</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Bookings</span>
            </a>
            <?php if ($user_type === 'worker'): ?>
                <a href="#" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>My Services</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-wallet"></i>
                    <span>Earnings</span>
                </a>
            <?php else: ?>
                <a href="#" class="menu-item">
                    <i class="fas fa-search"></i>
                    <span>Find Workers</span>
                </a>
            <?php endif; ?>
            <a href="#" class="menu-item">
                <i class="fas fa-comments"></i>
                <span>Messages</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="?logout=1" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
            
            <div class="user-actions">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo substr($fullname, 0, 1); ?></div>
                    <div>
                        <div class="user-name"><?php echo $fullname; ?></div>
                        <div class="user-role"><?php echo ucfirst($user_type); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo $fullname; ?>!</h1>
                    <p>Here's what's happening with your <?php echo $user_type; ?> account today.</p>
                </div>
                
                <?php if ($user_type === 'worker'): ?>
                <div class="welcome-stats">
                    <div class="stat-item">
                        <div class="stat-number">Ksh <?php echo number_format($earnings['week']); ?></div>
                        <div class="stat-label">This Week</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">Ksh <?php echo number_format($earnings['month']); ?></div>
                        <div class="stat-label">This Month</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($recent_jobs); ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="welcome-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($recent_jobs); ?></div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">12</div>
                        <div class="stat-label">Workers Saved</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">8</div>
                        <div class="stat-label">Reviews Given</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($user_type === 'worker'): ?>
            <!-- Worker Dashboard Content -->
            <div class="profile-header">
                <div class="profile-avatar"><?php echo substr($fullname, 0, 1); ?></div>
                <div class="profile-info">
                    <h2><?php echo $fullname; ?></h2>
                    <p>Professional Plumber | Nairobi, Kenya</p>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span>4.7 (128 reviews)</span>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="number">127</div>
                            <div class="label">Jobs Done</div>
                        </div>
                        <div class="profile-stat">
                            <div class="number">96%</div>
                            <div class="label">Success Rate</div>
                        </div>
                        <div class="profile-stat">
                            <div class="number">Ksh 1.2M</div>
                            <div class="label">Earned</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Jobs/Bookings -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $user_type === 'worker' ? 'Recent Jobs' : 'Recent Bookings'; ?></h3>
                        <a href="#">View All</a>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php if ($user_type === 'worker'): ?>
                                    <th>Client</th>
                                    <?php else: ?>
                                    <th>Worker</th>
                                    <?php endif; ?>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_jobs as $job): ?>
                                <tr>
                                    <?php if ($user_type === 'worker'): ?>
                                    <td><?php echo $job['client']; ?></td>
                                    <?php else: ?>
                                    <td><?php echo $job['worker']; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $job['service']; ?></td>
                                    <td><?php echo $job['date']; ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = 'status-'.strtolower(str_replace(' ', '-', $job['status']));
                                        echo '<span class="status-badge '.$statusClass.'">'.$job['status'].'</span>';
                                        ?>
                                    </td>
                                    <td>Ksh <?php echo number_format($job['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h3>Notifications</h3>
                        <a href="#">Mark as Read</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="notification-content">
                                <p><?php echo $notification['message']; ?></p>
                                <div class="notification-time"><?php echo $notification['time']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Second Row -->
            <div class="dashboard-grid">
                <?php if ($user_type === 'worker'): ?>
                <!-- Earnings Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>Earnings Overview</h3>
                        <a href="#">View Report</a>
                    </div>
                    <div class="card-body">
                        <div class="earnings-chart" style="height: 200px; display: flex; align-items: flex-end; gap: 10px; padding: 1rem 0;">
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 80px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Mon</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 120px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Tue</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 150px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Wed</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 100px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Thu</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 180px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Fri</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--primary); height: 60px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Sat</div>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background-color: var(--light-gray); height: 40px; width: 30px; border-radius: 5px;"></div>
                                <div style="margin-top: 10px; font-size: 0.8rem;">Sun</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--gray);">Total Earnings</div>
                                <div style="font-size: 1.5rem; font-weight: bold;">Ksh <?php echo number_format($earnings['total']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: var(--gray);">Available for Withdrawal</div>
                                <div style="font-size: 1.5rem; font-weight: bold;">Ksh <?php echo number_format($earnings['month'] * 0.8); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Top Workers -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Workers</h3>
                        <a href="#">View All</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($top_workers as $worker): ?>
                        <div class="worker-card">
                            <div class="worker-avatar"><?php echo substr($worker['name'], 0, 1); ?></div>
                            <div class="worker-info">
                                <h4><?php echo $worker['name']; ?></h4>
                                <div class="worker-meta">
                                    <span><?php echo $worker['service']; ?></span>
                                    <span class="worker-rating">
                                        <i class="fas fa-star"></i> <?php echo $worker['rating']; ?>
                                    </span>
                                    <span><?php echo $worker['jobs']; ?> jobs</span>
                                </div>
                            </div>
                            <button style="background: none; border: none; cursor: pointer;">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <a href="#" style="text-decoration: none;">
                                <div style="background-color: rgba(42, 109, 244, 0.1); border-radius: 8px; padding: 1.5rem; text-align: center; transition: var(--transition);">
                                    <div style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h3 style="color: var(--dark);">Find Workers</h3>
                                </div>
                            </a>
                            
                            <a href="#" style="text-decoration: none;">
                                <div style="background-color: rgba(243, 156, 18, 0.1); border-radius: 8px; padding: 1.5rem; text-align: center; transition: var(--transition);">
                                    <div style="font-size: 2rem; color: var(--secondary); margin-bottom: 0.5rem;">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <h3 style="color: var(--dark);">New Booking</h3>
                                </div>
                            </a>
                            
                            <a href="#" style="text-decoration: none;">
                                <div style="background-color: rgba(39, 174, 96, 0.1); border-radius: 8px; padding: 1.5rem; text-align: center; transition: var(--transition);">
                                    <div style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;">
                                        <i class="fas fa-comment-alt"></i>
                                    </div>
                                    <h3 style="color: var(--dark);">Messages</h3>
                                </div>
                            </a>
                            
                            <a href="#" style="text-decoration: none;">
                                <div style="background-color: rgba(155, 89, 182, 0.1); border-radius: 8px; padding: 1.5rem; text-align: center; transition: var(--transition);">
                                    <div style="font-size: 2rem; color: #9b59b6; margin-bottom: 0.5rem;">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <h3 style="color: var(--dark);">Settings</h3>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple notification toggle
        document.querySelector('.notification').addEventListener('click', function() {
            alert('Notifications panel would open here');
        });
        
        // Simple chart animation
        document.querySelectorAll('.earnings-chart > div > div:first-child').forEach((bar, index) => {
            // Animate the bars
            setTimeout(() => {
                bar.style.transition = 'height 0.5s ease';
                bar.style.height = '0px';
                setTimeout(() => {
                    bar.style.height = bar.dataset.height || getComputedStyle(bar).height;
                }, 10);
            }, index * 100);
        });
    </script>
</body>
</html>