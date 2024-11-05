<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

// Verify session information
if (!isset($_SESSION['room_code'], $_SESSION['nickname'])) {
    echo json_encode(['error' => 'You are not in a room.']);
    exit;
}

$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];

// Check if the room exists
if (!isset($rooms[$code])) {
    echo json_encode(['error' => 'Room does not exist.']);
    exit;
}

// Function to draw cards
function drawCards($participants)
{
    $deck = array_merge(
        array_fill(0, 6, 'k'),
        array_fill(0, 6, 'q'),
        array_fill(0, 6, 'a'),
        array_fill(0, 2, 'joker')
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
    // Ensure participants are available
    if (!isset($rooms[$code]['participants']) || empty($rooms[$code]['participants'])) {
        echo json_encode(['error' => 'No participants available to draw cards.']);
        exit;
    }

    $participants = $rooms[$code]['participants'];
    $cards = drawCards($participants);

    // Save drawn cards to the room's data
    foreach ($participants as $index => $participant) {
        $rooms[$code]['cards'][$participant] = $cards[$participant];
    }

    // Set drawn flag to true
    $rooms[$code]['drawn'] = true;

    // Set a random table flag
    $tableOptions = ['k', 'q', 'a'];
    $rooms[$code]['table'] = $tableOptions[array_rand($tableOptions)];

    // Assign numbers to participants and select a random starting turn
    $playerNumber = rand(1, count($participants));
    $rooms[$code]['current_turn'] = $playerNumber;

    // Set the message for the current turn based on the player number and nickname
    $nickname = $participants[$playerNumber - 1];
    $rooms[$code]['message'] = "It's $nickname's turn.";
    $rooms[$code]['called'] = false;
    // Update the rooms.json file
    if (file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT)) === false) {
        echo json_encode(['error' => 'Failed to save card data.']);
        exit;
    }
}

// Ensure that session data is set even if it's not drawn
$_SESSION['table'] = $rooms[$code]['table'] ?? null; // Use null if not set
$_SESSION['current_turn'] = $rooms[$code]['current_turn'] ?? null; // Use null if not set

// Prepare the response data
$responseData = [];
foreach ($rooms[$code]['participants'] as $participant) {
    if (isset($rooms[$code]['cards'][$participant])) {
        $responseData[$participant] = $rooms[$code]['cards'][$participant];
    }
}

// Return the cards, "table" value, and turn message
echo json_encode([
    'participants' => $responseData,
    'table' => $rooms[$code]['table'] ?? null, // Return null if not set
    'message' => $rooms[$code]['message'] ?? null, // Return null if not set
    'current_turn' => $rooms[$code]['current_turn'] ?? null // Return null if not set
]);
?>
