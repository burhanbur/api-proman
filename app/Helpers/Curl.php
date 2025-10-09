<?php 

if (!function_exists('postCurl')) {
    function postCurl($url = '', $params = '', $header = [])
    {
        $headers = array(
            'Content-Type: application/json',
        );

        if ($header) {
            $headers = array_merge($headers, $header);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($error) {
            \Log::error("cURL Error: " . $error);
            return null;
        }
        
        $data = json_decode($output);

        // Handle JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("JSON Decode Error: " . json_last_error_msg());
            return null;
        }
        
        return $data;
    }
}

if (!function_exists('getCurl')) {
    function getCurl($url = '', $params = '', $header = [])
    {
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
        );

        if ($header) {
            $headers = array_merge($headers, $header);
        }

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);

        curl_close($ch);
        
        $data = json_decode($output);
        
        return $data;
    }
}

if (!function_exists('putCurl')) {
    function putCurl($url = '', $params = '', $header = [])
    {
        $headers = array(
            'Content-Type: application/json',
        );

        if ($header) {
            $headers = array_merge($headers, $header);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);

        curl_close($ch);
        
        $data = json_decode($output);
        
        return $data;
    }
}