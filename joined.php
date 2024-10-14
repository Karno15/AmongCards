<?php
session_start(); // Start the session to store/retrieve session data

// Path to the rooms JSON file
$roomsFile = 'rooms.json';

// Initialize rooms list (or load it from the file if it exists)
$rooms = [];
if (file_exists($roomsFile)) {
    $rooms = json_decode(file_get_contents($roomsFile), true) ?? [];
}

// Check if the user clicked the "Exit" button to leave the room
if (isset($_POST['exit'])) {
    // Check if session data exists to identify the room and user
    if (isset($_SESSION['room_code']) && isset($_SESSION['nickname'])) {
        $code = $_SESSION['room_code'];
        $nick = $_SESSION['nickname'];

        // Check if the room exists
        if (isset($rooms[$code])) {
            // Check if the user is the host
            if ($rooms[$code]['host'] === $nick) {
                // Host is leaving, delete the entire room
                unset($rooms[$code]);
                echo "<h1>Room deleted. Host has left.</h1>";
            } else {
                // Participant is leaving, remove them from the participants list
                $rooms[$code]['participants'] = array_filter($rooms[$code]['participants'], function($participant) use ($nick) {
                    return $participant !== $nick;
                });
                echo "<h1>You have left the room.</h1>";
            }

            // Save the updated room data back to the JSON file
            file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
        }
    }

    // Clear the session and destroy it
    session_unset(); // Clear all session variables
    session_destroy(); // Destroy the session

    // Redirect to the index page
    header("Location: index.php");
    exit;
}

// Check if session already has room data
if (isset($_SESSION['room_code'])) {
    // If session exists, retrieve the room code and nickname from the session
    $code = $_SESSION['room_code'];
    $nick = $_SESSION['nickname'];

    // Verify that the room exists
    if (!isset($rooms[$code])) {
        // If the room doesn't exist (e.g., was deleted), clear the session and show an error
        session_unset(); // Clear all session variables
        session_destroy(); // Destroy the session
        echo "<h1>Error: Room does not exist</h1>";
        exit;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the nickname, code, and host flag from the POST request
    $nick = isset($_POST['nick']) ? htmlspecialchars($_POST['nick']) : '';
    $code = isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '';
    $host = isset($_POST['host']) ? htmlspecialchars($_POST['host']) : '';

    if ($host == '1') {
        // Host a new room
        if (!isset($rooms[$code])) {
            // If the room doesn't exist, create a new room with the host's nickname
            $rooms[$code] = [
                'host' => $nick,
                'participants' => [$nick], // Add the host to the participants list
            ];
        } else {
            echo "<h1>Error: Room already exists</h1>";
        }
    } else if ($host == '0') {
        // Join an existing room
        if (isset($rooms[$code])) {
            // Add the participant's nickname to the room if it exists
            if (!in_array($nick, $rooms[$code]['participants'])) {
                $rooms[$code]['participants'][] = $nick;
            } else {
                echo "<h1>Error: You have already joined this room.</h1>";
            }
        } else {
            echo "<h1>Error: Room does not exist</h1>";
        }
    }

    // Save the room data to the JSON file
    file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

    // Save the room code and nickname to the session for future page loads
    $_SESSION['room_code'] = $code;
    $_SESSION['nickname'] = $nick;

} else {
    echo "No data received.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OST - Joined Room</title>
    <style>

        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@100;400&display=swap');

        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #000; /* Dark background for contrast */
            color: #fff; /* Light text color for readability */
            text-align: center;
            font-family: "Lato", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        #table {
            width: 80vmin;
            height: 80vmin;
            background-color: #4caf50; /* Poker table green */
            border-radius: 50%; /* Circular table */
            position: relative; /* Position for inner elements */
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px; /* Add margin for spacing */
        }

        #deck {
            width: 20vmin; /* Central deck size */
            height: 20vmin;
            background-color: #444; /* Darker background for deck */
            color: #fff; /* Light text color for deck */
            border: 2px solid #007bff;
            border-radius: 10px; /* Rounded edges for deck */
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .player {
            position: absolute;
            width: 20vmin;
            height: 20vmin;
            background-color: #222; /* Darker player area background */
            color: #fff; /* Light text color for players */
            border: 1px solid #444; /* Dark border for players */
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        /* Positions for players around the table */
        .player1 { top: 5%; left: 50%; transform: translate(-50%, -50%); }
        .player2 { top: 50%; left: 95%; transform: translate(-50%, -50%); }
        .player3 { bottom: 5%; left: 50%; transform: translate(-50%, 50%); }
        .player4 { top: 50%; left: 5%; transform: translate(-50%, -50%); }
        
        .nickname {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div>
    <h1>Welcome <?php echo htmlspecialchars($nick); ?>!</h1>
    <p>You're in room code: <?php echo htmlspecialchars($code); ?></p>

    <form method="POST" action="joined.php">
        <button type="submit" name="exit">Exit Room</button>
    </form>
</div>

<div id="table">
    <div id="deck">Deck</div>

    <!-- Player Positions -->
    <div class="player player1">
        <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][0] ?? ''); ?></div>
        <div class="participantSquare">Participant Actions</div>
    </div>
    <div class="player player2">
        <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][1] ?? ''); ?></div>
        <div class="participantSquare">Participant Actions</div>
    </div>
    <div class="player player3">
        <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][2] ?? ''); ?></div>
        <div class="participantSquare">Participant Actions</div>
    </div>
    <div class="player player4">
        <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][3] ?? ''); ?></div>
        <div class="participantSquare">Participant Actions</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Function to check room status and participants every 4 seconds
function checkRoomStatus() {
    $.ajax({
        url: 'check_room.php',
        method: 'GET',
        success: function(response) {
            if (response.exists) {
                // Update the squares with new data
                response.participants.forEach(function(participant, index) {
                    if (index < 4) { // Update the first four participants
                        $('.nickname').eq(index).text(participant);
                    }
                });
            } else {
                // Handle room not existing (e.g., redirect or show message)
                alert("Room no longer exists.");
                window.location.href = 'index.php'; // Redirect to the index page
            }
        },
        error: function() {
            console.error("Error checking room status.");
        }
    });
}

// Check room status every 4 seconds
setInterval(checkRoomStatus, 4000);
</script>
</body>
</html>
