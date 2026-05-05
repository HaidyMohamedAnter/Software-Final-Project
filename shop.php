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

   
   $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'") or die('query failed');
   $fetch_product = mysqli_fetch_assoc($get_product);

   $available_quantity = (int)$fetch_product['quantity'];

   if($product_quantity > $available_quantity){
      $message[] = 'Sorry, not enough stock available!';
   }else{

      
      $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE product_id = '$product_id' AND user_id = '$user_id'");

      if(mysqli_num_rows($check_cart) > 0){

         $cart_item = mysqli_fetch_assoc($check_cart);
         $new_cart_quantity = $cart_item['quantity'] + $product_quantity;

      
         mysqli_query($conn, "UPDATE cart 
         SET quantity = '$new_cart_quantity' 
         WHERE id = '".$cart_item['id']."'");

      }else{

         
         mysqli_query($conn, "INSERT INTO cart(user_id, product_id, name, price, quantity, image)
         VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')");
      }

      
      mysqli_query($conn, "
         UPDATE products 
         SET quantity = quantity - $product_quantity 
         WHERE id = '$product_id'
      ");

      $message[] = 'Cart updated successfully!';
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
   $select_products = mysqli_query($conn, "SELECT * FROM products") or die('query failed');

   if(mysqli_num_rows($select_products) > 0){
      while($fetch_products = mysqli_fetch_assoc($select_products)){
   ?>

   <form action="" method="post" class="box">
      <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="">
      
      <div class="name"><?php echo $fetch_products['name']; ?></div>
      <div class="price"><?php echo $fetch_products['price']; ?> EGP</div>

      
      <div class="stock" style="color: <?php echo ($fetch_products['quantity']==0)?'red':'green'; ?>">
         Available: <?php echo $fetch_products['quantity']; ?>
      </div>

      <?php if($fetch_products['quantity'] == 0){ ?>

         <div style="color:red;">Out of Stock</div>
         <button disabled class="btn">Out of stock</button>

      <?php } else { ?>

         <input 
            type="number" 
            min="1" 
            max="<?php echo $fetch_products['quantity']; ?>" 
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
   }else{
      echo '<p class="empty">no products added yet!</p>';
   }
   ?>

   </div>

</section>








<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>