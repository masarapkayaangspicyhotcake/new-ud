<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Redirect if already logged in
if(isset($_SESSION['account_id'])){
   // Verify the account still exists and is valid
   $check_valid = $conn->prepare("SELECT account_id FROM accounts WHERE account_id = ?");
   $check_valid->execute([$_SESSION['account_id']]);
   
   if($check_valid->rowCount() > 0) {
      // Session is valid, redirect
      header('location: home.php');
      exit();
   } else {
      // Invalid session, clear it
      session_unset();
      session_destroy();
      session_start();
   }
}

$message = [];

// This will Generate token only if 1 doesnt exist(eto ung mag generate ng token)
if (!isset($_SESSION['csrf_token'])) {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
}
$token = $_SESSION['csrf_token'];

// Enhanced lockout handling with timer
if(isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    $lockout_time = 300; // 5-minute lockout in seconds (changed from 900)
    $elapsed_time = time() - $_SESSION['last_attempt'];
    $remaining_time = $lockout_time - $elapsed_time;
    
    if($remaining_time > 0) { 
        // Still in lockout period
        $minutes = floor($remaining_time / 60);
        $seconds = $remaining_time % 60;
        $message[] = "Account temporarily locked.";
        $blocked = true;
        
        // Set variables to enable the JavaScript countdown
        $show_timer = true;
        $timer_end = $_SESSION['last_attempt'] + $lockout_time;
    } else {
        // Reset counter after lockout period
        $_SESSION['login_attempts'] = 0;
    }
}
if(isset($_POST['submit']) && !isset($blocked)){
   // Verify CSRF token
   if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      $message[] = 'Invalid form submission.';
   } else {
      // Get and sanitize input
      $email = trim($_POST['email']);
      $password = $_POST['password'];
      
      // Validate input
      if(empty($email)){
         $message[] = 'Email is required!';
      }
      
      if(empty($password)){
         $message[] = 'Password is required!';
      }
      
      // If no validation errors, attempt login
      if(empty($message)){
         // Query accounts table for the user with this email
         $select_user = $conn->prepare("SELECT * FROM `accounts` WHERE email = ?");
         $select_user->execute([$email]);
         
         if($select_user->rowCount() > 0){
            $user = $select_user->fetch(PDO::FETCH_ASSOC);
            
            // Verify password using secure method
            if(password_verify($password, $user['password'])){
               // Regenerate session ID
               session_regenerate_id(true);
               
               // Set universal session variables
               $_SESSION['account_id'] = $user['account_id'];
               $_SESSION['user_name'] = $user['user_name'];
               $_SESSION['role'] = $user['role'];
               
               // For backward compatibility with admin sections
               if($user['role'] === 'superadmin' || $user['role'] === 'subadmin'){
                  $_SESSION['admin_id'] = $user['account_id'];
                  $_SESSION['admin_role'] = $user['role'];
               }
               
               // Reset login attempt counter
               unset($_SESSION['login_attempts']);
               
               // Redirect based on role
               if($user['role'] === 'superadmin'){
                  header('location: home.php');
               } elseif($user['role'] === 'subadmin'){
                  header('location: home.php');
               } else {
                  header('location: home.php');
               }
               exit();
            } else {
               // Track failed attempts
               $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
               $_SESSION['last_attempt'] = time();
               $message[] = 'Incorrect password!';
            }
         } else {
            $message[] = 'Invalid.';
         }
      }
   }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login - The University Digest</title>

   <!-- custom css file link -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/lockout.css">
   
</head>
<body>

<!-- Include the header -->
<?php include '../components/user_header.php'; ?>
   
<section class="form-container">
   <form action="" method="post">
      <h3>Login to Your Account</h3>
      
      <?php if(!empty($message)): ?>
      <div class="message">
         <?php foreach($message as $msg): ?>
            <p><?= $msg; ?></p>
         <?php endforeach; ?>
      </div>
      <?php endif; ?>
      
      <!-- Add the timer display here -->
      <?php if(isset($show_timer) && $show_timer): ?>
         <div id="lockoutTimer" class="lockout-message"></div>
      <?php endif; ?>

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
      <div class="input-group">
         <input type="email" name="email" placeholder="Enter your email" class="box" value="<?= $email ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="password" name="password" placeholder="Enter your password" class="box">
      </div>
      <button type="submit" name="submit" class="btn" style="width:100%;margin-top:10px;">Login Now</button>
      <div style="margin: 20px 0; text-align: center;">or</div>
      <a href="../google_handlers/google_auth_handler.php" class="google-btn" style="
         width:100%;
         display:flex;
         align-items:center;
         justify-content:center;
         padding: 16px 0;
         font-size: 1.2rem;
         background-color: #800000;
         color: #fff;
         border: none;
         border-radius: 4px;
         font-weight: bold;
         cursor: pointer;
         text-decoration: none;
         transition: background 0.2s;">
         <svg width="28" height="28" viewBox="0 0 48 48" style="margin-right:14px;">
            <g>
               <path fill="#4285F4" d="M43.6 20.5h-1.9V20H24v8h11.3c-1.6 4.3-5.7 7.5-10.3 7.5-6.1 0-11-4.9-11-11s4.9-11 11-11c2.6 0 5 .9 6.9 2.4l6.6-6.6C36.1 6.5 30.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.2-.1-3.5z"/>
               <path fill="#34A853" d="M6.3 14.1l6.6 4.8C14.7 16.1 19 13 24 13c2.6 0 5 .9 6.9 2.4l6.6-6.6C36.1 6.5 30.4 4 24 4c-7.7 0-14.4 4.4-17.7 10.1z"/>
               <path fill="#FBBC05" d="M24 44c6.2 0 11.4-2 15.2-5.5l-7-5.7c-2 1.4-4.5 2.2-8.2 2.2-4.6 0-8.7-3.2-10.3-7.5l-6.7 5.2C9.6 39.6 16.2 44 24 44z"/>
               <path fill="#EA4335" d="M43.6 20.5h-1.9V20H24v8h11.3c-1.1 3-3.6 5.5-7.3 5.5-4.6 0-8.7-3.2-10.3-7.5l-6.7 5.2C9.6 39.6 16.2 44 24 44c11 0 19.7-8 19.7-20 0-1.3-.1-2.2-.1-3.5z"/>
            </g>
         </svg>
         <span>Login using Gmail</span>
      </a>
      <p>Don't have an account? <a href="register.php">Register now</a></p>
   </form>
</section>

<!-- Include the footer -->
<?php include '../components/footer.php'; ?>

<!-- At the end of the body, load the JS -->
<?php if(isset($show_timer) && $show_timer): ?>
<script src="../js/login_timer.js"></script>
<script>
    // Initialize the timer with the server-provided end time
    document.addEventListener('DOMContentLoaded', function() {
        initLoginTimer(<?= $timer_end * 1000 ?>);
    });
</script>
<?php endif; ?>

</body>
</html>