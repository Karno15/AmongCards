<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = json_decode(file_get_contents($roomsFile), true);

// Check for valid input data
if (!isset($_SESSION['room_code']) || !isset($_SESSION['nickname'])) {
    echo json_encode(['error' => 'Data error: Room code or nickname missing.']);
    exit;
}

$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];

// Check if the room exists
if (!isset($rooms[$code])) {
    echo json_encode(['error' => 'Room not found.']);
    exit;
}

// Check if user is a spectator and not already a participant
if (!in_array($nick, $rooms[$code]['spectators'])) {
    echo json_encode(['error' => 'User is not a spectator or is already a participant.']);
    exit;
}

// Check if there are 1-3 participants and the "drawn" flag is false
$participants = $rooms[$code]['participants'];
if (count($participants) < 1 || count($participants) > 3 || isset($rooms[$code]['drawn']) && $rooms[$code]['drawn'] === true) {
    echo json_encode(['error' => 'Cannot join game: Invalid participant count or drawn flag is set.']);
    exit;
}

// Remove user from spectators
$rooms[$code]['spectators'] = array_filter($rooms[$code]['spectators'], function ($spectator) use ($nick) {
    return $spectator !== $nick;
});

// Add user to participants
$rooms[$code]['participants'][] = $nick;

// Save the updated data back to rooms.json
if (file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Successfully joined the game.']);
} else {
    echo json_encode(['error' => 'Failed to update the room data.']);
}

exit;
