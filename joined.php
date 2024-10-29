<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

// Check if the user has requested to exit and is in a session
if (isset($_POST['exit']) && isset($_SESSION['room_code'], $_SESSION['nickname'])) {
    $code = $_SESSION['room_code'];
    $nick = $_SESSION['nickname'];

    if (isset($rooms[$code])) {
        // If the user is the host, remove the entire room
        if ($rooms[$code]['host'] === $nick) {
            unset($rooms[$code]);
            $info = "Host has left the room. Room closed.";
        } else {
            // Remove the user from the participants list
            $rooms[$code]['participants'] = array_filter($rooms[$code]['participants'], fn($participant) => $participant !== $nick);
            // Also remove the user's cards if they exist
            unset($rooms[$code]['cards'][$nick]);
            $info = "You have left the room.";
        }

        // Save the updated room data to the file
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
    }
    
    // Clear the session for the user who exited
    session_unset();
    session_destroy();
    
    // Redirect back to the main page with an informational message
    header("Location: index.php?info=" . urlencode($info)); // Use urlencode to safely pass the message
    exit;
}
if (isset($_SESSION['room_code'], $_SESSION['nickname'])) {
    $code = $_SESSION['room_code'];
    $nick = $_SESSION['nickname'];

    if (!isset($rooms[$code])) {
        session_unset();
        session_destroy();
        $info = "Room does not exist";
        header("Location: index.php?info=" . $info);
        exit;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nick = htmlspecialchars($_POST['nick'] ?? '');
    $code = htmlspecialchars($_POST['code'] ?? '');
    $host = htmlspecialchars($_POST['host'] ?? '');

    if ($host == '1') {
        if (isset($rooms[$code])) {
            $info = "Room already exists";
            header("Location: index.php?info=" . $info);
            exit;
        } else {
            $rooms[$code] = ['host' => $nick, 'participants' => [$nick]];
        }
    } elseif ($host == '0') {
        if (!isset($rooms[$code])) {
            $info = "Room doesn't exist";
            session_unset();
            session_destroy();
            header("Location: index.php?info=" . $info);
            exit;
        } else {
            if (!in_array($nick, $rooms[$code]['participants'])) {
                $rooms[$code]['participants'][] = $nick;
            } else {
                echo "<h1>Error: You have already joined this room.</h1>";
            }
        }
    }

    file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>OST - Joined Room</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@100;400&display=swap');

        body {
            font-family: "Lato", sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #000;
            color: #fff;
            text-align: center;
            font-weight: 400;
            font-style: normal;
        }

        #table {
            width: 50vw;
            height: 28vw;
            background-color: #4caf50;
            border-radius: 50%;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #deck {
            width: 9vw;
            height: 9vw;
            background-color: #444;
            color: #fff;
            border: 0.2vw solid #007bff;
            border-radius: 1vw;
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .player {
            position: absolute;
            width: 18vw;
            height: 9vw;
            background-color: #222;
            color: #fff;
            border: 0.1vw solid #444;
            border-radius: 0.5vw;
        }

        .player1 {
            top: 4%;
            left: 50%;
            transform: translate(-50%, -90%);
        }

        .player2 {
            top: 50%;
            left: 95%;
            transform: translate(-5%, -50%);
        }

        .player3 {
            bottom: 4%;
            left: 50%;
            transform: translate(-50%, 90%);
        }

        .player4 {
            top: 50%;
            left: 5%;
            transform: translate(-95%, -50%);
        }

        .nickname {
            padding-top: 15px;
            font-weight: bold;
            height: 1.8vw;
        }

        .card {
            height: 5vw;
            width: 3.23vw;
        }

        .card:hover {
            outline: 2px solid yellow;
        }

        #infos {
            position: absolute;
            top: 0;
            left: 2vw;
        }

        #table-status {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-weight: bold;
            z-index: 10;
            font-size: 20pt;
        }

        #messages {
            position: absolute;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-weight: bold;
            z-index: 10;
        }

        .highlight {
            border: 2px solid yellow;
            box-shadow: 0 0 10px yellow;
        }
    </style>
</head>

