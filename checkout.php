<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit();
}

if(isset($_POST['order_btn'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $number = mysqli_real_escape_string($conn, $_POST['number']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $method = mysqli_real_escape_string($conn, $_POST['method']);
   $address = mysqli_real_escape_string($conn, 
      'flat no. '. $_POST['flat'].', '.
      $_POST['street'].', '.
      $_POST['city'].', '.
      $_POST['country'].' - '.
      $_POST['pin_code']
   );

   $placed_on = date('d-M-Y');

   $cart_total = 0;
   $cart_products = [];

   
   $cart_query = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'") or die('query failed');

   if(mysqli_num_rows($cart_query) > 0){
      while($cart_item = mysqli_fetch_assoc($cart_query)){
         $cart_products[] = $cart_item['name'].' ('.$cart_item['quantity'].')';
         $sub_total = $cart_item['price'] * $cart_item['quantity'];
         $cart_total += $sub_total;
      }
   }

   $total_products = mysqli_real_escape_string($conn, implode(', ', $cart_products));

   
   $order_query = mysqli_query($conn, "
      SELECT * FROM orders 
      WHERE name = '$name' 
      AND number = '$number' 
      AND email = '$email' 
      AND method = '$method' 
      AND address = '$address' 
      AND total_products = '$total_products' 
      AND total_price = '$cart_total'
   ") or die('query failed');

   if($cart_total == 0){
      $message[] = 'Your cart is empty!';
   }
   elseif(mysqli_num_rows($order_query) > 0){
      $message[] = 'Order already placed!';
   }
   else{
      // ✅ 1. update stock
     $cart_items = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'");

     while($item = mysqli_fetch_assoc($cart_items)){

       $product_id = $item['product_id'];
      $qty = $item['quantity'];

       $check = mysqli_query($conn, "SELECT quantity FROM products WHERE id='$product_id'");
       $product = mysqli_fetch_assoc($check);

   if($qty > $product['quantity']){
      die('Some items are out of stock!');
   }

   mysqli_query($conn, "
      UPDATE products 
      SET quantity = quantity - $qty 
      WHERE id = '$product_id'
   ");
}

// ✅ 2. save order
mysqli_query($conn, "
   INSERT INTO orders
   (user_id, name, number, email, method, address, total_products, total_price, placed_on)
   VALUES
   ('$user_id', '$name', '$number', '$email', '$method', '$address', '$total_products', '$cart_total', '$placed_on')
") or die('query failed');

// ✅ 3. clear cart
mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");

  
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>checkout</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
   <h3>checkout</h3>
   <p><a href="home.php">home</a> / checkout</p>
</div>

<section class="display-order">

<?php  
$grand_total = 0;
$select_cart = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'") or die('query failed');

if(mysqli_num_rows($select_cart) > 0){
   while($fetch_cart = mysqli_fetch_assoc($select_cart)){
      $total_price = $fetch_cart['price'] * $fetch_cart['quantity'];
      $grand_total += $total_price;
?>

<p>
   <?php echo $fetch_cart['name']; ?> 
   <span>(<?php echo $fetch_cart['price']; ?> EGPx <?php echo $fetch_cart['quantity']; ?> piece)</span>
</p>

<?php
   }
}else{
   echo '<p class="empty">your cart is empty</p>';
}
?>

<div class="grand-total">grand total : <span><?php echo $grand_total; ?> EGP</span></div>

</section>

<section class="checkout">

<form action="" method="post">
   <h3>place your order</h3>

   <div class="flex">

      <div class="inputBox">
         <span>your name :</span>
         <input type="text" name="name" required>
      </div>

      <div class="inputBox">
         <span>your number :</span>
         <input type="number" name="number" required>
      </div>

      <div class="inputBox">
         <span>your email :</span>
         <input type="email" name="email" required>
      </div>

      <div class="inputBox">
         <span>payment method :</span>
         <select name="method">
            <option value="cash on delivery">cash on delivery</option>
            <option value="credit card">credit card</option>
            <option value="paypal">paypal</option>
         </select>
      </div>

      <div class="inputBox">
         <span>flat :</span>
         <input type="text" name="flat" required>
      </div>

      <div class="inputBox">
         <span>street :</span>
         <input type="text" name="street" required>
      </div>

      <div class="inputBox">
         <span>city :</span>
         <input type="text" name="city" required>
      </div>

      <div class="inputBox">
         <span>country :</span>
         <input type="text" name="country" required>
      </div>

      <div class="inputBox">
         <span>pin code :</span>
         <input type="number" name="pin_code" required>
      </div>

   </div>

   <input type="submit" value="order now" name="order_btn" class="btn">

</form>

</section>

<?php include 'footer.php'; ?>

</body>
</html>