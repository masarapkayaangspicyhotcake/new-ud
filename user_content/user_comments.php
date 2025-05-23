<?php

include '../components/connect.php';

$db = new Database();
$conn = $db->connect();


session_start();

if(isset($_SESSION['account_id'])){
   $user_id = $_SESSION['account_id'];
}else{
   $user_id = '';
   header('location:home.php');
};

if(isset($_POST['edit_comment'])){

   $edit_comment_id = $_POST['edit_comment_id'];
   $edit_comment_id = filter_var($edit_comment_id, FILTER_SANITIZE_STRING);
   $comment_edit_box = $_POST['comment_edit_box'];
   $comment_edit_box = filter_var($comment_edit_box, FILTER_SANITIZE_STRING);

   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE comment = ? AND comment_id = ?");
   $verify_comment->execute([$comment_edit_box, $edit_comment_id]);

   if($verify_comment->rowCount() > 0){
      $message[] = 'comment already added!';
   }else{
      $update_comment = $conn->prepare("UPDATE `comments` SET comment = ? WHERE comment_id = ?");
      $update_comment->execute([$comment_edit_box, $edit_comment_id]);
      $message[] = 'your comment edited successfully!';
   }
   
}

if(isset($_POST['delete_comment'])){
   $delete_comment_id = $_POST['comment_id'];
   $delete_comment_id = filter_var($delete_comment_id, FILTER_SANITIZE_STRING);
   $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE comment_id = ?");
   $delete_comment->execute([$delete_comment_id]);
   $message[] = 'comment deleted successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>update profile</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/style.css">

   <style>
      /* Fix header spacing */
      body {
         padding-top: 120px; /* Increased from 80px to create more space */
      }
      
      /* Remove conflicting header styles */
      .header {
         position: fixed;
         top: 0;
         left: 0;
         right: 0;
         z-index: 1000;
         margin-top: 0; /* Remove the conflicting margin */
      }
      
      .comments-container, 
      .comment-edit-form {
         margin-top: 30px;
      }
      
      /* Additional mobile responsive styles */
      @media (max-width: 768px) {
         body {
            padding-top: 140px; /* Even more space on mobile */
         }
      }
      
      @media (max-width: 480px) {
         body {
            padding-top: 160px; /* More space for smaller screens */
         }
      }
   </style>
</head>
<body>
   
<!-- header section starts  -->
<?php include '../components/user_header.php'; ?>
<!-- header section ends -->

<?php
   if(isset($_POST['open_edit_box'])){
   $comment_id = $_POST['comment_id'];
   $comment_id = filter_var($comment_id, FILTER_SANITIZE_STRING);
?>
   <section class="comment-edit-form">
   <p>edit your comment</p>
   <?php
      $select_edit_comment = $conn->prepare("SELECT * FROM `comments` WHERE comment_id = ?");
      $select_edit_comment->execute([$comment_id]);
      $fetch_edit_comment = $select_edit_comment->fetch(PDO::FETCH_ASSOC);
   ?>
   <form action="" method="POST">
      <input type="hidden" name="edit_comment_id" value="<?= $comment_id; ?>">
      <textarea name="comment_edit_box" required cols="30" rows="10" placeholder="please enter your comment"><?= $fetch_edit_comment['comment']; ?></textarea>
      <button type="submit" class="inline-btn" name="edit_comment">edit comment</button>
      <div class="inline-option-btn" onclick="window.location.href = 'user_comments.php';">cancel edit</div>
   </form>
   </section>
<?php
   }
?>

<section class="comments-container">

   <h1 class="heading">your comments</h1>

   <p class="comment-title">your comments on the posts</p>
   <div class="user-comments-container">
      <?php
         $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE commented_by = ?");
         $select_comments->execute([$user_id]);
         if($select_comments->rowCount() > 0){
            while($fetch_comments = $select_comments->fetch(PDO::FETCH_ASSOC)){
      ?>
      <div class="show-comments">
         <?php
            $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ?");
            $select_posts->execute([$fetch_comments['post_id']]);
            while($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)){
         ?>
         <div class="post-title"> from : <span><?= $fetch_posts['title']; ?></span> <a href="view_post.php?post_id=<?= $fetch_posts['post_id']; ?>" >view post</a></div>
         <?php
            }
         ?>
         <div class="comment-box"><?= $fetch_comments['comment']; ?></div>
         <form action="" method="POST">
            <input type="hidden" name="comment_id" value="<?= $fetch_comments['comment_id']; ?>">
            <button type="submit" class="inline-option-btn" name="open_edit_box">edit comment</button>
            <button type="submit" class="inline-delete-btn" name="delete_comment" onclick="return confirm('delete this comment?');">delete comment</button>
         </form>
      </div>
      <?php
            }
         }else{
            echo '<p class="empty">no comments added yet!</p>';
         }
      ?>
   </div>
</section>

<!-- footer section starts -->
<?php include '../components/footer.php'; ?>
<!-- footer section ends -->

<!-- custom js file link  -->
<script src="../js/script.js"></script>
<script src="../js/comments.js"></script>
</body>
</html>