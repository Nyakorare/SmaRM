<?php
require './php/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100;300;400;600;700&display=swap" rel="stylesheet">
        <link href="./css/bootstrap.min.css" rel="stylesheet">
        <link href="./css/bootstrap-icons.css" rel="stylesheet">
        <link href="./css/owl.carousel.min.css" rel="stylesheet">
        <link href="./css/owl.theme.default.min.css" rel="stylesheet">
        <link href="./css/styles.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="./images/SmaRM-Logo.png" class="img-fluid logo-image">
                    <div class="d-flex flex-column">
                        <strong class="logo-text">SmaRM</strong>
                        <small class="logo-slogan">Smart Room Management</small>
                    </div>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav align-items-center ms-lg-5">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">Homepage</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./pages/landing-pages/about.html">About SmaRM</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarLightDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>

                            <ul class="dropdown-menu dropdown-menu-light" aria-labelledby="navbarLightDropdownMenuLink">
                                <li><a class="dropdown-item" href="./smarm-system.html">SmaRM System Procurement</a></li>

                                <li><a class="dropdown-item" href="./smarm-details.html">SmaRM Details</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./pages/landing-pages/contact.html">Contact</a>
                        </li>
                        <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item ms-lg-auto">
                            <div class="dropdown">
                                <button class="nav-link custom-btn btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo $_SESSION['role'] === 'admin' ? './pages/admin/admin.php' : './pages/teacher/teacher.php'; ?>">Dashboard</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="./php/logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </li>
                        <?php else: ?>
                        <li class="nav-item ms-lg-auto">
                            <a class="nav-link" href="./pages/landing-pages/register-page.php">Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link custom-btn btn" href="./pages/landing-pages/login-page.php">Login</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            <section class="hero-section d-flex justify-content-center align-items-center">
                <div class="section-overlay"></div>
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6 col-12 mb-5 mb-lg-0">
                            <div class="hero-section-text mt-5">
                                <h6 class="text-white">Are you looking if you have your assigned room for today?</h6>
                                <h1 class="hero-title text-white mt-4 mb-4">Online Real Time. <br> Best Room Management</h1>
                                <a href="#categories-section" class="custom-btn custom-border-btn btn">Browse Features</a>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                        <form class="custom-form hero-form" id="checkRoomForm" method="post" role="form">
    <h3 class="text-white mb-3">Check Room</h3>

    <div class="row">
        <!-- Room Selection -->
        <div class="col-lg-6 col-md-6 col-12">
            <div class="input-group">
                <span class="input-group-text" id="basic-addon1"><i class="bi-building custom-icon"></i></span>
                <select name="room" id="room" class="form-control" required>
                    <option value="">Select Room</option>
                    <option value="1">Room 1</option>
                    <option value="2">Room 2</option>
                    <option value="3">Room 3</option>
                    <option value="4">Room 4</option>
                    <option value="5">Room 5</option>
                </select>
            </div>
        </div>

        <!-- Date Picker -->
        <div class="col-lg-6 col-md-6 col-12">
            <div class="input-group">
                <span class="input-group-text" id="basic-addon2"><i class="bi-calendar custom-icon"></i></span>
                <input type="date" name="date" id="date" class="form-control" required>
            </div>
        </div>

        <!-- Time Picker -->
        <div class="col-lg-6 col-md-6 col-12">
            <div class="input-group">
                <span class="input-group-text" id="basic-addon3"><i class="bi-clock custom-icon"></i></span>
                <input type="time" name="time" id="time" class="form-control" required>
            </div>
        </div>

         <!-- Submit Button -->
         <div class="col-lg-12 col-12">
            <button type="button" class="form-control" id="checkButton">
                Check
            </button>
        </div>
    </div>
</form>
                        </div>
                    </div>
                </div>
            </section>
