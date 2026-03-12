<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$download_link = get_download_link();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_download_link = trim($_POST['download_link']);
    
    $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('download_link', ?) 
                           ON DUPLICATE KEY UPDATE value = ?");
    $stmt->bind_param("ss", $new_download_link, $new_download_link);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success = "Settings updated successfully!";
        $download_link = $new_download_link;
    } else {
        $error = "Failed to update settings.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub Launcher - Admin Settings</title>
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <span>GameHub</span>Admin
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="visits.php"><i class="fas fa-users"></i> Visits</a></li>
                    <li><a href="downloads.php"><i class="fas fa-download"></i> Downloads</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Games</a></li>
                    <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <h1>Settings</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section class="admin-settings">
                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label for="download_link">Download Link</label>
                        <input type="text" id="download_link" name="download_link" 
                               value="<?php echo htmlspecialchars($download_link); ?>" required>
                        <p class="form-help">URL where the launcher executable is hosted</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>