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
            // Remove the user from the participants list if they are a participant
            if (in_array($nick, $rooms[$code]['participants'])) {
                $rooms[$code]['participants'] = array_filter($rooms[$code]['participants'], fn($participant) => $participant !== $nick);
            }
            // Remove the user from the spectators list if they are a spectator
            if (in_array($nick, $rooms[$code]['spectators'])) {
                $rooms[$code]['spectators'] = array_filter($rooms[$code]['spectators'], fn($spectator) => $spectator !== $nick);
            }

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
            $rooms[$code] = ['host' => $nick, 'participants' => [$nick], 'spectators' => []];
        }
    } elseif ($host == '0') {
        if (!isset($rooms[$code])) {
            $info = "Room doesn't exist";
            session_unset();
            session_destroy();
            header("Location: index.php?info=" . $info);
            exit;
        } else {
            // Check if the game is ongoing (flagged as 'drawn')
            if (isset($rooms[$code]['drawn']) && $rooms[$code]['drawn'] === true) {
                // Game is ongoing, add to spectators if not already a participant or spectator
                if (!in_array($nick, $rooms[$code]['participants']) && !in_array($nick, $rooms[$code]['spectators'])) {
                    $rooms[$code]['spectators'][] = $nick;
                }
            } else {
                // Check if the room is already full
                if (count($rooms[$code]['participants']) < 4) {
                    // Only add to participants if not already in
                    if (!in_array($nick, $rooms[$code]['participants'])) {
                        $rooms[$code]['participants'][] = $nick;
                    } else {
                        $info = "Error: You have already joined this room.";
                        session_unset();
                        session_destroy();
                        header("Location: index.php?info=" . $info);
                    }
                } else {
                    // Room is full, add to spectators if not already a participant
                    if (!in_array($nick, $rooms[$code]['participants']) && !in_array($nick, $rooms[$code]['spectators'])) {
                        $rooms[$code]['spectators'][] = $nick;
                    }
                }
            }
        }
    }

    file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));

    $_SESSION['room_code'] = $code;
    $_SESSION['nickname'] = $nick;
} else {
    echo "No data received.";
    $info = 'No access';
    header("Location: index.php?info=" . $info);
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
            min-height: 10vw;
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
            padding-top: 5px;
            font-weight: bold;
            height: 1.8vw;
        }

        .card {
            height: 5vw;
            width: 3.23vw;
        }

        .card.selected {
            outline: 2px solid blue;
            transform: translateY(-10px);
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
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
            <button id="drawCards" disabled>Draw Cards</button>
            <button id="resetGame" disabled>Reset Game</button>
        <?php endif; ?>
        <button id="join" disabled>Join game</button>
        <h3>Spectators:</h3>
        <div id="spectators"></div>
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
                table_cards: [],
                table_status: '',
                spectators: [] // Add this line
            };

            function checkRoomStatus() {
                $.get('check_room.php', function(response) {
                    if (response.message != 'Room does not exist.') {
                        $('#host-display').text(response.host);

                        const currentParticipants = response.participants;
                        const currentSpectators = response.spectators; // Get current spectators

                        // Clear previous participant displays
                        $('.nickname').text('');

                        // Loop through each participant using their keys
                        $.each(currentParticipants, function(index, nickname) {
                            // Update the corresponding participant slot if available
                            const nicknameSlot = $('.nickname').eq(index);
                            if (nicknameSlot.length) {
                                nicknameSlot.text(nickname);
                            }
                        });

                        // Update spectators display
                        updateSpectators(currentSpectators);

                        // Check if there are at least 2 players to enable buttons
                        if (Object.keys(currentParticipants).length >= 2) {
                            $('#drawCards').prop('disabled', false);
                            $('#resetGame').prop('disabled', false);
                        } else {
                            $('#drawCards').prop('disabled', true);
                            $('#resetGame').prop('disabled', true);
                        }

                        // Compare current state with previous state
                        const hasTurnChanged = previousState.current_turn !== response.current_turn;
                        const hasMessageChanged = previousState.message !== response.message;
                        const hasCardsChanged = JSON.stringify(previousState.cards) !== JSON.stringify(response.cards);
                        const hasTableStatusChanged = previousState.table_status !== response.table;

                        if (hasTurnChanged || hasMessageChanged || hasCardsChanged || hasTableStatusChanged) {
                            if (hasTurnChanged) {
                                previousState.current_turn = response.current_turn;
                                if (response.current_turn) {
                                    highlightCurrentTurn(response.current_turn);
                                } else {
                                    clearHighlights();
                                }
                            }

                            if (hasMessageChanged) {
                                $('#messages').text(response.message);
                                previousState.message = response.message;
                            }

                            if (hasCardsChanged) {
                                clearAllCards();
                                updateParticipantCards(response.cards, response.current_turn);
                                previousState.cards = response.cards;
                            }

                            if (hasTableStatusChanged) {
                                updateTableStatus(response.table);
                                previousState.table_status = response.table;
                            }
                        }
                    } else {
                        window.location.href = 'index.php';
                    }
                }).fail(function() {
                    console.error("Error checking room status.");
                });
            }

            function updateSpectators(spectators) {
                $('#spectators').empty(); // Clear the existing spectators list
                if (spectators.length > 0) {
                    $.each(spectators, function(index, spectator) {
                        console.log(spectator);
                        $('#spectators').append('<div>' + spectator + '</div>');
                    });
                } else {
                    $('#spectators').append('<div>No spectators.</div>');
                }
            }



            function clearAllCards() {
                $('.participantSquare').empty();
            }

            function clearAllBoards() {
                clearAllCards();
                $('#messages').empty();
                clearHighlights();
            }

            function clearHighlights() {
                $('.player').removeClass('highlight');
            }

            function highlightCurrentTurn(currentTurn) {
                clearHighlights();
                if (currentTurn) {
                    $('.player' + currentTurn).addClass('highlight');
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

                    const participantSquare = $('.player' + (playerPosition + 1) + ' .participantSquare');
                    participantSquare.empty();

                    if (participant === currentUser) {
                        const isUserTurn = currentTurn === playerPosition + 1;

                        // Add actual cards for the current user with clickable selection effect
                        $.each(cardList, function(i, card) {
                            const cardImage = $('<img>', {
                                src: card + ".svg",
                                alt: 'Card',
                                class: 'card'
                            });

                            // Click to toggle selection only if it's the user's turn
                            cardImage.click(function() {
                                if (isUserTurn) {
                                    $(this).toggleClass('selected');
                                    updatePutButtonState();
                                }
                            });

                            participantSquare.append(cardImage);
                        });

                        // Create a container div for the buttons
                        const buttonContainer = $('<div>', {
                            class: 'button-container'
                        });

                        // Add PUT and CALL buttons for the current player
                        const putButton = $('<button>', {
                            text: 'PUT',
                            id: 'putButton',
                            click: handlePutButtonClick // Add event handler
                        });

                        const callButton = $('<button>', {
                            text: 'CALL',
                            id: 'callButton',
                            click: handleCallButtonClick // Add event handler for CALL button
                        });

                        // Append buttons to the button container and the container to the participantSquare
                        buttonContainer.append(putButton, callButton);
                        participantSquare.append(buttonContainer);

                        // Show or hide buttons based on the user's turn
                        if (isUserTurn) {
                            putButton.show();
                            callButton.show();
                            updatePutButtonState();
                        } else {
                            putButton.hide();
                            callButton.hide();
                        }

                    } else {
                        // Show card backs for other participants
                        for (let i = 0; i < cardList.length; i++) {
                            const cardBack = $('<img>', {
                                src: 'back.svg',
                                alt: 'Card Back',
                                class: 'card'
                            });
                            participantSquare.append(cardBack);
                        }
                    }
                });
            }

            function handleCallButtonClick() {
                $.post('call.php', {}, function(response) {
                    checkRoomStatus();
                    console.log('CALL action successful.');
                }).fail(function() {
                    console.log("Error communicating with the server.");
                });
            }

            // Function to enable/disable PUT button based on selected cards
            function updatePutButtonState() {
                const selectedCards = $('.card.selected');
                $('#putButton').prop('disabled', (selectedCards.length === 0 || selectedCards.length > 3));
            }

            function handlePutButtonClick() {
                // Collect selected cards
                const selectedCards = $('.card.selected').map(function() {
                    return $(this).attr('src').replace(".svg", "");
                }).get();

                const currentUser = '<?php echo htmlspecialchars($nick); ?>';

                // AJAX request to put_cards.php
                $.post('put_cards.php', {
                    current_player: currentUser,
                    cards: selectedCards
                }, function(response) {
                    if (response.success) {
                        checkRoomStatus(); // Refresh room status to update UI
                    } else {
                        alert(response.error || 'An error occurred while putting cards.');
                    }
                }, 'json').fail(function() {
                    alert("Error communicating with the server.");
                });
            }


            function updateTableStatus(tableStatus) {
                $('#table-value').text(tableStatus);
            }

            function resetGame() {
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

            function drawCards() {
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

                    updateParticipantCards(data.cards, data.current_turn);
                    currentTurnNumber = data.current_turn;
                    highlightCurrentTurn();
                }, 'json').fail(function() {
                    alert("Error communicating with the server.");
                });

                checkRoomStatus();
            }

            $('#drawCards').click(function() {
                drawCards();
            });

            $('#resetGame').click(function() {
                if (confirm("Are you sure you want to reset the game? This will delete all drawn cards.")) {
                    resetGame();
                }
            });
            // Initial status check
            checkRoomStatus();
            setInterval(checkRoomStatus, 4000);
        });
    </script>


</body>

</html>