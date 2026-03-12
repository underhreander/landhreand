<?php
/**
 * Геоблок для СНГ стран
 * Добавьте этот файл в корень сайта и подключите в начале index.php
 */

class GeoBlock {
    // Список стран СНГ (ISO коды)
    private static $cis_countries = [
        'RU', // Россия
        'BY', // Беларусь
        'KZ', // Казахстан
        'KG', // Киргизия
        'TJ', // Таджикистан
        'UZ', // Узбекистан
        'AM', // Армения
        'AZ', // Азербайджан
        'MD', // Молдова
        'TM', // Туркменистан
        'UA', // Украина (если нужно блокировать)
        'GE'  // Грузия (если нужно блокировать)
    ];

    /**
     * Получить IP адрес пользователя
     */
    private static function getUserIP() {
        // Проверяем различные заголовки для получения реального IP
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Получить страну по IP через бесплатный API
     */
    private static function getCountryByIP($ip) {
        // Используем бесплатный сервис ip-api.com
        $url = "http://ip-api.com/json/{$ip}?fields=status,countryCode";
        
        // Настройки для cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; GeoBlock/1.0)');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return $data['countryCode'];
            }
        }
        
        return null;
    }

    /**
     * Альтернативный метод через ipinfo.io
     */
    private static function getCountryByIPBackup($ip) {
        $url = "https://ipinfo.io/{$ip}/country";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; GeoBlock/1.0)');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return trim($response);
        }
        
        return null;
    }

    /**
     * Проверить, нужно ли блокировать пользователя
     */
    public static function shouldBlock() {
        // Проверка на локальные IP (для тестирования)
        $user_ip = self::getUserIP();
        
        if (filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            // Локальный IP - не блокируем в dev среде
            return false;
        }

        // Получаем страну пользователя
        $country = self::getCountryByIP($user_ip);
        
        // Если первый API не сработал, пробуем второй
        if (!$country) {
            $country = self::getCountryByIPBackup($user_ip);
        }
        
        // Если не удалось определить страну, не блокируем
        if (!$country) {
            return false;
        }
        
        // Проверяем, входит ли страна в список СНГ
        return in_array(strtoupper($country), self::$cis_countries);
    }

    /**
     * Показать страницу блокировки
     */
    public static function showBlockPage() {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Restricted</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Arial', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }
                
                .container {
                    text-align: center;
                    max-width: 600px;
                    padding: 2rem;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 20px;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    opacity: 0.8;
                }
                
                h1 {
                    font-size: 2.5rem;
                    margin-bottom: 1rem;
                    font-weight: 300;
                }
                
                p {
                    font-size: 1.2rem;
                    line-height: 1.6;
                    opacity: 0.9;
                    margin-bottom: 2rem;
                }
                
                .info {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 1rem;
                    border-radius: 10px;
                    font-size: 0.9rem;
                    opacity: 0.7;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🚫</div>
                <h1>Access Restricted</h1>
                <p>Sorry, but access to this website is not available from your location.</p>
                <div class="info">
                    This restriction is in place due to regional policies and regulations.
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Основная функция проверки и блокировки
     */
    public static function check() {
        if (self::shouldBlock()) {
            self::showBlockPage();
        }
    }
}

// Автоматическая проверка при подключении файла
// GeoBlock::check();
?>