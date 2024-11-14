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
            'spectators' => $rooms[$code]['spectators'] ?? [],
            'drawn' => $rooms[$code]['drawn'] ?? false,
            'current_turn' => $rooms[$code]['current_turn'] ?? null,
            'message' => $rooms[$code]['message'] ?? '',
            'cards' => $rooms[$code]['cards'] ?? [],
            'last_nickname' => $rooms[$code]['last_nickname'] ?? '',
            'table' => $rooms[$code]['table'] ?? '',
            'called' => $rooms[$code]['called'] ?? false,
            'new_game' => $rooms[$code]['new_game'] ?? false,
            'shooting' => $rooms[$code]['shooting'] ?? false,
            'shots' => $rooms[$code]['shots'] ?? [],
        ];
        if(isset($rooms[$code]['called']) && $rooms[$code]['called']){
            $response['last_cards'] = $rooms[$code]['last_cards'] ?? [];
        }
    } else {
        $response['message'] = 'Room does not exist.';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
