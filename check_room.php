<?php
session_start(); // Start the session

// Path to the rooms JSON file
$roomsFile = 'rooms.json';

// Initialize rooms list (or load it from the file if it exists)
$rooms = [];
if (file_exists($roomsFile)) {
    $rooms = json_decode(file_get_contents($roomsFile), true) ?? [];
}

// Check if session already has room data
if (isset($_SESSION['room_code'])) {
    // Retrieve the room code from the session
    $code = $_SESSION['room_code'];

    // Verify that the room exists
    if (isset($rooms[$code])) {
        // Room exists, get participants
        $host = $rooms[$code]['host'];
        $participants = $rooms[$code]['participants'];
        
        // Prepare response data
        $response = [
            'exists' => true,
            'host' => htmlspecialchars($host),
            'participants' => array_map('htmlspecialchars', $participants), // Escape participant names
        ];
    } else {
        // Room does not exist
        $response = [
            'exists' => false,
            'message' => 'Room does not exist.',
        ];
    }
} else {
    // No room code in session
    $response = [
        'exists' => false,
        'message' => 'No room code in session.',
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
