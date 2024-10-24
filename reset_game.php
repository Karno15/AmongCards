<?php
session_start();

$roomsFile = 'rooms.json';

if (file_exists($roomsFile)) {
    $rooms = json_decode(file_get_contents($roomsFile), true);
} else {
    $rooms = [];
}

if (isset($_SESSION['room_code'])) {
    $code = $_SESSION['room_code'];

    // Check if the room exists
    if (isset($rooms[$code])) {
        // Remove the cards object entirely
        unset($rooms[$code]['cards']);
        
        // Set drawn to false
        $rooms[$code]['drawn'] = false;

        // Save the updated rooms data back to the file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

        $response = [
            'success' => true,
            'message' => 'Game reset successfully.',
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Room does not exist.',
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'No room code in session.',
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
