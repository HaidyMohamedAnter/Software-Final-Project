<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit();
}


if(isset($_POST['update_order'])){

   $order_id = (int) $_POST['order_id'];
   $new_status = $_POST['update_payment'];


   $get_order = mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id'") or die('query failed');

   if(mysqli_num_rows($get_order) > 0){

      $order = mysqli_fetch_assoc($get_order);

      $old_status = $order['payment_status'];
      $products_string = $order['total_products'];

      
      if($new_status == 'rejected' && $old_status != 'rejected'){

         
         $products = explode(',', $products_string);

         foreach($products as $item){

           
            preg_match('/(.*)\((\d+)\)/', trim($item), $matches);

            if(count($matches) == 3){

               $product_name = mysqli_real_escape_string($conn, trim($matches[1]));
               $quantity = (int)$matches[2];

              
               mysqli_query($conn, "
                  UPDATE products 
                  SET quantity = quantity + $quantity
                  WHERE name = '$product_name'
               ") or die('query failed');
            }
         }
      }
   }

  
   mysqli_query($conn, "
      UPDATE orders 
      SET payment_status = '$new_status' 
      WHERE id = '$order_id'
   ") or die('query failed');

   $message[] = 'Order updated successfully!';
}


if(isset($_GET['delete'])){
   $delete_id = (int) $_GET['delete'];

   mysqli_query($conn, "DELETE FROM orders WHERE id = '$delete_id'") or die('query failed');

   header('location:admin_orders.php');
   exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Admin Orders</title>

   <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<section class="orders">

   <h1 class="title">Placed Orders</h1>

   <div class="box-container">

   <?php
      $select_orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC") or die('query failed');

      if(mysqli_num_rows($select_orders) > 0){
         while($fetch_orders = mysqli_fetch_assoc($select_orders)){
   ?>

   <div class="box">
      <p> user id : <span><?php echo $fetch_orders['user_id']; ?></span> </p>
      <p> placed on : <span><?php echo $fetch_orders['placed_on']; ?></span> </p>
      <p> name : <span><?php echo $fetch_orders['name']; ?></span> </p>
      <p> number : <span><?php echo $fetch_orders['number']; ?></span> </p>
      <p> email : <span><?php echo $fetch_orders['email']; ?></span> </p>
      <p> address : <span><?php echo $fetch_orders['address']; ?></span> </p>
      <p> total products : <span><?php echo $fetch_orders['total_products']; ?></span> </p>
      <p> total price : <span><?php echo $fetch_orders['total_price']; ?> EGP</span> </p>
      <p> payment method : <span><?php echo $fetch_orders['method']; ?></span> </p>

      <form action="" method="post">
         <input type="hidden" name="order_id" value="<?php echo $fetch_orders['id']; ?>">

         <select name="update_payment">
            <option disabled selected><?php echo $fetch_orders['payment_status']; ?></option>
            <option value="pending">pending</option>
            <option value="completed">completed</option>
            <option value="rejected">rejected</option>
         </select>

         <input type="submit" value="update" name="update_order" class="option-btn">

         <a href="admin_orders.php?delete=<?php echo $fetch_orders['id']; ?>" 
            onclick="return confirm('delete this order?');" 
            class="delete-btn">delete</a>
      </form>
   </div>

   <?php
         }
      }else{
         echo '<p class="empty">no orders placed yet!</p>';
      }
   ?>

   </div>

</section>

</body>
</html>