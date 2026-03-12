<?php
session_start();
require_once 'db_connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$error = $success = '';

// Получаем все игры (не только активные)
function get_all_games() {
    global $conn;
    
    $games = [];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM games ORDER BY display_order, name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error getting all games: " . $e->getMessage());
    }
    
    return $games;
}

$games = get_all_games();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        if (isset($_POST['add_game'])) {
            $name = trim($_POST['name']);
            $image = '';
            
            // Обработка загрузки изображения
            if (isset($_FILES['game_image']) && $_FILES['game_image']['error'] == 0) {
                $allowed = ['png', 'jpg', 'jpeg', 'webp'];
                $filename = $_FILES['game_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $newname = 'game_' . time() . '.' . $filetype;
                    $target = '../images/games/' . $newname;
                    
                    if (move_uploaded_file($_FILES['game_image']['tmp_name'], $target)) {
                        $image = 'images/games/' . $newname;
                    } else {
                        $error = "Failed to upload image";
                    }
                } else {
                    $error = "Invalid image format. Use PNG, JPG, JPEG or WEBP";
                }
            } elseif (!empty(trim($_POST['image_url']))) {
                $image = trim($_POST['image_url']);
            }
            
            if (empty($name)) {
                $error = "Game name is required";
            } elseif (empty($error)) {
                if (add_game($name, $image)) {
                    $success = "Game added successfully!";
                    $games = get_all_games();
                } else {
                    $error = "Error adding game";
                }
            }
        } elseif (isset($_POST['delete_game'])) {
            $game_id = (int)$_POST['game_id'];
            
            if (delete_game($game_id)) {
                $success = "Game deleted successfully!";
                $games = get_all_games();
            } else {
                $error = "Error deleting game";
            }
        } elseif (isset($_POST['update_game'])) {
            $game_id = (int)$_POST['game_id'];
            $name = trim($_POST['name']);
            $display_order = (int)$_POST['display_order'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Получаем текущее изображение
            $current_game = get_game_by_id($game_id);
            $image = $current_game['image'];
            
            // Проверяем новое изображение
            if (isset($_FILES['game_image']) && $_FILES['game_image']['error'] == 0) {
                $allowed = ['png', 'jpg', 'jpeg', 'webp'];
                $filename = $_FILES['game_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $newname = 'game_' . time() . '.' . $filetype;
                    $target = '../images/games/' . $newname;
                    
                    if (move_uploaded_file($_FILES['game_image']['tmp_name'], $target)) {
                        // Удаляем старое изображение если оно было загружено на сервер
                        if (strpos($current_game['image'], 'images/games/') === 0) {
                            @unlink('../' . $current_game['image']);
                        }
                        $image = 'images/games/' . $newname;
                    }
                }
            } elseif (!empty(trim($_POST['image_url']))) {
                $image = trim($_POST['image_url']);
            }
            
            if (update_game($game_id, $name, $image, $display_order, $is_active)) {
                $success = "Game updated successfully!";
                $games = get_all_games();
            } else {
                $error = "Error updating game";
            }
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub Launcher - Manage Games</title>
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
                    <li class="active"><a href="games.php"><i class="fas fa-gamepad"></i> Games</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content admin-games">
            <header class="admin-header">
                <h1>Manage Games</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section class="admin-actions">
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <div class="add-game-form">
                    <h2>Add New Game</h2>
                    <form id="game-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="game_name">Game Name *</label>
                            <input type="text" id="game_name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="game_image">Game Image</label>
                            <input type="file" id="game_image" name="game_image" accept=".png,.jpg,.jpeg,.webp">
                            <small>Upload image file (PNG, JPG, JPEG, WEBP) or use URL below</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url">Or Image URL</label>
                            <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.png">
                        </div>
                        
                        <div class="image-preview-container">
                            <img id="image-preview" style="display: none; max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                        
                        <button type="submit" name="add_game" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Game
                        </button>
                    </form>
                </div>
            </section>

            <section class="games-grid">
                <h2>Current Games (<?php echo count($games); ?>)</h2>
                
                <?php if (empty($games)): ?>
                <div class="no-games">
                    <i class="fas fa-gamepad"></i>
                    <p>No games added yet. Add your first game above!</p>
                </div>
                <?php else: ?>
                <div class="games-container">
                    <?php foreach ($games as $game): ?>
                    <div class="game-card" data-game-id="<?php echo $game['id']; ?>">
                        <div class="game-image">
                            <?php if (!empty($game['image'])): ?>
                            <img src="<?php 
                                // Проверяем, является ли изображение внешней ссылкой или локальным файлом
                                if (filter_var($game['image'], FILTER_VALIDATE_URL)) {
                                    echo htmlspecialchars($game['image']);
                                } else {
                                    echo '../' . htmlspecialchars($game['image']);
                                }
                            ?>" 
                                 alt="<?php echo htmlspecialchars($game['name']); ?>"
                                 onerror="this.src='../images/placeholder-game.png'">
                            <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <span>No Image</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="game-info">
                            <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                            <div class="game-meta">
                                <span class="game-status <?php echo $game['is_active'] ? 'active' : 'inactive'; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo $game['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <span class="game-order">Order: <?php echo $game['display_order']; ?></span>
                            </div>
                            <div class="game-dates">
                                <?php if (isset($game['created_at']) && !empty($game['created_at'])): ?>
                                <small>Added: <?php echo date('M j, Y', strtotime($game['created_at'])); ?></small>
                                <?php else: ?>
                                <small>Added: Date not available</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="game-actions">
                            <label class="toggle-switch">
                                <input type="checkbox" class="toggle-game-active" 
                                       data-game-id="<?php echo $game['id']; ?>"
                                       <?php echo $game['is_active'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            
                            <button class="btn btn-small btn-edit" onclick="editGame(<?php echo $game['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" name="delete_game" class="btn btn-small btn-danger confirm-action" 
                                        data-action="delete this game">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Edit Game Modal -->
    <div id="editGameModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Game</h3>
                <span class="modal-close">&times;</span>
            </div>
            <form id="editGameForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="game_id" id="edit_game_id">
                
                <div class="form-group">
                    <label for="edit_name">Game Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_display_order">Display Order</label>
                    <input type="number" id="edit_display_order" name="display_order" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="edit_game_image">New Image</label>
                    <input type="file" id="edit_game_image" name="game_image" accept=".png,.jpg,.jpeg,.webp">
                </div>
                
                <div class="form-group">
                    <label for="edit_image_url">Or Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active"> 
                        Game is Active
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_game" class="btn btn-primary">Update Game</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin.js"></script>
    <script>
        // Предварительный просмотр изображений
        function setupImagePreview() {
            const fileInput = document.getElementById('game_image');
            const urlInput = document.getElementById('image_url');
            const preview = document.getElementById('image-preview');
            
            // Предварительный просмотр для загруженного файла
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                    urlInput.value = ''; // Очищаем URL поле
                }
            });
            
            // Предварительный просмотр для URL
            urlInput.addEventListener('input', function(e) {
                const url = e.target.value.trim();
                if (url && isValidImageUrl(url)) {
                    preview.src = url;
                    preview.style.display = 'block';
                    preview.onerror = function() {
                        preview.style.display = 'none';
                    };
                    fileInput.value = ''; // Очищаем файл поле
                } else {
                    preview.style.display = 'none';
                }
            });
        }
        
        // Проверка валидности URL изображения
        function isValidImageUrl(url) {
            const pattern = /^https?:\/\/.+\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i;
            return pattern.test(url);
        }
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', setupImagePreview);
        
        // Edit game functionality
        function editGame(gameId) {
            // Find game data
            const gameCard = document.querySelector(`[data-game-id="${gameId}"]`);
            const gameName = gameCard.querySelector('h3').textContent;
            const isActive = gameCard.querySelector('.toggle-game-active').checked;
            
            // Fill modal
            document.getElementById('edit_game_id').value = gameId;
            document.getElementById('edit_name').value = gameName;
            document.getElementById('edit_is_active').checked = isActive;
            
            // Show modal
            document.getElementById('editGameModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editGameModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editGameModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Close modal with X button
        document.querySelector('.modal-close').onclick = closeEditModal;
    </script>
</body>
</html>