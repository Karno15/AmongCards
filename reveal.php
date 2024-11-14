<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

$code = $_SESSION['room_code'] ?? null;
if ($code && isset($rooms[$code])) {

        $lastCards = $rooms[$code]['last_cards'] ?? [];
        $host = $rooms[$code]['host'] ?? '';
        $table = $rooms[$code]['table'] ?? '';
        // Return the last_cards array as JSON

        $rooms[$code]['shooting'] = true;

        // Save the modified data back to the JSON file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

        echo json_encode([
            'last_cards' => $lastCards,
            'host' => $host,
            'table' => $table
        ]);

} else {
    echo json_encode(['error' => 'Room not found.']);
}
