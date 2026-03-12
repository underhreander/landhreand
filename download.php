<?php
session_start();
require_once 'admin/db_connect.php';

// Логирование посещения
log_visit();

// Получение ссылки для скачивания из базы данных
$download_link = get_download_link();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download GameHub Launcher</title>
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <span>GameHub</span>Launcher
            </div>
            <nav class="nav">
                <ul>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="index.php#games">Games</a></li>
                    <li><a href="download.php" class="active">Download</a></li>
                    <li><a href="index.php#support">Support</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="download-page">
        <div class="container">
            <div class="download-steps">
                <div class="step active">
                    <span>1</span>
                    <p>Generate Trial Code</p>
                </div>
                <div class="step">
                    <span>2</span>
                    <p>Download Launcher</p>
                </div>
                <div class="step">
                    <span>3</span>
                    <p>Install & Enjoy</p>
                </div>
            </div>

            <div class="download-content">
                <h1 class="animate__animated animate__fadeIn">Get GameHub Launcher</h1>
                <p class="animate__animated animate__fadeIn">Complete the form below to get your 7-day free trial code and download the launcher</p>

                <div class="code-generation animate__animated animate__fadeIn">
                    <form id="trialForm">
                        <div class="form-group">
                            <input type="email" id="userEmail" placeholder="Enter your email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Trial Code</button>
                    </form>
                </div>

                <div id="codeResult" class="hidden">
                    <div class="trial-code-box">
                        <h3>Your 7-Day Trial Code</h3>
                        <div class="code-display" id="trialCode"></div>
                        <p class="code-valid">Valid for 7 days after activation</p>
                    </div>

                    <div class="download-box hidden" id="downloadBox">
                        <h3>Download GameHub Launcher</h3>
                        <a href="<?php echo htmlspecialchars($download_link); ?>" class="btn btn-download" download id="downloadBtn">
                            <i class="fas fa-download"></i> Download Now
                        </a>
                        <p class="file-info">Version 1.0.0 | 45 MB | Windows 10/11</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <!-- Футер как на главной странице -->
    </footer>

    <script src="js/download.js"></script>
</body>
</html>