<body>
    <div id="infos">
        <p>You're in room code: <?php echo htmlspecialchars($code); ?></p>

        <form method="POST" action="joined.php">
            <button type="submit" name="exit">Exit Room</button>

        </form>
        <?php if ($_SESSION['nickname'] === $rooms[$code]['host']): ?>
            <button id="drawCards">Draw Cards</button>
            <button id="resetGame">Reset Game</button>
        <?php endif; ?>
    </div>


    <div id="table">

        <div id="deck"><img src="back.svg" alt="Card Back" class='card'></div>


        <p id="table-status">Table: <span id="table-value"></span></p>
        <h2 id="messages">Info will be shown here.</h2>
        <!-- Player Positions -->
        <div class="player player1">
            <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][0] ?? ''); ?></div>
            <div class="participantSquare"></div>
        </div>
        <div class="player player2">
            <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][1] ?? ''); ?></div>
            <div class="participantSquare"></div>
        </div>
        <div class="player player3">
            <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][2] ?? ''); ?></div>
            <div class="participantSquare"></div>
        </div>
        <div class="player player4">
            <div class="nickname"><?php echo htmlspecialchars($rooms[$code]['participants'][3] ?? ''); ?></div>
            <div class="participantSquare"></div>
        </div>
    </div>

    <!-- Button to Draw Cards -->
    <script>
        $(document).ready(function() {
            let currentTurnNumber = 0;

            // Store previous state for comparison
            let previousState = {
                current_turn: null,
                message: '',
                cards: {},
                participants: [],
                table_cards: [], // Track table cards
                table_status: '' // Track table status
            };

            function checkRoomStatus() {
                $.get('check_room.php', function(response) {
                    if (response.exists) {
                        $('#host-display').text(response.host);

                        const currentParticipants = response.participants;
                        $('.nickname').each(function(index) {
                            if (index < currentParticipants.length) {
                                $(this).text(currentParticipants[index]);
                            } else {
                                $(this).text('');
                            }
                        });

                        // Compare current state with previous state
                        const hasTurnChanged = previousState.current_turn !== response.current_turn;
                        const hasMessageChanged = previousState.message !== response.message;
                        const hasCardsChanged = JSON.stringify(previousState.cards) !== JSON.stringify(response.cards);
                        const hasTableStatusChanged = previousState.table_status !== response.table; // Corrected line for table status

                        // Update current turn, message, and table status if they have changed
                        if (hasTurnChanged || hasMessageChanged || hasCardsChanged || hasTableStatusChanged) {
                            if (hasTurnChanged) {
                                previousState.current_turn = response.current_turn;

                                if (response.current_turn) {
                                    highlightCurrentTurn(response.current_turn); // Highlight the current player
                                } else {
                                    clearHighlights(); // Clear highlights if no current turn
                                }
                            }

                            if (hasMessageChanged) {
                                $('#messages').text(response.message);
                                previousState.message = response.message; // Update previous state
                            }

                            // Update cards for all participants if they have changed
                            if (hasCardsChanged) {
                                // Clear previous cards
                                clearAllCards();

                                // Update the participant cards
                                updateParticipantCards(response.cards, response.current_turn);
                                previousState.cards = response.cards; // Update previous state
                            }

                            // Update table status if it has changed
                            if (hasTableStatusChanged) {
                                updateTableStatus(response.table); // Use response.table to update the status
                                previousState.table_status = response.table; // Update previous state
                            }
                        }
                    } else {
                        window.location.href = 'index.php';
                    }
                }).fail(function() {
                    console.error("Error checking room status.");
                });
            }

            function clearAllCards() {
                $('.participantSquare').empty(); // Clear all player cards
            }

            function clearAllBoards() {
                clearAllCards(); // Clear cards
                $('#messages').empty(); // Clear messages
                clearHighlights(); // Clear highlights
            }

            function clearHighlights() {
                $('.player').removeClass('highlight'); // Clear all player highlights
            }

            function highlightCurrentTurn(currentTurn) {
                clearHighlights(); // Clear previous highlights
                if (currentTurn) {
                    $('.player' + currentTurn).addClass('highlight'); // Highlight the current player based on the turn number
                }
            }

            function updateParticipantCards(cards, currentTurn) {
                const currentUser = '<?php echo htmlspecialchars($nick); ?>';

                $.each(cards, function(participant, cardList) {
                    let playerPosition = 0;
                    $('.nickname').each(function(index) {
                        if (participant === $(this).text().trim()) {
                            playerPosition = index;
                        }
                    });

                    // Clear previous cards for this player
                    $('.player' + (playerPosition + 1) + ' .participantSquare').empty();

                    if (participant === currentUser) {
                        // Show actual cards for the current user
                        $.each(cardList, function(i, card) {
                            const cardImage = $('<img>', {
                                src: card + ".svg",
                                alt: 'Card',
                                class: 'card'
                            });
                            $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardImage);
                        });
                    } else {
                        // Show card backs for other participants
                        for (let i = 0; i < cardList.length; i++) {
                            const cardBack = $('<img>', {
                                src: 'back.svg',
                                alt: 'Card Back',
                                class: 'card'
                            });
                            $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardBack);
                        }
                    }
                });
            }

            // Function to update table status display
            function updateTableStatus(tableStatus) {
                const tableValueDisplay = $('#table-value'); // Update the table status
                tableValueDisplay.text(tableStatus); // Set the text to the current table status (King, Ace, Queen)
            }
            $('#drawCards').click(function() {
                $.post('draw_cards.php', {}, function(data) {
                    clearAllBoards();

                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    $('#table-value').text(data.table);
                    if (data.message) {
                        $('#messages').text(data.message);
                    }

                    // Load cards for participants
                    updateParticipantCards(data.cards, data.current_turn);

                    // Highlight current turn
                    currentTurnNumber = data.current_turn;
                    highlightCurrentTurn();
                }, 'json');
            });

            $('#resetGame').click(function() {
                if (confirm("Are you sure you want to reset the game? This will delete all drawn cards.")) {
                    $.post('reset_game.php', {}, function(response) {
                        if (response.success) {
                            alert("Game has been reset.");
                            $('#table-value').text("");
                            $('#messages').text("");
                            clearAllBoards();
                            checkRoomStatus();
                        } else {
                            alert("Error resetting the game: " + response.error);
                        }
                    }, 'json').fail(function() {
                        alert("Error communicating with the server.");
                    });
                }
            });

            // Initial status check
            checkRoomStatus();
            setInterval(checkRoomStatus, 4000);
        });
    </script>


</body>

</html>