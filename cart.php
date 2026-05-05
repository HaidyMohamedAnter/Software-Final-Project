<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['update_cart'])){
   $cart_id = $_POST['cart_id'];
   $new_quantity = $_POST['cart_quantity'];

   
   $get_cart = mysqli_query($conn, "SELECT * FROM cart WHERE id = '$cart_id'");
   $fetch_cart = mysqli_fetch_assoc($get_cart);

   $product_id = $fetch_cart['product_id'];
   $old_quantity = $fetch_cart['quantity'];


   $difference = $new_quantity - $old_quantity;

   
   $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'");
   $fetch_product = mysqli_fetch_assoc($get_product);

   $current_quantity = $fetch_product['quantity'];

   
   $updated_quantity = $current_quantity - $difference;

   mysqli_query($conn, "UPDATE products SET quantity = '$updated_quantity' WHERE id = '$product_id'");
   mysqli_query($conn, "UPDATE cart SET quantity = '$new_quantity' WHERE id = '$cart_id'");

   $message[] = 'cart quantity updated!';
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];

   $get_cart = mysqli_query($conn, "SELECT * FROM cart WHERE id = '$delete_id'");

   if($fetch_cart = mysqli_fetch_assoc($get_cart)){

      $product_id = $fetch_cart['product_id'];
      $cart_quantity = $fetch_cart['quantity'];

      $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'");
      $fetch_product = mysqli_fetch_assoc($get_product);

      $new_quantity = $fetch_product['quantity'] + $cart_quantity;

      mysqli_query($conn, "UPDATE products SET quantity = '$new_quantity' WHERE id = '$product_id'");
   }

   mysqli_query($conn, "DELETE FROM cart WHERE id = '$delete_id'");

   header('location:cart.php');
   exit();
}

if(isset($_GET['delete_all'])){

   $get_all = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'");

   while($item = mysqli_fetch_assoc($get_all)){

      $product_id = $item['product_id'];
      $cart_quantity = $item['quantity'];

      $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'");
      $fetch_product = mysqli_fetch_assoc($get_product);

      $new_quantity = $fetch_product['quantity'] + $cart_quantity;

      mysqli_query($conn, "UPDATE products SET quantity = '$new_quantity' WHERE id = '$product_id'");
   }

mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
   header('location:cart.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>cart</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>shopping cart</h3>
   <p> <a href="home.php">home</a> / cart </p>
</div>

<section class="shopping-cart">

   <h1 class="title">products added</h1>

   <div class="box-container">
      <?php
         $grand_total = 0;
         $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
         if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){   
      ?>
      <div class="box">
         <a href="cart.php?delete=<?php echo $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('delete this from cart?');"></a>
         <img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" alt="">
         <div class="name"><?php echo $fetch_cart['name']; ?></div>
         <div class="price"><?php echo $fetch_cart['price']; ?>EGP</div>
         <form action="" method="post">
            <input type="hidden" name="cart_id" value="<?php echo $fetch_cart['id']; ?>">
            <input type="number" min="1" name="cart_quantity" value="<?php echo $fetch_cart['quantity']; ?>">
            <input type="submit" name="update_cart" value="update" class="option-btn">
         </form>
         <div class="sub-total"> sub total : <span><?php echo $sub_total = ($fetch_cart['quantity'] * $fetch_cart['price']); ?>EGP</span> </div>
      </div>
      <?php
      $grand_total += $sub_total;
         }
      }else{
         echo '<p class="empty">your cart is empty</p>';
      }
      ?>
   </div>

   <div style="margin-top: 2rem; text-align:center;">
      <a href="cart.php?delete_all" class="delete-btn <?php echo ($grand_total > 1)?'':'disabled'; ?>" onclick="return confirm('delete all from cart?');">delete all</a>
   </div>

   <div class="cart-total">
      <p>grand total : <span><?php echo $grand_total; ?>EGP</span></p>
      <div class="flex">
         <a href="shop.php" class="option-btn">continue shopping</a>
         <a href="checkout.php" class="btn <?php echo ($grand_total > 1)?'':'disabled'; ?>">proceed to checkout</a>
      </div>
   </div>

</section>








<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>