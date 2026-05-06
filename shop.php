<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit();
}

if(isset($_POST['add_to_cart'])){

   $product_id = (int) $_POST['product_id'];
   $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
   $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
   $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
   $product_quantity = (int) $_POST['product_quantity'];

   // 1. Get current warehouse stock
   $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'") or die('query failed');
   $fetch_product = mysqli_fetch_assoc($get_product);
   $total_warehouse_stock = (int)$fetch_product['quantity'];

   // 2. Check current quantity in this user's cart
   $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE product_id = '$product_id' AND user_id = '$user_id'");
   
   if(mysqli_num_rows($check_cart) > 0){
      $cart_item = mysqli_fetch_assoc($check_cart);
      $current_cart_qty = (int)$cart_item['quantity'];
      $new_total_qty = $current_cart_qty + $product_quantity;

      // Check if the NEW total exceeds warehouse stock
      if($new_total_qty > $total_warehouse_stock){
         $message[] = 'You reached the limit for this item!';
      } else {
         mysqli_query($conn, "UPDATE cart SET quantity = '$new_total_qty' WHERE id = '".$cart_item['id']."'");
         $message[] = 'Cart updated successfully!';
      }
   } else {
      // Check if first-time add exceeds warehouse stock
      if($product_quantity > $total_warehouse_stock){
         $message[] = 'Not enough stock available!';
      } else {
         mysqli_query($conn, "INSERT INTO cart(user_id, product_id, name, price, quantity, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')");
         $message[] = 'Product added to cart!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>shop</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
   <h3>our shop</h3>
   <p><a href="home.php">home</a> / shop</p>
</div>

<section class="products">

   <h1 class="title">latest products</h1>

   <div class="box-container">

   <?php  
   // We join the cart table to find out how many items THIS specific user already has
   // This allows us to calculate the "Available" quantity in real-time.
   $select_products = mysqli_query($conn, "
      SELECT p.*, IFNULL(c.quantity, 0) AS in_cart_quantity 
      FROM products p 
      LEFT JOIN cart c ON p.id = c.product_id AND c.user_id = '$user_id'
   ") or die('query failed');

   if(mysqli_num_rows($select_products) > 0){
      while($fetch_products = mysqli_fetch_assoc($select_products)){
         
         $warehouse_stock = (int)$fetch_products['quantity'];
         $in_cart = (int)$fetch_products['in_cart_quantity'];
         $display_quantity = $warehouse_stock - $in_cart;
   ?>

   <form action="" method="post" class="box">
      <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="">
      
      <div class="name"><?php echo $fetch_products['name']; ?></div>
      <div class="price"><?php echo $fetch_products['price']; ?> EGP</div>

      <!-- Logic for Stock Label -->
      <?php if($warehouse_stock == 0): ?>
         <div class="stock" style="color:red;">Out of Stock</div>
      <?php elseif($display_quantity == 0): ?>
         <div class="stock" style="color:orange;">All available units in your cart</div>
      <?php else: ?>
         <div class="stock" style="color:green;">Available: <?php echo $display_quantity; ?></div>
      <?php endif; ?>

      <!-- Logic for Button and Input -->
      <?php if($display_quantity <= 0){ ?>
         
         <button disabled class="btn" style="background-color:gray;">
            <?php echo ($warehouse_stock == 0) ? 'Out of stock' : 'Limit reached'; ?>
         </button>

      <?php } else { ?>

         <input 
            type="number" 
            min="1" 
            max="<?php echo $display_quantity; ?>" 
            name="product_quantity" 
            value="1" 
            class="qty"
         >

         <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
         <input type="hidden" name="product_name" value="<?php echo $fetch_products['name']; ?>">
         <input type="hidden" name="product_price" value="<?php echo $fetch_products['price']; ?>">
         <input type="hidden" name="product_image" value="<?php echo $fetch_products['image']; ?>">

         <input type="submit" value="add to cart" name="add_to_cart" class="btn">

      <?php } ?>

   </form>

   <?php
      }
   } else {
      echo '<p class="empty">no products added yet!</p>';
   }
   ?>

   </div>

</section>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>