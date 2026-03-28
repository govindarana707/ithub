<?php
require_once 'includes/session_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT HUB - Online Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-particles">
            <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
            <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
            <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
            <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-delay: 6s;"></div>
            <div class="particle" style="width: 20px; height: 20px; left: 50%; animation-delay: 8s;"></div>
            <div class="particle" style="width: 6px; height: 6px; left: 60%; animation-delay: 10s;"></div>
            <div class="particle" style="width: 14px; height: 14px; left: 70%; animation-delay: 12s;"></div>
            <div class="particle" style="width: 18px; height: 18px; left: 80%; animation-delay: 14s;"></div>
            <div class="particle" style="width: 9px; height: 9px; left: 90%; animation-delay: 16s;"></div>
        </div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">Welcome to IT HUB</h1>
                    <p class="hero-subtitle">
                        Your premier online learning platform for IT education and professional development. 
                        Learn from industry experts, enhance your skills, and advance your career.
                    </p>
                    <div class="hero-buttons d-flex gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Get Started
                            </a>
                            <a href="courses.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-book me-2"></i>Browse Courses
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-light btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                            <a href="courses.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-book me-2"></i>Browse Courses
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero1.png" alt="Online Learning" class="img-fluid rounded shadow hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section section-light">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose IT HUB?</h2>
                <p class="section-subtitle">Discover the features that make us the best choice for your IT education</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 reveal">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4>Expert Instructors</h4>
                        <p>Learn from industry professionals with years of real-world experience in IT.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal" style="animation-delay: 0.2s">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h4>Hands-on Learning</h4>
                        <p>Practice with real projects and assignments to build practical skills.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal" style="animation-delay: 0.4s">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h4>Certification</h4>
                        <p>Earn certificates upon completion to showcase your achievements.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Courses Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Popular Courses</h2>
                <p class="section-subtitle">Explore our most sought-after courses</p>
            </div>
            
            <div class="row g-4">
                <?php
                require_once 'config/config.php';
                require_once 'models/Course.php';
                
                $course = new Course();
                $popularCourses = $course->getPopularCourses(6);
                
                foreach ($popularCourses as $c):
                ?>
                    <div class="col-md-4 reveal">
                        <div class="card course-card h-100">
                            <?php if ($c['thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($c['thumbnail'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($c['title']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="<?php echo htmlspecialchars($c['title']); ?>">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($c['category_name']); ?></span>
                                    <span class="badge bg-secondary"><?php echo ucfirst($c['difficulty_level']); ?></span>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($c['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($c['description']), 0, 100); ?>...</p>
                                <div class="course-meta mt-auto">
                                    <small><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($c['instructor_name']); ?></small>
                                    <small><i class="fas fa-users me-1"></i><?php echo $c['enrollment_count']; ?> students</small>
                                </div>
                                <div class="mt-3">
                                    <a href="course-details.php?id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">View Course</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="courses.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-book me-2"></i>View All Courses
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="section section-dark">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 reveal">
                    <div class="stat-card primary">
                        <h3>1000+</h3>
                        <p>Students</p>
                    </div>
                </div>
                <div class="col-md-3 reveal" style="animation-delay: 0.1s">
                    <div class="stat-card success">
                        <h3>50+</h3>
                        <p>Courses</p>
                    </div>
                </div>
                <div class="col-md-3 reveal" style="animation-delay: 0.2s">
                    <div class="stat-card info">
                        <h3>20+</h3>
                        <p>Instructors</p>
                    </div>
                </div>
                <div class="col-md-3 reveal" style="animation-delay: 0.3s">
                    <div class="stat-card warning">
                        <h3>95%</h3>
                        <p>Success Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section section-light">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What Our Students Say</h2>
                <p class="section-subtitle">Real experiences from our learners</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 reveal">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text">"IT HUB transformed my career. The courses are comprehensive and the instructors are amazing!"</p>
                            <footer class="blockquote-footer">
                                <strong>Ram Bahadur</strong> - Web Developer
                            </footer>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 reveal" style="animation-delay: 0.1s">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text">"The hands-on projects helped me build a strong portfolio. I landed my dream job thanks to IT HUB!"</p>
                            <footer class="blockquote-footer">
                                <strong>Sita Thapa</strong> - Data Scientist
                            </footer>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 reveal" style="animation-delay: 0.2s">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text">"Excellent platform for IT learning. The content is up-to-date and relevant to industry needs."</p>
                            <footer class="blockquote-footer">
                                <strong>Bir Bikram sha</strong> - DevOps Engineer
                            </footer>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section section-dark cta-section">
        <div class="container text-center">
            <h2 class="section-title text-white">Ready to Start Your Learning Journey?</h2>
            <p class="section-subtitle text-white">Join thousands of students advancing their careers with IT HUB</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-light btn-lg btn-glass">
                    <i class="fas fa-rocket me-2"></i>Get Started Now
                </a>
            <?php else: ?>
                <a href="courses.php" class="btn btn-light btn-lg btn-glass">
                    <i class="fas fa-book me-2"></i>Explore Courses
                </a>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
