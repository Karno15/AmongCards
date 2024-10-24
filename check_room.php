<?php
session_start();

$roomsFile = 'rooms.json';

$rooms = [];
if (file_exists($roomsFile)) {
    $rooms = json_decode(file_get_contents($roomsFile), true) ?? [];
}

if (isset($_SESSION['room_code'])) {
    $code = $_SESSION['room_code'];

    if (isset($rooms[$code])) {
        $host = $rooms[$code]['host'];
        $participants = $rooms[$code]['participants'];
        $drawn = $rooms[$code]['drawn'] ?? false; // Get the drawn flag, default to false if not set
        
        $response = [
            'exists' => true,
            'host' => htmlspecialchars($host),
            'participants' => array_map('htmlspecialchars', $participants),
            'drawn' => $drawn, // Include the drawn flag in the response
        ];
    } else {
        $response = [
            'exists' => false,
            'message' => 'Room does not exist.',
        ];
    }
} else {
    $response = [
        'exists' => false,
        'message' => 'No room code in session.',
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
