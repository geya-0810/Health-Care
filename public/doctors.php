<?php 
// public/doctors.php
require_once __DIR__ . '/../src/config/config.php';

$db = Database::getConnection();
$doctors = Doctor::all($db);

require_once __DIR__ . '/header.php'; 
?>

     <!-- TEAM -->
     <section id="team" data-stellar-background-ratio="1">
          <div class="container">
               <div class="row">

                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.1s">Our Doctors</h2>
                         </div>
                    </div>

                    <div class="clearfix"></div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.2s" style="background-image: url('images/team-image1.jpg');">
                              <!-- <img src="images/team-image1.jpg" class="img-responsive" alt=""> -->

                                   <div class="team-info">
                                        <h3>Nate Baston</h3>
                                        <p>General Principal</p>
                                        <div class="team-contact-info">
                                             <p><i class="fa fa-phone"></i> 010-020-0120</p>
                                             <p><i class="fa fa-envelope-o"></i> <a href="#">general@company.com</a></p>
                                        </div>
                                        <button class="doctor-btn"><a href="appointment.php?doctor_id=1">Make an appointment</a></button>

                                        <ul class="social-icon">
                                             <li><a href="#" class="fa fa-linkedin-square"></a></li>
                                             <li><a href="#" class="fa fa-envelope-o"></a></li>
                                        </ul>
                                   </div>

                         </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.4s" style="background-image: url('images/team-image2.jpg');">
                              <!-- <img src="images/team-image2.jpg" class="img-responsive" alt=""> -->

                                   <div class="team-info">
                                        <h3>Jason Stewart</h3>
                                        <p>Pregnancy</p>
                                        <div class="team-contact-info">
                                             <p><i class="fa fa-phone"></i> 010-070-0170</p>
                                             <p><i class="fa fa-envelope-o"></i> <a href="#">pregnancy@company.com</a></p>
                                        </div>
                                        <button class="doctor-btn"><a href="appointment.php?doctor_id=1">Make an appointment</a></button>
                                        <ul class="social-icon">
                                             <li><a href="#" class="fa fa-facebook-square"></a></li>
                                             <li><a href="#" class="fa fa-envelope-o"></a></li>
                                             <li><a href="#" class="fa fa-flickr"></a></li>
                                        </ul>
                                   </div>

                         </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.6s" style="background-image: url('images/team-image3.jpg');">
                              <!-- <img src="images/team-image3.jpg" class="img-responsive" alt=""> -->

                                   <div class="team-info">
                                        <h3>Miasha Nakahara</h3>
                                        <p>Cardiology</p>
                                        <div class="team-contact-info">
                                             <p><i class="fa fa-phone"></i> 010-040-0140</p>
                                             <p><i class="fa fa-envelope-o"></i> <a href="#">cardio@company.com</a></p>
                                        </div>
                                        <button class="doctor-btn"><a href="appointment.php?doctor_id=1">Make an appointment</a></button>
                                        <ul class="social-icon">
                                             <li><a href="#" class="fa fa-twitter"></a></li>
                                             <li><a href="#" class="fa fa-envelope-o"></a></li>
                                        </ul>
                                   </div>

                         </div>
                    </div>

                    <?php 
                    if (!empty($doctors)): 
                         $counter = 1;
                         
                         foreach ($doctors as $index => $doctor): 
                              $delay = 0.2 + (($counter % 3) * 0.2);
                              if ($delay == 0.2) $delay = 0.8;   
                              
                              $profile_image = !empty($doctor['avatar_url']) 
                                   ? htmlspecialchars($doctor['avatar_url']) 
                                   : 'images/team-image1.jpg';
                    ?>
                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="<?= $delay ?>s" style="background-image: url('<?= $profile_image ?>');">
                              <!-- <img src="<?//= $profile_image ?>" class="img-responsive" alt="<?//= htmlspecialchars($doctor['full_name']) ?>"> -->

                                   <div class="team-info">
                                        <h3><?= htmlspecialchars($doctor['full_name']) ?></h3>
                                        <p><?= htmlspecialchars($doctor['specialty']) ?></p>
                                        <div class="team-contact-info">
                                             <p><i class="fa fa-phone"></i> <?= htmlspecialchars($doctor['phone'] ?? 'N/A') ?></p>
                                             <p><i class="fa fa-envelope-o"></i> <a href="mailto:<?= htmlspecialchars($doctor['email'] ?? '') ?>"><?= htmlspecialchars($doctor['email'] ?? 'N/A') ?></a></p>
                                        </div>
                                        <button class="doctor-btn"><a href="appointment.php?doctor_id=<?= $doctor['doctor_id'] ?>">Make an appointment</a></button>
                                        <ul class="social-icon">
                                             <li><a href="#" class="fa fa-envelope-o"></a></li>
                                        </ul>
                                   </div>

                         </div>
                    </div>
                    <?php                               
                              // if ($counter % 3 == 0) {
                              //      echo '<div class="clearfix visible-md visible-lg"></div>';
                              // }
                              
                              // if ($counter % 2 == 0) {
                              //      echo '<div class="clearfix visible-sm"></div>';
                              // }

                              $counter++;
                         endforeach; 
                    else: 
                    ?>
                         <div class="col-md-12">
                              <p>No doctors available at the moment.</p>
                         </div>
                    <?php endif; ?>
                    
                    
               </div>
          </div>
     </section>
<?php require_once __DIR__ . '/footer.php'; ?>