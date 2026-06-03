<?php
class DeviceDetector {

    private $userAgent;
    private $deviceInfo = [];

    public function __construct($userAgent = null) {
        $this->userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->detectDevice();
    }

    private function detectDevice() {
        // Initialize default values
        $this->deviceInfo = [
            'device_type' => 'Unknown',
            'device_os' => 'Unknown',
            'os_version' => 'Unknown',
            'browser' => 'Unknown',
            'browser_version' => 'Unknown',
            'device_model' => 'Unknown',
            'is_mobile' => 0,
            'is_tablet' => 0,
            'is_desktop' => 0,
            'is_robot' => 0
        ];

        $ua = strtolower($this->userAgent);

        // Detect bots/crawlers
        $bots = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
                 'yandexbot', 'facebookexternalhit', 'facebot', 'twitterbot'];
        foreach ($bots as $bot) {
            if (strpos($ua, $bot) !== false) {
                $this->deviceInfo['device_type'] = 'Robot';
                $this->deviceInfo['is_robot'] = 1;
                $this->deviceInfo['device_model'] = ucfirst($bot);
                return $this->deviceInfo;
            }
        }

        // Detect mobile devices
        if (preg_match('/(android|iphone|ipod|windows phone|blackberry|opera mini|iemobile)/', $ua, $matches)) {
            $this->deviceInfo['is_mobile'] = 1;
            $this->deviceInfo['device_type'] = 'Mobile';

            // Android
            if (strpos($ua, 'android') !== false) {
                $this->deviceInfo['device_os'] = 'Android';
                if (preg_match('/android[\/\s]([\d\.]+)/', $ua, $matches)) {
                    $this->deviceInfo['os_version'] = $matches[1];
                }
                // Detect Samsung, Google Pixel, etc.
                if (preg_match('/(sm-[a-z0-9]+|samsung|nexus|pixel)/', $ua, $matches)) {
                    $this->deviceInfo['device_model'] = strtoupper($matches[1]);
                }
            }
            // iOS
            elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipod') !== false) {
                $this->deviceInfo['device_os'] = 'iOS';
                if (preg_match('/os ([\d_]+) like mac/', $ua, $matches)) {
                    $this->deviceInfo['os_version'] = str_replace('_', '.', $matches[1]);
                }
                $this->deviceInfo['device_model'] = (strpos($ua, 'ipod') !== false) ? 'iPod' : 'iPhone';
            }
            // Windows Phone
            elseif (strpos($ua, 'windows phone') !== false) {
                $this->deviceInfo['device_os'] = 'Windows Phone';
                if (preg_match('/windows phone ([\d\.]+)/', $ua, $matches)) {
                    $this->deviceInfo['os_version'] = $matches[1];
                }
            }
        }
        // Detect tablets
        elseif (preg_match('/(ipad|tablet|kindle|silk)/', $ua)) {
            $this->deviceInfo['is_tablet'] = 1;
            $this->deviceInfo['device_type'] = 'Tablet';

            if (strpos($ua, 'ipad') !== false) {
                $this->deviceInfo['device_os'] = 'iOS';
                if (preg_match('/os ([\d_]+) like mac/', $ua, $matches)) {
                    $this->deviceInfo['os_version'] = str_replace('_', '.', $matches[1]);
                }
                $this->deviceInfo['device_model'] = 'iPad';
            } elseif (strpos($ua, 'android') !== false) {
                $this->deviceInfo['device_os'] = 'Android';
                $this->deviceInfo['device_model'] = 'Android Tablet';
            }
        }
        // Desktop
        else {
            $this->deviceInfo['is_desktop'] = 1;
            $this->deviceInfo['device_type'] = 'Desktop';

            // Detect OS
            if (strpos($ua, 'windows') !== false) {
                $this->deviceInfo['device_os'] = 'Windows';
                if (preg_match('/windows nt ([\d\.]+)/', $ua, $matches)) {
                    $versions = ['6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista', '5.2' => 'XP'];
                    $this->deviceInfo['os_version'] = $versions[$matches[1]] ?? $matches[1];
                }
            } elseif (strpos($ua, 'mac') !== false) {
                $this->deviceInfo['device_os'] = 'macOS';
                if (preg_match('/mac os x ([\d_]+)/', $ua, $matches)) {
                    $this->deviceInfo['os_version'] = str_replace('_', '.', $matches[1]);
                }
            } elseif (strpos($ua, 'linux') !== false) {
                $this->deviceInfo['device_os'] = 'Linux';
            } elseif (strpos($ua, 'chrome os') !== false) {
                $this->deviceInfo['device_os'] = 'Chrome OS';
            }
        }

        // Detect browser
        $browsers = [
            'chrome' => 'Chrome',
            'firefox' => 'Firefox',
            'safari' => 'Safari',
            'edge' => 'Edge',
            'opera' => 'Opera',
            'msie' => 'Internet Explorer',
            'trident' => 'Internet Explorer'
        ];

        foreach ($browsers as $key => $name) {
            if (strpos($ua, $key) !== false) {
                $this->deviceInfo['browser'] = $name;
                // Extract version
                if (preg_match('/' . $key . '[\/\s]([\d\.]+)/', $ua, $matches)) {
                    $this->deviceInfo['browser_version'] = $matches[1];
                }
                break;
            }
        }

        return $this->deviceInfo;
    }

    public function getDeviceInfo() {
        return $this->deviceInfo;
    }

    public function getAllInfo() {
        return [
            'device_info' => $this->deviceInfo,
            'raw_user_agent' => $this->userAgent,
            'ip_address' => $this->getClientIP(),
            'language' => substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 5),
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'session_id' => session_id() ?: null
        ];
    }

    private function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';

        // Handle multiple IPs (take the first one)
        if (strpos($ipaddress, ',') !== false) {
            $ipaddress = explode(',', $ipaddress)[0];
        }

        return trim($ipaddress);
    }
}