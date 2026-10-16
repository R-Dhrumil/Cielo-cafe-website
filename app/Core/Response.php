<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    /**
     * Send JSON response. Supports both standard ($data, $statusCode, $headers)
     * and convenience ($successBool, $data, $message, $statusCode) signatures.
     */
    public static function json(mixed $data, mixed $arg2 = 200, mixed $arg3 = [], mixed $arg4 = null): void
    {
        // Convenience signature: Response::json(bool $success, mixed $data, string $message = '', int $statusCode = 200)
        if (is_bool($data)) {
            $isSuccess = $data;
            $payloadData = $arg2;
            $message = is_string($arg3) ? $arg3 : ($isSuccess ? 'Success' : 'Error');
            $statusCode = is_int($arg4) ? $arg4 : (is_int($arg3) ? $arg3 : ($isSuccess ? 200 : 400));

            $payload = [
                'success' => $isSuccess,
                'message' => $message,
                'data' => $payloadData,
            ];

            if (!$isSuccess && !empty($message)) {
                $payload['error'] = $message;
            }

            http_response_code($statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Standard signature: Response::json(mixed $data, int $statusCode = 200, array $headers = [])
        $statusCode = is_int($arg2) ? $arg2 : 200;
        $headers = is_array($arg3) ? $arg3 : [];

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
