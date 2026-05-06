<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit();
}

// 1. Update Cart Quantity
if(isset($_POST['update_cart'])){
   $cart_id = $_POST['cart_id'];
   $new_quantity = (int)$_POST['cart_quantity'];

   // Check warehouse stock before allowing update
   $get_cart = mysqli_query($conn, "SELECT product_id FROM cart WHERE id = '$cart_id'");
   $fetch_cart = mysqli_fetch_assoc($get_cart);
   $product_id = $fetch_cart['product_id'];

   $get_product = mysqli_query($conn, "SELECT quantity FROM products WHERE id = '$product_id'");
   $fetch_product = mysqli_fetch_assoc($get_product);
   $warehouse_stock = (int)$fetch_product['quantity'];

   if($new_quantity > $warehouse_stock){
      $message[] = 'Cannot add more than warehouse stock!';
   } else {
      mysqli_query($conn, "UPDATE cart SET quantity = '$new_quantity' WHERE id = '$cart_id'");
      $message[] = 'Cart quantity updated!';
   }
}

// 2. Delete Single Item
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   // Notice: We REMOVED the code that adds stock back to the products table.
   mysqli_query($conn, "DELETE FROM cart WHERE id = '$delete_id'");
   header('location:cart.php');
   exit();
}

// 3. Delete All Items
if(isset($_GET['delete_all'])){
   // Notice: We REMOVED the loop that adds stock back to the products table.
   mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
   header('location:cart.php');
   exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>cart</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
         $select_cart = mysqli_query($conn, "
            SELECT c.*, p.quantity AS warehouse_stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = '$user_id'
         ") or die('query failed');

         if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){   
      ?>
      <div class="box">
         <a href="cart.php?delete=<?php echo $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('delete this from cart?');"></a>
         <img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" alt="">
         <div class="name"><?php echo $fetch_cart['name']; ?></div>
         <div class="price"><?php echo $fetch_cart['price']; ?> EGP</div>
         
         <form action="" method="post">
            <input type="hidden" name="cart_id" value="<?php echo $fetch_cart['id']; ?>">
            <!-- Scenario B: The max is the total warehouse stock -->
            <input type="number" min="1" max="<?php echo $fetch_cart['warehouse_stock']; ?>" name="cart_quantity" value="<?php echo $fetch_cart['quantity']; ?>">
            <input type="submit" name="update_cart" value="update" class="option-btn">
         </form>
         
         <div class="sub-total"> sub total : <span><?php echo $sub_total = ($fetch_cart['quantity'] * $fetch_cart['price']); ?> EGP</span> </div>
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
      <p>grand total : <span><?php echo $grand_total; ?> EGP</span></p>
      <div class="flex">
         <a href="shop.php" class="option-btn">continue shopping</a>
         <a href="checkout.php" class="btn <?php echo ($grand_total > 0)?'':'disabled'; ?>">proceed to checkout</a>
      </div>
   </div>

</section>

<?php include 'footer.php'; ?>
<script src="js/script.js"></script>

</body>
</html>