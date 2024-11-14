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


        $participants = $rooms[$code]['participants'];
        foreach ($participants as $participant) {
                $rooms[$code]['shots'][$participant] = 0;
        }


        // Save the updated rooms data back to the file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
        $_SESSION['table'] = '';
        $response = [
            'success' => true,
            'message' => 'Shots reset successfully.',
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
