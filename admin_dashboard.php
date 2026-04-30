<?php
session_start();
include 'dbconnect.php';
 
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

 
$allowed_pages = ['stats', 'history', 'credit', 'users'];
$page = (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) ? $_GET['page'] : 'stats';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Online Banking</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        :root { --primary: #2c3e50; --secondary: #34495e; --accent: #27ae60; --danger: #e74c3c; }
        body { margin: 0; display: flex; height: 100vh; background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--primary); color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 30px 20px; background: rgba(0,0,0,0.1); text-align: center; }
        .nav-menu { flex-grow: 1; padding: 20px 0; }
        .nav-item { 
            padding: 15px 25px; display: block; color: rgba(255,255,255,0.7); 
            text-decoration: none; transition: 0.3s; border-left: 4px solid transparent; 
        }
        .nav-item:hover, .nav-item.active { 
            background: rgba(255,255,255,0.1); color: white; border-left-color: var(--accent); 
        }
        
    
        .main-content { flex-grow: 1; overflow-y: auto; padding: 40px; }
        .module-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #eee; color: #7f8c8d; }
        td { padding: 12px; border-bottom: 1px solid #f9f9f9; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-success { background: var(--accent); color: white; }
        .btn-danger { background: var(--danger); color: white; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2 style="margin:0;">Bank Admin</h2>
        <small>System Management</small>
    </div>
    
    <nav class="nav-menu">
        <a href="admin_dashboard.php?page=stats" class="nav-item <?php echo $page=='stats'?'active':''; ?>">📊Total money</a>
        <a href="admin_dashboard.php?page=history" class="nav-item <?php echo $page=='history'?'active':''; ?>">📜 audit log</a>
        <a href="admin_dashboard.php?page=credit" class="nav-item <?php echo $page=='credit'?'active':''; ?>"> GIve money </a>
        <a href="admin_dashboard.php?page=users" class="nav-item <?php echo $page=='users'?'active':''; ?>">👤 Account managment</a>
    </nav>

    <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="logout.php" style="color: #ecf0f1; text-decoration: none; font-size: 0.9rem;">Logout System</a>
    </div>
</aside>
<div class="module-header">

</div>

<?php echo $message; ?>
 
<style>
    .admin-input { 
        width: 100%; padding: 12px; margin: 8px 0 20px; 
        border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; 
    }
    .form-group label { font-weight: bold; color: var(--primary); }
</style>

<main class="main-content">
    <div class="module-card">
        <?php 
        
            include "admin_modules/" . $page . ".php"; 
        ?>
    </div>
</main>

</body>
</html>
