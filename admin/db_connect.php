<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'vh19747_root');
define('DB_PASS', 'Jwc31rgZ28');
define('DB_NAME', 'vh19747_gamehublauncher');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

/**
 * Log visit to database
 */
function log_visit() {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $page = $_SERVER['REQUEST_URI'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $country = get_country_from_ip($ip);
    
    try {
        $stmt = $conn->prepare("INSERT INTO visits (ip_address, user_agent, page_visited, referrer, country) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $user_agent, $page, $referrer, $country);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error logging visit: " . $e->getMessage());
    }
}

/**
 * Get country from IP using multiple APIs with fallback
 */
function get_country_from_ip($ip) {
    // Check for localhost/private IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return 'Localhost';
    }
    
    // Try different free GeoIP services
    $apis = [
        "http://ip-api.com/json/{$ip}?fields=country",
        "https://ipapi.co/{$ip}/country_name/",
        "https://api.country.is/{$ip}"
    ];
    
    foreach ($apis as $api) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Mozilla/5.0 (compatible; GeoIP/1.0)'
                ]
            ]);
            
            $response = @file_get_contents($api, false, $context);
            
            if ($response === false) {
                continue;
            }
            
            // Handle different API response formats
            if (strpos($api, 'ip-api.com') !== false) {
                $data = json_decode($response, true);
                if (isset($data['country']) && !empty($data['country']) && $data['country'] !== 'Unknown') {
                    return $data['country'];
                }
            } elseif (strpos($api, 'ipapi.co') !== false) {
                $country = trim($response);
                if (!empty($country) && $country !== 'Undefined' && strlen($country) > 1) {
                    return $country;
                }
            } elseif (strpos($api, 'country.is') !== false) {
                $data = json_decode($response, true);
                if (isset($data['country']) && !empty($data['country'])) {
                    return $data['country'];
                }
            }
        } catch (Exception $e) {
            error_log("GeoIP API error for {$api}: " . $e->getMessage());
            continue;
        }
    }
    
    // If all APIs fail, try one more with curl if available
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://freegeoip.app/json/{$ip}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; GeoIP/1.0)');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['country_name']) && !empty($data['country_name'])) {
                    return $data['country_name'];
                }
            }
        } catch (Exception $e) {
            error_log("cURL GeoIP error: " . $e->getMessage());
        }
    }
    
    return 'Unknown';
}

/**
 * Get basic site statistics (улучшенная версия)
 */
