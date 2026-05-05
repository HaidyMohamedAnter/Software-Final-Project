<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit();
}

if(isset($_POST['add_to_cart'])){

   $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
   $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
   $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
   $product_quantity = (int) $_POST['product_quantity'];
   $product_id = (int) $_POST['product_id'];

   
   $check_stock = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'") or die('query failed');
   $fetch_stock = mysqli_fetch_assoc($check_stock);
   $available_quantity = (int)$fetch_stock['quantity'];


   $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE product_id = '$product_id' AND user_id = '$user_id'") or die('query failed');

   if($product_quantity > $available_quantity){
      $message[] = 'Sorry, not enough stock available!';
   }
   elseif(mysqli_num_rows($check_cart) > 0){

      $fetch_cart = mysqli_fetch_assoc($check_cart);
      $new_cart_quantity = $fetch_cart['quantity'] + $product_quantity;

      if($product_quantity > $available_quantity){
         $message[] = 'Sorry, not enough stock available!';
      }else{

         
         mysqli_query($conn, "UPDATE cart 
         SET quantity = '$new_cart_quantity' 
         WHERE id = '".$fetch_cart['id']."'")
         or die('query failed');

       
         $new_quantity = $available_quantity - $product_quantity;

         mysqli_query($conn, "UPDATE products 
         SET quantity = '$new_quantity' 
         WHERE id = '$product_id'")
         or die('query failed');

         $message[] = 'Cart updated successfully!';
      }
   }
   else{

      
      mysqli_query($conn, "INSERT INTO cart(user_id, product_id, name, price, quantity, image)
      VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')")
      or die('query failed');

     
      $new_quantity = $available_quantity - $product_quantity;

      mysqli_query($conn, "UPDATE products 
      SET quantity = '$new_quantity' 
      WHERE id = '$product_id'")
      or die('query failed');

      $message[] = 'Product added to cart!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>home</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="products">

   <h1 class="title">latest products</h1>

   <div class="box-container">

   <?php  
   $select_products = mysqli_query($conn, "SELECT * FROM products") or die('query failed');

   if(mysqli_num_rows($select_products) > 0){
      while($fetch_products = mysqli_fetch_assoc($select_products)){
   ?>

   <form action="" method="post" class="box">
      <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="">
      <div class="name"><?php echo $fetch_products['name']; ?></div>
      <div class="price"><?php echo $fetch_products['price']; ?> EGP</div>

      <div class="stock">
         Available: <?php echo $fetch_products['quantity']; ?>
      </div>

      
      <input 
         type="number" 
         min="1" 
         max="<?php echo $fetch_products['quantity']; ?>" 
         name="product_quantity" 
         value="1" 
         class="qty"
      >

      <input type="hidden" name="product_name" value="<?php echo $fetch_products['name']; ?>">
      <input type="hidden" name="product_price" value="<?php echo $fetch_products['price']; ?>">
      <input type="hidden" name="product_image" value="<?php echo $fetch_products['image']; ?>">
      <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">

      <input type="submit" value="add to cart" name="add_to_cart" class="btn">
   </form>

   <?php
      }
   }else{
      echo '<p class="empty">no products added yet!</p>';
   }
   ?>

   </div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>