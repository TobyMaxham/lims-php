<?php

declare(strict_types=1);

// Minimal router for the PHP built-in webserver, used by CurlClientTest to
// verify the CurlClient against a real HTTP server.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

switch ($path) {
    case '/echo':
        http_response_code(200);
        echo json_encode([
            'method'         => $_SERVER['REQUEST_METHOD'],
            'authorization'  => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            'accept'         => $_SERVER['HTTP_ACCEPT'] ?? null,
            'content_type'   => $_SERVER['CONTENT_TYPE'] ?? null,
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
            'body'           => file_get_contents('php://input'),
        ]);
        break;

    case '/not-found':
        http_response_code(404);
        header('X-Custom: yes');
        echo json_encode(['License not found']);
        break;

    case '/slow':
        usleep(1_500_000);
        http_response_code(200);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['No route for '.$path]);
        break;
}
