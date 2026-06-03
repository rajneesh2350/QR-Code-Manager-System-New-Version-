<?php
class GeoLocation {

    public static function getLocationInfo($ip) {
        if ($ip == '127.0.0.1' || $ip == '::1' || $ip == 'UNKNOWN') {
            return [
                'country' => 'Local',
                'city' => 'Local',
                'region' => 'Local',
                'isp' => 'Local',
                'timezone' => date_default_timezone_get()
            ];
        }

        // Try ipapi.co (free, no API key required)
        $apiUrl = "http://ip-api.com/json/{$ip}?fields=status,country,city,regionName,isp,timezone";

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                if ($data && isset($data['status']) && $data['status'] == 'success') {
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['regionName'] ?? 'Unknown',
                        'isp' => $data['isp'] ?? 'Unknown',
                        'timezone' => $data['timezone'] ?? 'Unknown'
                    ];
                }
            }
        }

        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'region' => 'Unknown',
            'isp' => 'Unknown',
            'timezone' => 'Unknown'
        ];
    }
}