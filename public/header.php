<!DOCTYPE html>
<html lang="en">
<head>
     <base href="<?= APP_URL; ?>/">
     <title>Health Care<?= (isset($page_title) ? " - " . $page_title : ''); ?></title>

     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=Edge">
     <meta name="description" content="">
     <meta name="keywords" content="">
     <meta name="author" content="Tooplate">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
     
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="css/bootstrap.min.css">
     <link rel="stylesheet" href="css/font-awesome.min.css">
     <link rel="stylesheet" href="css/animate.css">
     <link rel="stylesheet" href="css/owl.carousel.css">
     <link rel="stylesheet" href="css/owl.theme.default.min.css">
     <link rel="stylesheet" href="css/custom.css">

     <!-- MAIN CSS -->
     <link rel="stylesheet" href="css/tooplate-style.css">

</head>
<body id="top" data-spy="scroll" data-target=".navbar-collapse" data-offset="50">
    
     <!-- PRE LOADER -->
     <section class="preloader">
          <div class="spinner">

               <span class="spinner-rotate"></span>
               
          </div>
     </section>


     <!-- HEADER -->
     <header>
          <div class="container">
               <div class="row">

                    <div class="col-md-4 col-sm-5">
                         <p>Welcome to a Professional Health Care</p>
                    </div>
                         
                    <div class="col-md-8 col-sm-7 text-align-right">
                         <span class="phone-icon"><a href="tel:+60173779880"><i class="fa fa-phone"></i> +60 17-377 9880</a></span>
                         <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> 24 Hours (Mon-Sun)</span>
                         <span class="email-icon"><a href="mailto:24035081@imail.sunway.edu.my"><i class="fa fa-envelope-o"></i> 24035081@imail.sunway.edu.my</a></span>
                    </div>

               </div>
          </div>
     </header>


     <!-- MENU -->
     <section class="navbar navbar-default navbar-static-top" role="navigation">
          <div class="container">

               <div class="navbar-header">
                    <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                    </button>

                    <!-- lOGO TEXT HERE -->
                    <a href="index.php" class="navbar-brand"><i class="fa fa-h-square"></i>ealth Center</a>
               </div>

               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="index.php" class="smoothScroll">Home</a></li>
                         <li><a href="about_us.php" class="smoothScroll">About Us</a></li>
                         <li><a href="doctors.php" class="smoothScroll">Doctors</a></li>
                         <!-- <li><a href="news-detail.html" class="smoothScroll">News</a></li>
                         <li class="appointment-btn"><a href="appointment.php">Make an appointment</a></li> -->
                         <?php if (!isset($_SESSION['user_id'])): ?>
                              <li><a href="login.php" class="smoothScroll">Login</a></li>
                              <li><a href="register.php" class="smoothScroll">Register</a></li>
                         <?php else: ?>
                              <?php
                                   $role = $_SESSION['role'] ?? 'patient';
                                   $accountLink  = match ($role) {
                                        'admin'  => 'admin/dashboard.php',
                                        'assist' => 'admin/manage-schedules.php',
                                        'doctor' => 'profile.php',
                                        default  => 'profile.php',
                                   };
                                   $accountLabel = match ($role) {
                                        'admin'  => 'Admin Dashboard',
                                        'assist' => 'Staff Portal',
                                        'doctor' => 'My Schedule',
                                        default  => 'My Profile',
                                   };
                              ?>
                              <li><a href="<?php echo $accountLink; ?>" class="smoothScroll"><?php echo $accountLabel; ?></a></li>
                              <li><a href="logout.php" class="smoothScroll">Log out</a></li>
                         <?php endif; ?>

                         <!-- <?php /* $link = !isset($_SESSION['user_id']) ? 'login' : 'profile'; ?> 
                         <li><a href="<?php echo $link . '.php' ?>" class="smoothScroll"><?php echo ucwords($link) */?></a></li> -->

                         <!-- <li><a href="#top" class="smoothScroll">Home</a></li>
                         <li><a href="#about" class="smoothScroll">About Us</a></li>
                         <li><a href="#team" class="smoothScroll">Doctors</a></li>
                         <li><a href="#news" class="smoothScroll">News</a></li>
                         <li><a href="#google-map" class="smoothScroll">Contact</a></li>
                         <li class="appointment-btn"><a href="#appointment">Make an appointment</a></li> -->
                    </ul>
               </div>

          </div>
     </section>