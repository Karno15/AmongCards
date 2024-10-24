<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

if (!isset($_SESSION['room_code'], $_SESSION['nickname'])) {
    echo json_encode(['error' => 'You are not in a room.']);
    exit;
}

$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];

// Check if room exists
if (!isset($rooms[$code])) {
    echo json_encode(['error' => 'Room does not exist.']);
    exit;
}

// Function to draw cards
function drawCards($participants) {
    $deck = array_merge(
        array_fill(0, 6, 'k.svg'),
        array_fill(0, 6, 'q.svg'),
        array_fill(0, 6, 'a.svg'),
        array_fill(0, 2, 'joker.svg')
    );

    shuffle($deck); // Shuffle the deck

    $cards = [];
    foreach ($participants as $participant) {
        $cards[$participant] = array_splice($deck, 0, 5); // Give 5 cards to each participant
    }

    return $cards;
}

// Check if cards are already drawn
if (!isset($rooms[$code]['cards'])) {
    $participants = $rooms[$code]['participants'];
    $cards = drawCards($participants);

    // Save drawn cards to the room's data
    foreach ($participants as $participant) {
        $rooms[$code]['cards'][$participant] = $cards[$participant];
    }

    // Set the drawn flag to true
    $rooms[$code]['drawn'] = true;

    // Update the rooms.json file
    file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
}

// Prepare the response data
$responseData = [];
foreach ($rooms[$code]['participants'] as $participant) {
    if (isset($rooms[$code]['cards'][$participant])) {
        $responseData[$participant] = $rooms[$code]['cards'][$participant];
    }
}

// Return the cards for each participant
echo json_encode(['participants' => $responseData]);
?>
