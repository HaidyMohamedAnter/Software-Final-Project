<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit();
}


if(isset($_POST['add_product'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $price = $_POST['price'];
   $quantity = max(0, (int) $_POST['quantity']);
   $image = $_FILES['image']['name'];
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'uploaded_img/'.$image;

   $check = mysqli_query($conn, "SELECT * FROM products WHERE name = '$name'");

   if(mysqli_num_rows($check) > 0){
      $message[] = 'Product already exists!';
   }else{

      mysqli_query($conn, "INSERT INTO products(name, price, quantity, image)
      VALUES('$name','$price','$quantity','$image')");

      move_uploaded_file($image_tmp_name, $image_folder);

      header('location:admin_products.php');
      exit();
   }
}


if(isset($_GET['delete'])){
   $delete_id = (int)$_GET['delete'];

   $get_img = mysqli_query($conn, "SELECT image FROM products WHERE id = '$delete_id'");
   $img = mysqli_fetch_assoc($get_img);

   if(file_exists('uploaded_img/'.$img['image'])){
      unlink('uploaded_img/'.$img['image']);
   }

   mysqli_query($conn, "DELETE FROM products WHERE id = '$delete_id'");

   header('location:admin_products.php');
   exit();
}


if(isset($_POST['update_product'])){

   $id = $_POST['update_p_id'];
   $name = $_POST['update_name'];
   $price = $_POST['update_price'];
   $quantity = max(0, (int)$_POST['update_quantity']);

   mysqli_query($conn, "UPDATE products 
   SET name='$name', price='$price', quantity='$quantity'
   WHERE id='$id'");

   // image
   if(!empty($_FILES['update_image']['name'])){

      $new_image = $_FILES['update_image']['name'];
      $tmp = $_FILES['update_image']['tmp_name'];
      $old = $_POST['update_old_image'];

      move_uploaded_file($tmp, 'uploaded_img/'.$new_image);

      if(file_exists('uploaded_img/'.$old)){
         unlink('uploaded_img/'.$old);
      }

      mysqli_query($conn, "UPDATE products SET image='$new_image' WHERE id='$id'");
   }

   header('location:admin_products.php');
   exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Admin Products</title>

   <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<section class="add-products">

   <h1 class="title">Add Product</h1>

   <form method="post" enctype="multipart/form-data">
      <input type="text" name="name" placeholder="Name" required class="box">
      <input type="number" name="price" placeholder="Price" required class="box">
      <input type="number" name="quantity" placeholder="Quantity" required class="box">
      <input type="file" name="image" required class="box">
      <input type="submit" name="add_product" value="Add Product" class="btn">
   </form>

</section>

<section class="show-products">

<div class="box-container">

<?php
$products = mysqli_query($conn, "SELECT * FROM products");

while($p = mysqli_fetch_assoc($products)){
?>

<div class="box">

   <img src="uploaded_img/<?php echo $p['image']; ?>">

   <div class="name"><?php echo $p['name']; ?></div>

   <div class="price"><?php echo $p['price']; ?> EGP</div>

   <div class="quantity" style="color: <?php echo ($p['quantity'] == 0) ? 'red' : 'green'; ?>">
      Quantity: <?php echo $p['quantity']; ?>
   </div>

   <?php if($p['quantity'] == 0){ ?>
      <div style="color:red;">Out of Stock</div>
   <?php } ?>

   <a href="?update=<?php echo $p['id']; ?>" class="option-btn">Update</a>
   <a href="?delete=<?php echo $p['id']; ?>" class="delete-btn" onclick="return confirm('Delete product?')">Delete</a>

</div>

<?php } ?>

</div>

</section>

<!-- EDIT FORM -->
<?php
if(isset($_GET['update'])){
$id = $_GET['update'];
$res = mysqli_query($conn, "SELECT * FROM products WHERE id='$id'");
$p = mysqli_fetch_assoc($res);
?>

<section class="edit-product-form">

<form method="post" enctype="multipart/form-data">

<input type="hidden" name="update_p_id" value="<?php echo $p['id']; ?>">
<input type="hidden" name="update_old_image" value="<?php echo $p['image']; ?>">

<img src="uploaded_img/<?php echo $p['image']; ?>">

<input type="text" name="update_name" value="<?php echo $p['name']; ?>" class="box">
<input type="number" name="update_price" value="<?php echo $p['price']; ?>" class="box">
<input type="number" name="update_quantity" value="<?php echo $p['quantity']; ?>" class="box">

<input type="file" name="update_image" class="box">

<input type="submit" name="update_product" value="Update" class="btn">

</form>

</section>

<?php } ?>

</body>
</html>