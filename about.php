<?php

include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>about</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>about us</h3>
   <p> <a href="home.php">home</a> / about </p>
</div>

<section class="about">

   <div class="flex">

      <div class="image">
         <img src="images/about-img.jpg" alt="">
      </div>

      <div class="content">
         <h3>why choose us?</h3>
         <p>We bring you the best of both worlds — premium fashion and affordable prices.
Our platform sources clothing from well-known and trusted brands, ensuring you get the same high quality, design, and style you love ,but at a significantly lower price.
We believe that looking good shouldn’t come at a high cost. That’s why we carefully select products that meet high standards in fabric, durability, and trend — without the premium markup.</p>
         <p>
<p>✔ Authentic styles from trusted brands</p>
<p>✔ Same quality, better prices</p>
<p>✔ Carefully curated collections</p>
<p>✔ Fashion that fits your budget</p>
<p>With us, you don’t have to compromise between quality and affordability — you get both.</p>
         <a href="contact.php" class="btn">contact us</a>
      </div>

   </div>

</section>

<section class="reviews">

   <h1 class="title">client's reviews</h1>

   <div class="box-container">

      <div class="box">
         <img src="images\pic-1.png" alt="">
         <p>ماما بتجبلي دايما من الويبسايت هنا و البرودكتس جميلة</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>MINI RODINA MOHAMED FARAG</h3>
      </div>

      <div class="box">
         <img src="images/pic-2.png" alt="">
         <p>بلبس من الويبسايت هنا من و انا صغيرة</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>LITTLE RODINA MOHAMED FARAG</h3>
      </div>

      <div class="box">
         <img src="images/pic-3.png" alt="">
         <p>b7b ashtri stock kterr mn hena bgd touhfa</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>ADULT RODINA MOHAMED FARAG</h3>
      </div>

      <div class="box">
         <img src="images/pic-4.png" alt="">
         <p>ماما بتشتريلي دايما  من هنا</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>MINI HAIDY MOHAMED ANTER</h3>
      </div>

      <div class="box">
         <img src="images/pic-5.png" alt="">
         <p>الكوالتي و الاسعار خطيرررررره</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>LITTLE HAIDY MOHAMED ANTER</h>
      </div>

      <div class="box">
         <img src="images/pic-6.png" alt="">
         <p>touhfaaaaa its my fav bgdddd </p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3>ADULT HAIDY MOHAMED ANTER</h3>
      </div>

   </div>

</section>








<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>