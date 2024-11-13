<?php
session_start();

// Path to the JSON file where the room data is stored
$roomsFile = 'rooms.json';

// Read the existing rooms data from the file
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

// Ensure necessary data is provided
if (isset($_POST['nickname']) && isset($_POST['dead'])) {
    $nickname = $_POST['nickname'];
    $dead = (int)$_POST['dead'];
    $code = $_SESSION['room_code'] ?? null;

    // Check if the room code exists and is valid
    if ($code && isset($rooms[$code])) {
        // Get participants, spectators, and shots data
        $participants = $rooms[$code]['participants'] ?? [];
        $spectators = $rooms[$code]['spectators'] ?? [];
        $shots = $rooms[$code]['shots'] ?? [];

        // Process the shoot action
        if ($dead === 0) {
            // Player is not dead, waste a shot by increasing their shot count
            if (isset($shots[$nickname])) {
                $shots[$nickname] = min($shots[$nickname] + 1, 6); // Max shots: 6
                $rooms[$code]['message'] = $nickname.' stayed alive!';
            }
        } else {
            // Player is dead, move to spectators and remove from participants
            if (isset($participants[$nickname])) {
                // Move the player to spectators
                $spectators[] = $nickname;

                // Remove from participants
                unset($participants[$nickname]);

                // Remove the player's shots and cards
                unset($shots[$nickname]);
                unset($rooms[$code]['cards'][$nickname]); // Assuming cards are stored in the room's data
                $rooms[$code]['message'] = $nickname.' died!';
            }
        }

        // Update the rooms array with the modified participants, spectators, and shots
        $rooms[$code]['participants'] = $participants;
        $rooms[$code]['spectators'] = $spectators;
        $rooms[$code]['shots'] = $shots;

        // Write the updated data back to the JSON file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

        // Return the updated data as a response
        echo json_encode([
            'participants' => $participants,
            'spectators' => $spectators,
            'shots' => $shots
        ]);
    } else {
        echo json_encode(['error' => 'Room not found or invalid session']);
    }
} else {
    echo json_encode(['error' => 'Invalid data']);
}
?>
