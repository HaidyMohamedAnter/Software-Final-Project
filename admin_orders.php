<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit();
}

// ─────────────────────────────────────────
// Update Order Status
// ─────────────────────────────────────────

if(isset($_POST['update_order'])){

   $order_id = (int) $_POST['order_id'];
   $new_status = $_POST['update_payment'];

   $get_order = mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id'") or die('query failed');

   if(mysqli_num_rows($get_order) > 0){

      $order = mysqli_fetch_assoc($get_order);
      $old_status      = $order['payment_status'];
      $products_string = $order['total_products'] ?? '';   // ← Fixed: prevent NULL

      // Restore stock only when changing TO 'rejected'
      if($new_status == 'rejected' && $old_status != 'rejected' && !empty($products_string)){

         $products = explode(',', $products_string);

         foreach($products as $item){
            $item = trim($item);
            if(empty($item)) continue;

            preg_match('/(.*)\((\d+)\)/', $item, $matches);

            if(count($matches) == 3){
               $product_name = mysqli_real_escape_string($conn, trim($matches[1]));
               $quantity     = (int)$matches[2];

               mysqli_query($conn, "
                  UPDATE products 
                  SET quantity = quantity + $quantity
                  WHERE name = '$product_name'
               ") or die('query failed');
            }
         }
      }
   }

   // Update the order status
   mysqli_query($conn, "
      UPDATE orders 
      SET payment_status = '$new_status' 
      WHERE id = '$order_id'
   ") or die('query failed');

   $message[] = 'Order updated successfully!';
}

// ─────────────────────────────────────────
// Delete Order
// ─────────────────────────────────────────

if(isset($_GET['delete'])){
   $delete_id = (int) $_GET['delete'];

   $get_order = mysqli_query($conn, "SELECT payment_status FROM orders WHERE id = '$delete_id'") or die('query failed');
   
   if(mysqli_num_rows($get_order) > 0){
      $order = mysqli_fetch_assoc($get_order);

      if($order['payment_status'] == 'pending'){
         $message[] = 'Cannot delete a pending order! Update the status first.';
      }else{
         mysqli_query($conn, "DELETE FROM orders WHERE id = '$delete_id'") or die('query failed');
         header('location:admin_orders.php');
         exit();
      }
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Admin Orders</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<?php
if(isset($message) && is_array($message)){
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

<section class="orders">

   <h1 class="title">Placed Orders</h1>

   <div class="box-container">

   <?php
      $select_orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC") or die('query failed');

      if(mysqli_num_rows($select_orders) > 0){
         while($fetch_orders = mysqli_fetch_assoc($select_orders)){
            $status = $fetch_orders['payment_status'];
   ?>

   <div class="box">
      <p> user id : <span><?= $fetch_orders['user_id'] ?? 'N/A'; ?></span> </p>
      <p> placed on : <span><?= $fetch_orders['placed_on']; ?></span> </p>
      <p> name : <span><?= $fetch_orders['name']; ?></span> </p>
      <p> number : <span><?= $fetch_orders['number']; ?></span> </p>
      <p> email : <span><?= $fetch_orders['email']; ?></span> </p>
      <p> address : <span><?= $fetch_orders['address']; ?></span> </p>
      <p> total products : <span><?= $fetch_orders['total_products']; ?></span> </p>
      <p> total price : <span><?= $fetch_orders['total_price']; ?> EGP</span> </p>
      <p> payment method : <span><?= $fetch_orders['method']; ?></span> </p>
      <p> payment status : 
         <span style="color:<?php
            if($status == 'pending')   echo 'orange';
            elseif($status == 'completed') echo 'green';
            elseif($status == 'rejected')  echo 'red';
         ?>;">
            <?= $status; ?>
         </span>
      </p>

      <form action="" method="post">
         <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">

         <select name="update_payment">
            <option disabled selected><?= $status; ?></option>
            <option value="pending">pending</option>
            <option value="completed">completed</option>
            <option value="rejected">rejected</option>
         </select>

         <input type="submit" value="update" name="update_order" class="option-btn">
      </form>

      <?php if($status == 'pending'): ?>
         <a class="delete-btn" 
            style="background-color:gray; cursor:not-allowed; opacity:0.5;"
            title="Cannot delete a pending order — update status first">delete</a>
      <?php else: ?>
         <a href="admin_orders.php?delete=<?= $fetch_orders['id']; ?>"
            onclick="return confirm('Delete this order?');"
            class="delete-btn">delete</a>
      <?php endif; ?>

   </div>

   <?php
         }
      } else {
         echo '<p class="empty">no orders placed yet!</p>';
      }
   ?>

   </div>

</section>

<script src="js/admin_script.js"></script>

</body>
</html>