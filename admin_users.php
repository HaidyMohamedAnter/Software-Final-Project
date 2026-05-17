<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit();
}

if(isset($_GET['delete'])){
   $delete_id = (int)$_GET['delete'];

   // Step 1: Handle Pending Orders - Restore Stock & Delete them
   $pending_check = mysqli_query($conn, "
      SELECT * FROM orders 
      WHERE user_id = '$delete_id' AND payment_status = 'pending'
   ") or die('query failed');

   if(mysqli_num_rows($pending_check) > 0){
      while($order = mysqli_fetch_assoc($pending_check)){
         $products_string = $order['total_products'];
         $products = explode(',', $products_string);

         foreach($products as $item){
            preg_match('/(.*)\((\d+)\)/', trim($item), $matches);
            if(count($matches) == 3){
               $product_name = mysqli_real_escape_string($conn, trim($matches[1]));
               $quantity     = (int)$matches[2];

               // Restore stock
               mysqli_query($conn, "
                  UPDATE products 
                  SET quantity = quantity + $quantity 
                  WHERE name = '$product_name'
               ") or die('query failed');
            }
         }

         // Delete this pending order
         mysqli_query($conn, "DELETE FROM orders WHERE id = '{$order['id']}'") 
         or die('query failed');
      }
   }

   // Step 2: Preserve completed orders (set user_id to NULL)
   mysqli_query($conn, "UPDATE orders 
                        SET user_id = NULL 
                        WHERE user_id = '$delete_id'") 
   or die('query failed');

   // Step 3: Delete the user
   mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('query failed');

   // Success Message
   $message[] = 'User deleted successfully! Pending orders were cancelled and stock restored. Completed orders preserved.';

   header('location:admin_users.php');
   exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>users</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<?php
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="message">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="users">

   <h1 class="title"> user accounts </h1>

   <div class="box-container">
      <?php
         $select_users = mysqli_query($conn, "SELECT * FROM `users`") or die('query failed');
         while($fetch_users = mysqli_fetch_assoc($select_users)){

            // Check if this user has pending orders
            $pending = mysqli_query($conn, "
               SELECT COUNT(*) AS cnt FROM orders 
               WHERE user_id = '{$fetch_users['id']}' AND payment_status = 'pending'
            ");
            $pending_row  = mysqli_fetch_assoc($pending);
            $has_pending  = $pending_row['cnt'] > 0;
      ?>
      <div class="box">
         <p> user id : <span><?php echo $fetch_users['id']; ?></span> </p>
         <p> username : <span><?php echo $fetch_users['name']; ?></span> </p>
         <p> email : <span><?php echo $fetch_users['email']; ?></span> </p>
         <p> user type : 
            <span style="color:<?php if($fetch_users['user_type'] == 'admin'){ echo 'var(--orange)'; } ?>">
               <?php echo $fetch_users['user_type']; ?>
            </span> 
         </p>

         <?php if($has_pending): ?>
            <p style="color:orange; font-size:0.85rem;">
               ⚠ This user has pending orders — stock will be restored if deleted.
            </p>
            <a href="admin_users.php?delete=<?php echo $fetch_users['id']; ?>" 
               onclick="return confirm('This user has pending orders.\nStock will be restored and pending orders will be cancelled.\nCompleted orders will be preserved.\n\nDelete anyway?');" 
               class="delete-btn">delete user</a>
         <?php else: ?>
            <a href="admin_users.php?delete=<?php echo $fetch_users['id']; ?>" 
               onclick="return confirm('delete this user?');" 
               class="delete-btn">delete user</a>
         <?php endif; ?>

      </div>
      <?php }; ?>
   </div>

</section>

<script src="js/admin_script.js"></script>

</body>
</html>