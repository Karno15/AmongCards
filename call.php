<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = json_decode(file_get_contents($roomsFile), true);

// Check for valid input data
if (!isset($_SESSION['room_code']) || !isset($_SESSION['nickname'])) {
    echo json_encode(['error' => 'Data error.']);
    exit;
}

$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];

// Check if the room exists
if (!isset($rooms[$code])) {
    echo json_encode(['error' => 'Room not found.']);
    exit;
}

// Check if 'last_nickname' exists in the room data
if (!isset($rooms[$code]['last_nickname'])) {
    echo json_encode(['error' => 'Unable to call. The "last_nickname" flag is missing.']);
    exit;
}else{
    $last_nickname = $rooms[$code]['last_nickname'];
}

// Update the 'called' flag and 'last_nickname' to the current user
$rooms[$code]['called'] = true;

$rooms[$code]['message'] = $nick.' called '.$last_nickname.' a liar!';
// Save the updated data back to rooms.json
if (file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Call action completed.']);
} else {
    echo json_encode(['error' => 'Failed to update the room data.']);
}
exit;
