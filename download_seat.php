<?php

declare(strict_types=1);

// Validate seat number
$seatNo = $_GET['seat'] ?? '';

if (!ctype_digit((string) $seatNo) || (int) $seatNo < 1) {
    http_response_code(400);
    exit('Invalid seat number.');
}

// Define the image directory
$imageDirectory = __DIR__ . '/images/Seats/';

// Construct image path
$filePath = $imageDirectory . (int) $seatNo . '.jpg';

// Check whether the image exists
if (!is_file($filePath)) {
    http_response_code(404);
    exit('Seating arrangement not found.');
}

// Force download
header('Content-Type: image/jpeg');
header(
    'Content-Disposition: attachment; filename="Seating-Arrangement-SeatNo-' .
    (int) $seatNo . '.jpg"'
);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store');

readfile($filePath);
exit;
