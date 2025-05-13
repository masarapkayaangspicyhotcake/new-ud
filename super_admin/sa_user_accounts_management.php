<?php
// Include the database connection file
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Start the session
session_start();

// Check if the admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Initialize $message as an array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Delete user
if (isset($_GET['delete'])) {
   $delete_id = $_GET['delete'];
   
   // Check if user exists before deleting
   $check_user = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ? AND role = 'user'");
   $check_user->execute([$delete_id]);
   
   if($check_user->rowCount() > 0) {
      try {
         // Begin transaction
         $conn->beginTransaction();
         
         // Delete the user
         $delete_user = $conn->prepare("DELETE FROM `accounts` WHERE account_id = ? AND role = 'user'");
         $delete_user->execute([$delete_id]);
         
         $conn->commit();
         $message[] = 'User deleted successfully!';
      } catch (PDOException $e) {
         $conn->rollBack();
         $message[] = 'Error deleting user: ' . $e->getMessage();
      }
   } else {
      $message[] = 'User not found or you do not have permission to delete!';
   }
}

// Fetch all users
$select_users = $conn->prepare("SELECT * FROM `accounts` WHERE role = 'user'");
$select_users->execute();
$users = $select_users->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Management | Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="user-management">
   <h1 class="heading">User Management</h1>

   <?php
   if (isset($message) && is_array($message)) {
      foreach ($message as $msg) {
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <div class="box-container">
      <?php if (count($users) > 0): ?>
         <?php foreach ($users as $user): ?>
            <div class="box">
               <!-- Removed profile image section -->
               
               <p>User ID: <span><?= $user['account_id']; ?></span></p>
               <p>Name: <span><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></span></p>
               <p>Username: <span><?= htmlspecialchars($user['user_name']); ?></span></p>
               <p>Email: <span><?= htmlspecialchars($user['email']); ?></span></p>
               <p>Role: <span><?= $user['role']; ?></span></p>
               <a href="sa_user_accounts_management.php?delete=<?= $user['account_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</a>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">No users found!</p>
      <?php endif; ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>