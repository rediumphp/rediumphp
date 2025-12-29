<?php

namespace Redium\Http;

use Exception;

class HttpClient
{
    /**
     * Make HTTP GET request
     * 
     * @param string $url Target URL
     * @param array $headers Optional headers
     * @return array Response data
     * @throws Exception On request failure
     */
    public static function get(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("GET request failed: {$error}");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("GET request returned HTTP {$httpCode}");
        }

        return $response ? (json_decode($response, true) ?? []) : [];
    }

    /**
     * Make HTTP POST request
     * 
     * @param string $url Target URL
     * @param array $body Request body data
     * @param array $headers Optional headers
     * @return array Response data
     * @throws Exception On request failure
     */
    public static function post(string $url, array $body, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        
        $defaultHeaders = ['Content-Type: application/json'];
        $combinedHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $combinedHeaders);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("POST request failed: {$error}");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("POST request returned HTTP {$httpCode}");
        }

        return $response ? (json_decode($response, true) ?? []) : [];
    }

    /**
     * Make HTTP PUT request
     * 
     * @param string $url Target URL
     * @param array $body Request body data
     * @param array $headers Optional headers
     * @return array Response data
     * @throws Exception On request failure
     */
    public static function put(string $url, array $body, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        
        $defaultHeaders = ['Content-Type: application/json'];
        $combinedHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $combinedHeaders);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("PUT request failed: {$error}");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("PUT request returned HTTP {$httpCode}");
        }

        return $response ? (json_decode($response, true) ?? []) : [];
    }

    /**
     * Make HTTP DELETE request
     * 
     * @param string $url Target URL
     * @param array $headers Optional headers
     * @return array Response data
     * @throws Exception On request failure
     */
    public static function delete(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("DELETE request failed: {$error}");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("DELETE request returned HTTP {$httpCode}");
        }

        return $response ? (json_decode($response, true) ?? []) : [];
    }
}