<!-- Modal -->
<div class="modal fade" id="roomAvailabilityModal" tabindex="-1" aria-labelledby="roomAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomAvailabilityModalLabel">Room Availability</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

            <section class="categories-section section-padding" id="categories-section">
                <div class="container">
                    <div class="row justify-content-center align-items-center">

                        <div class="col-lg-12 col-12 text-center">
                            <h2 class="mb-5">SmaRM <span>Features</span></h2>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-window"></i>
                                
                                    <small class="categories-block-title">Web Application</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">1</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-clock"></i>
                                
                                    <small class="categories-block-title">Real Time</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">2</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-cloud"></i>
                                
                                    <small class="categories-block-title">Drag and Drop Management</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">3</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-globe"></i>
                                
                                    <small class="categories-block-title">Online Accesibility</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">4</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-people"></i>
                                
                                    <small class="categories-block-title">Hierarchical Accounts</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">5</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section class="about-section">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-3 col-12">
                            <div class="about-image-wrap custom-border-radius-start">
                                <img src="./images/me.jpg" class="about-image custom-border-radius-start img-fluid" alt="">

                                <div class="about-info">
                                    <h4 class="text-white mb-0 me-2">Glenn R. Galbadores I</h4>

                                    <p class="text-white mb-0">Team Leader</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="custom-text-block">
                                <h2 class="text-white mb-2">Introduction to SmaRM</h2>

                                <p class="text-white">SmaRM is a website application for Room Management.<br>This system was initially created for TUP-M COS.</p>

                                <div class="custom-border-btn-wrap d-flex align-items-center mt-5">
                                    <a href="./pages/landing-pages/about.html" class="custom-btn custom-border-btn btn me-4">Get to know us</a>

                                    <a href="./pages/landing-pages/smarm-details.html" class="custom-link smoothscroll">Explore SmaRM Framework</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">
                            <div class="instagram-block">
                                <img src="./images/circle.gif" class="about-image custom-border-radius-end img-fluid" alt="">

                                <div class="instagram-block-text">
                                    <a href="https://www.instagram.com/guren_chan/" class="custom-btn btn" target="_blank">
                                        <i class="bi-instagram"></i>
                                        @ItsNotMe_Glenn
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <br>
            <section>
                <div class="container">
                    <div class="row">

                        <div class="col-lg-6 col-12">
                            <div class="custom-text-block custom-border-radius-start">
                                <h2 class="text-white mb-3">SmaRM helps Teachers & Students monitor their classrooms in real-time.</h2>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="video-thumb">
                                <img src="images/blue-bg.gif" class="about-image custom-border-radius-end img-fluid" alt="">

                                <div class="video-info">
                                    <img src="./images/smarm-logo.png">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section class="reviews-section section-padding">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-12 col-12">
                            <h2 class="text-center mb-5">Project Developers</h2>

                            <div class="owl-carousel owl-theme reviews-carousel">
                                <div class="reviews-thumb">
                                
                                    <div class="reviews-info d-flex align-items-center">
                                        <img src="./images/glenn.jpg" class="avatar-image img-fluid" alt="">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap w-100 ms-3">
                                            <p class="mb-0">
                                                <strong>Glenn G</strong>
                                                <small>Team Leader, Project Manager & Head Developer</small>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="reviews-body">
                                        <img src="./images/quote.png" class="quote-icon img-fluid" alt="">

                                        <h4 class="reviews-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</h4>
                                    </div>
                                </div>

                                <div class="reviews-thumb">
                                    <div class="reviews-info d-flex align-items-center">
                                        <img src="images/kurt.jpg" class="avatar-image img-fluid" alt="">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap w-100 ms-3">
                                            <p class="mb-0">
                                                <strong>Kurt B</strong>
                                                <small>Developer</small>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="reviews-body">
                                        <img src="images/quote.png" class="quote-icon img-fluid" alt="">

                                        <h4 class="reviews-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</h4>
                                    </div>
                                </div>

                                <div class="reviews-thumb">

                                    <div class="reviews-info d-flex align-items-center">
                                        <img src="images/justine.jpg" class="avatar-image img-fluid" alt="">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap w-100 ms-3">
                                            <p class="mb-0">
                                                <strong>Justine M</strong>
                                                <small>Developer</small>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="reviews-body">
                                        <img src="images/quote.png" class="quote-icon img-fluid" alt="">

                                        <h4 class="reviews-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</h4>
                                    </div>
                                </div>

                                <div class="reviews-thumb">
                                    <div class="reviews-info d-flex align-items-center">
                                        <img src="images/walter.jpg" class="avatar-image img-fluid" alt="">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap w-100 ms-3">
                                            <p class="mb-0">
                                                <strong>John Walter M</strong>
                                                <small>Developer & Lead Design</small>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="reviews-body">
                                        <img src="images/quote.png" class="quote-icon img-fluid" alt="">

                                        <h4 class="reviews-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</h4>
                                    </div>
                                </div>

                                <div class="reviews-thumb">
                                    <div class="reviews-info d-flex align-items-center">
                                        <img src="images/ken.jpg" class="avatar-image img-fluid" alt="">

                                        <div class="d-flex align-items-center justify-content-between flex-wrap w-100 ms-3">
                                            <p class="mb-0">
                                                <strong>Ken Zedrick M</strong>
                                                <small>Developer & QA</small>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="reviews-body">
                                        <img src="images/quote.png" class="quote-icon img-fluid" alt="">

                                        <h4 class="reviews-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta-section">
                <div class="section-overlay"></div>

                <div class="container">
                    <div class="row">

                        <div class="col-lg-6 col-10">
                            <h2 class="text-white mb-2">SmaRM's Purpose</h2>

                            <p class="text-white">SmaRM aims to simplify room assignments, ensuring efficient scheduling and real-time availability monitoring.</p>
                        </div>

                        <div class="col-lg-4 col-12 ms-auto">
                            <div class="custom-border-btn-wrap d-flex align-items-center mt-lg-4 mt-2">
                                <a href="./pages/landing-pages/register-page.php" class="custom-btn custom-border-btn btn me-4">Create an account</a>

                                <a href="./pages/landing-pages/smarm-system.html" class="custom-link">Acquire SmaRM</a>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="container">
                <div class="row">

                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <div class="d-flex align-items-center mb-4">
                            <img src="images/SmaRM-Logo.png" class="img-fluid logo-image">

                            <div class="d-flex flex-column">
                                <strong class="logo-text">SmaRM</strong>
                                <small class="logo-slogan">Smart Room Management</small>
                            </div>
                        </div>  

                        <p class="mb-2">
                            <i class="custom-icon bi-globe me-1"></i>

                            <a href="#" class="site-footer-link">
                                www.(hindi pa deployed sa web).com
                            </a>
                        </p>

                        <p class="mb-2">
                            <i class="custom-icon bi-telephone me-1"></i>

                            <a href="tel: 305-240-9671" class="site-footer-link">
                                +63 915 0813 134
                            </a>
                        </p>

                        <p>
                            <i class="custom-icon bi-envelope me-1"></i>

                            <a href="mailto:info@yourgmail.com" class="site-footer-link">
                                glenn.galbadores@tup.edu.ph
                            </a>
                        </p>

                    </div>

                    <div class="col-lg-2 col-md-3 col-6 ms-lg-auto">
                        <h6 class="site-footer-title">Company</h6>

                        <ul class="footer-menu">
                            <li class="footer-menu-item"><a href="./pages/landing-pages/about.html" class="footer-menu-link">About</a></li>
                            <li class="footer-menu-item"><a href="./pages/landing-pages/contact.html" class="footer-menu-link">Contact</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-2 col-md-3 col-6">
                        <h6 class="site-footer-title">Resources</h6>

                        <ul class="footer-menu">
                            <li class="footer-menu-item"><a href="./pages/landing-pages/smarm-details.html" class="footer-menu-link">Guide</a></li>

                            <li class="footer-menu-item"><a href="#" class="footer-menu-link">How it works</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-8 col-12 mt-3 mt-lg-0">
                        <h6 class="site-footer-title">Newsletter</h6>

                        <form class="custom-form newsletter-form" action="#" method="post" role="form">
                            <h6 class="site-footer-title">Get notified about SmaRM</h6>

                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1"><i class="bi-person"></i></span>

                                <input type="text" name="newsletter-name" id="newsletter-name" class="form-control" placeholder="yourname@gmail.com" required>

                                <button type="submit" class="form-control">
                                    <i class="bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <div class="site-footer-bottom">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-4 col-12 d-flex align-items-center">
                            <p class="copyright-text">Copyright © SmaRM 2024</p>

                            <ul class="footer-menu d-flex">
                                <li class="footer-menu-item"><a href="./pages/landing-pages/terms.html" class="footer-menu-link">Privacy Policy</a></li>

                                <li class="footer-menu-item"><a href="#" class="footer-menu-link">Terms</a></li>
                            </ul>
                        </div>
                        <a class="back-top-icon bi-arrow-up smoothscroll d-flex justify-content-center align-items-center" href="#top"></a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
        <script src="./js/scripts.js"></script>
        <script src="./js/jquery.min.js"></script>
        <script src="./js/bootstrap.min.js"></script>
        <script src="./js/owl.carousel.min.js"></script>
        <script src="./js/counter.js"></script>
        <script src="./js/custom.js"></script>
        <script>
    document.getElementById('checkButton').addEventListener('click', function() {
        let room = document.getElementById('room').value;
        let time = document.getElementById('time').value;

        if (!room || !time) {
            alert('Please select both a room and a time.');
            return;
        }

        // Perform AJAX request to check room availability
        let xhr = new XMLHttpRequest();
        xhr.open('POST', './php/check_room.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                let response = JSON.parse(xhr.responseText);
                let messageElement = document.getElementById('modalMessage');
                if (response.available) {
                    messageElement.textContent = 'The room is free at this time.';
                } else {
                    messageElement.innerHTML = `Room is already booked by <strong>${response.scheduler_name}</strong> from <strong>${response.start_time}</strong> to <strong>${response.end_time}</strong>.`;
                }
                // Show the modal
                let modal = new bootstrap.Modal(document.getElementById('roomAvailabilityModal'));
                modal.show();
            }
        };
        xhr.send('room=' + encodeURIComponent(room) + '&time=' + encodeURIComponent(time));
    });
</script>
</html>