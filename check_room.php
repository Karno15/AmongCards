<?php
session_start();

$roomsFile = 'rooms.json';

$rooms = [];
if (file_exists($roomsFile)) {
    $rooms = json_decode(file_get_contents($roomsFile), true) ?? [];
}

$response = [
    'exists' => false,
    'message' => 'No room code in session.',
];

if (isset($_SESSION['room_code'])) {
    $code = $_SESSION['room_code'];

    if (isset($rooms[$code])) {
        $response = [
            'host' => $rooms[$code]['host'] ?? '',
            'participants' => $rooms[$code]['participants'] ?? [],
            'drawn' => $rooms[$code]['drawn'] ?? false,
            'current_turn' => $rooms[$code]['current_turn'] ?? null,
            'message' => $rooms[$code]['message'] ?? '',
            'cards' => $rooms[$code]['cards'] ?? [],
            'table' => $rooms[$code]['table'] ?? '',
            'last_cards' => $rooms[$code]['last_cards'] ?? ''
        ];
    } else {
        $response['message'] = 'Room does not exist.';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
