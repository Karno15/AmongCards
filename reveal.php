<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

$code = $_SESSION['room_code'] ?? null;
if ($code && isset($rooms[$code])) {
    // Retrieve the last two cards in the room's last_cards array
    $lastCards = $rooms[$code]['last_cards'] ?? [];
    $host = $rooms[$code]['host'] ?? '';
    // Return the last_cards array as JSON
    echo json_encode(['last_cards' => $lastCards,'host' => $host,
]);
} else {
    echo json_encode(['error' => 'Room or last cards not found.']);
}
?>
