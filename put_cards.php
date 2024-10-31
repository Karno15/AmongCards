<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = json_decode(file_get_contents($roomsFile), true);

// Check for valid input data
if (!isset($_POST['cards']) || !isset($_SESSION['room_code']) || !isset($_SESSION['nickname']) || !isset($_POST['current_player'])) {
    echo json_encode(['error' => 'Data error.']);
    exit;
}

$code = $_SESSION['room_code'];
$nick = $_SESSION['nickname'];
$cards = $_POST['cards'];
$currentPlayer = $_POST['current_player'];

// Ensure that the nickname matches the current player
if ($currentPlayer !== $nick) {
    echo json_encode(['error' => 'It is not your turn.']);
    exit;
}

// Check if the room exists
if (!isset($rooms[$code])) {
    echo json_encode(['error' => 'Room not found.']);
    exit;
}

// Reference the specific room data
$room = &$rooms[$code];

// Verify that it is the current player's turn
if ($room['current_turn'] !== array_search($nick, $room['participants']) + 1) {
    echo json_encode(['error' => 'Not your turn.']);
    exit;
}

// Check if the cards are valid (player has the cards in their hand)
$playerCards = &$room['cards'][$nick];
$playerCardCounts = array_count_values($playerCards);
$selectedCardCounts = array_count_values($cards);

// Verify that the player has enough of each card selected
foreach ($selectedCardCounts as $card => $count) {
    if (!isset($playerCardCounts[$card]) || $playerCardCounts[$card] < $count) {
        echo json_encode(['error' => 'Invalid card(s) selection.']);
        exit;
    }
}

// Place the cards on the table and remove them from the player's hand
$room['last_cards'] = $cards; // Update table with chosen cards

// Remove each selected card one by one from the player's hand
foreach ($cards as $card) {
    $key = array_search($card, $playerCards);
    if ($key !== false) {
        unset($playerCards[$key]);
    }
}
// Re-index the array to maintain proper order
$playerCards = array_values($playerCards);

// Update the message to indicate the next player's turn
$nextTurn = ($room['current_turn'] % count($room['participants'])) + 1;
$room['current_turn'] = $nextTurn;
$room['message'] = "It's " . $room['participants'][$nextTurn - 1] . "'s turn.";

// Save changes to the rooms.json file
file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

// Return success response
echo json_encode(['success' => true, 'message' => 'Cards placed successfully.', 'table' => $cards]);
exit;
?>