function get_site_stats() {
    global $conn;
    
    $stats = [
        'total_visits' => 0,
        'unique_visitors' => 0,
        'total_downloads' => 0,
        'unique_downloads' => 0,
        'today_downloads' => 0,
        'today_unique_downloads' => 0
    ];
    
    try {
        $queries = [
            'total_visits' => "SELECT COUNT(*) as total FROM visits",
            'unique_visitors' => "SELECT COUNT(DISTINCT ip_address) as unique_count FROM visits",
            'total_downloads' => "SELECT COUNT(*) as total FROM downloads",
            'unique_downloads' => "SELECT COUNT(DISTINCT ip_address) as unique_count FROM downloads",
            'today_downloads' => "SELECT COUNT(*) as today FROM downloads WHERE DATE(download_time) = CURDATE()",
            'today_unique_downloads' => "SELECT COUNT(DISTINCT ip_address) as today_unique FROM downloads WHERE DATE(download_time) = CURDATE()"
        ];
        
        foreach ($queries as $key => $query) {
            $result = $conn->query($query);
            if ($result && $row = $result->fetch_assoc()) {
                $stats[$key] = $row[array_key_first($row)];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting site stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Get list of active games ordered by display order
 */
function get_games_list() {
    global $conn;
    
    $games = [];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM games WHERE is_active = 1 ORDER BY display_order, name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error getting games list: " . $e->getMessage());
    }
    
    return $games;
}

/**
 * Log download with trial code (улучшенная версия)
 */
function log_download($code, $email = '') {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $country = get_country_from_ip($ip);
    
    try {
        // First check if this IP has already downloaded today (for unique downloads count)
        $stmt_check = $conn->prepare("SELECT id FROM downloads WHERE ip_address = ? AND DATE(download_time) = CURDATE() LIMIT 1");
        $stmt_check->bind_param("s", $ip);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        // Insert the download record
        $stmt = $conn->prepare("INSERT INTO downloads (ip_address, user_agent, country, trial_code, email, download_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $ip, $user_agent, $country, $code, $email);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Log admin action for tracking
            log_admin_action('download_logged', "Download logged - IP: $ip, Code: $code");
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Error logging download: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unique downloads count (исправленная функция)
 */
function get_unique_downloads() {
    global $conn;
    
    try {
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_downloads FROM downloads");
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['unique_downloads'];
        }
    } catch (Exception $e) {
        error_log("Error getting unique downloads: " . $e->getMessage());
    }
    
    return 0;
}

/**
 * Get today's unique downloads count
 */
function get_today_unique_downloads() {
    global $conn;
    
    try {
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as today_unique FROM downloads WHERE DATE(download_time) = CURDATE()");
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['today_unique'];
        }
    } catch (Exception $e) {
        error_log("Error getting today unique downloads: " . $e->getMessage());
    }
    
    return 0;
}

/**
 * Get current download link
 */
function get_download_link() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'download_link' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            return $row['value'];
        }
    } catch (Exception $e) {
        error_log("Error getting download link: " . $e->getMessage());
    }
    
    return 'downloads/GameHubLauncher.exe';
}

/**
 * Admin authentication
 */
function admin_login($username, $password) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $admin['password_hash'])) {
                // Update last login
                $update = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $admin['id']);
                $update->execute();
                $update->close();
                
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;
                
                // Log the login
                log_admin_action('login', 'Successful login');
                
                return $admin;
            }
        }
        
        // Log failed attempt
        log_admin_action('failed_login', 'Failed login attempt for username: ' . $username);
    } catch (Exception $e) {
        error_log("Error during admin login: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Log admin actions
 */
function log_admin_action($action, $details = '') {
    global $conn;
    
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, ip_address, action, details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $admin_id, $ip, $action, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
    }
}

/**
 * Get admin statistics (исправленная версия с улучшенными запросами)
 */
function get_admin_stats() {
    global $conn;
    
    $stats = [
        'total_visits' => 0,
        'unique_visitors' => 0,  
        'total_downloads' => 0,
        'unique_downloads' => 0,
        'today_downloads' => 0,
        'today_unique_downloads' => 0
    ];
    
    try {
        // Total visits
        $result = $conn->query("SELECT COUNT(*) as total FROM visits");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_visits'] = (int)$row['total'];
        }
        
        // Unique visitors
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_count FROM visits");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['unique_visitors'] = (int)$row['unique_count'];
        }
        
        // Total downloads
        $result = $conn->query("SELECT COUNT(*) as total FROM downloads");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_downloads'] = (int)$row['total'];
        }
        
        // Unique downloads (всех времен)
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_count FROM downloads");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['unique_downloads'] = (int)$row['unique_count'];
        }
        
        // Today's downloads
        $result = $conn->query("SELECT COUNT(*) as today FROM downloads WHERE DATE(download_time) = CURDATE()");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['today_downloads'] = (int)$row['today'];
        }
        
        // Today's unique downloads
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as today_unique FROM downloads WHERE DATE(download_time) = CURDATE()");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['today_unique_downloads'] = (int)$row['today_unique'];
        }
        
    } catch (Exception $e) {
        error_log("Error getting admin stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Check if IP has already downloaded today (исправленная функция)
 */
function has_downloaded_today($ip) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id FROM downloads WHERE ip_address = ? AND DATE(download_time) = CURDATE() LIMIT 1");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_downloaded = $result->num_rows > 0;
        $stmt->close();
        
        return $has_downloaded;
    } catch (Exception $e) {
        error_log("Error checking today's downloads: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total downloads count
 */
function get_total_downloads() {
    global $conn;
    
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM downloads");
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
    } catch (Exception $e) {
        error_log("Error getting total downloads: " . $e->getMessage());
    }
    
    return 0;
}

/**
 * Get today's downloads count
 */
function get_today_downloads() {
    global $conn;
    
    try {
        $result = $conn->query("SELECT COUNT(*) as today FROM downloads WHERE DATE(download_time) = CURDATE()");
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['today'];
        }
    } catch (Exception $e) {
        error_log("Error getting today downloads: " . $e->getMessage());
    }
    
    return 0;
}

/**
 * Get recent visits
 */
function get_recent_visits($limit = 10) {
    global $conn;
    
    $visits = [];
    
    try {
        $stmt = $conn->prepare("SELECT ip_address, country, page_visited, visit_time FROM visits ORDER BY visit_time DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $visits[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting recent visits: " . $e->getMessage());
    }
    
    return $visits;
}

/**
 * Get recent downloads
 */
function get_recent_downloads($limit = 10) {
    global $conn;
    
    $downloads = [];
    
    try {
        $stmt = $conn->prepare("SELECT ip_address, country, trial_code, download_time FROM downloads ORDER BY download_time DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $downloads[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting recent downloads: " . $e->getMessage());
    }
    
    return $downloads;
}

/**
 * Get country statistics
 */
function get_country_stats() {
    global $conn;
    
    $countries = [];
    
    try {
        $stmt = $conn->prepare("SELECT country, COUNT(*) as count FROM visits WHERE country != 'Unknown' GROUP BY country ORDER BY count DESC LIMIT 8");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $countries[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting country stats: " . $e->getMessage());
    }
    
    return $countries;
}

/**
 * Get daily statistics
 */
function get_daily_stats($days = 30) {
    global $conn;
    
    $stats = [];
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                DATE(visit_time) as date, 
                COUNT(*) as visits,
                (SELECT COUNT(*) FROM downloads WHERE DATE(download_time) = DATE(v.visit_time)) as downloads
            FROM visits v
            WHERE visit_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(visit_time)
            ORDER BY DATE(visit_time)
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting daily stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Add new game
 */
function add_game($name, $image_path) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO games (name, image) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $image_path);
        $result = $stmt->execute();
        
        if ($result) {
            log_admin_action('game_add', "Added game: $name");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error adding game: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete game
 */
function delete_game($game_id) {
    global $conn;
    
    try {
        // Get game info before deletion for logging
        $stmt = $conn->prepare("SELECT name FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
        
        // Delete the game
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $result = $stmt->execute();
        
        if ($result && isset($game['name'])) {
            log_admin_action('game_delete', "Deleted game: " . $game['name']);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error deleting game: " . $e->getMessage());
        return false;
    }
}

/**
 * Update game
 */
function update_game($game_id, $name, $image_path, $display_order, $is_active) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE games SET name = ?, image = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssiii", $name, $image_path, $display_order, $is_active, $game_id);
        $result = $stmt->execute();
        
        if ($result) {
            log_admin_action('game_update', "Updated game ID: $game_id");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error updating game: " . $e->getMessage());
        return false;
    }
}

/**
 * Get game by ID
 */
function get_game_by_id($game_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM games WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting game: " . $e->getMessage());
        return false;
    }
}
?>