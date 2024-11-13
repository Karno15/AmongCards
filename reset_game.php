<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = json_decode(file_get_contents($roomsFile), true);
$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];

if ($rooms[$code]['host'] !== $nick) {
    echo json_encode(['error' => 'Only the host can reset the game.']);
    exit;
}

if (isset($_SESSION['room_code'])) {
    $code = $_SESSION['room_code'];

    // Check if the room exists
    if (isset($rooms[$code])) {
        // Remove the cards object entirely
        unset($rooms[$code]['cards']);

        // Set drawn to false
        $rooms[$code]['called'] = false;
        $rooms[$code]['drawn'] = false;
        $rooms[$code]['message'] = '';
        $rooms[$code]['current_turn'] = '';
        $rooms[$code]['table'] = '';
        $rooms[$code]['shoot'] = false;
        // Save the updated rooms data back to the file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
        $_SESSION['table'] = '';
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
