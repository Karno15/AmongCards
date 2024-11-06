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
                $rooms[$code]['participants'] = array_values(array_filter($rooms[$code]['participants'], fn($participant) => $participant !== $nick));
            }
            // Remove the user from the spectators list if they are a spectator
            if (in_array($nick, $rooms[$code]['spectators'])) {
                $rooms[$code]['spectators'] = array_values(array_filter($rooms[$code]['spectators'], fn($spectator) => $spectator !== $nick));
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
        <button id="joinGame" disabled>Join game</button>
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
                spectators: []
            };

            // Flag to prevent multiple resets
            let isResetting = false;

            function checkRoomStatus() {
                $.get('check_room.php', function(response) {
                    if (response.message != 'Room does not exist.') {
                        $('#host-display').text(response.host);

                        const currentParticipants = response.participants;
                        const currentSpectators = response.spectators;

                        $('.nickname').text('');

                        // Loop through each participant using their keys
                        $.each(currentParticipants, function(index, nickname) {
                            const nicknameSlot = $('.nickname').eq(index);
                            if (nicknameSlot.length) {
                                nicknameSlot.text(nickname);
                            }
                        });

                        updateSpectators(currentSpectators);

                        // Enable/disable buttons based on participants count
                        if (Object.keys(currentParticipants).length >= 2) {
                            $('#resetGame').prop('disabled', false);
                        } else {
                            $('#resetGame').prop('disabled', true);
                        }

                        // Disable "Draw Cards" button if cards have been drawn
                        if (response.drawn) {
                            $('#drawCards').prop('disabled', true);
                        } else {
                            $('#drawCards').prop('disabled', false);
                        }

                        // If response.called is true, wait 2 seconds, then reveal last cards from reveal.php
                        if (response.called) {
                            setTimeout(revealLastCards, 2000);
                        }

                        // Compare state to decide on updates
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
                                $('#deck').find('img:not([src="back.svg"])').remove();
                                updateParticipantCards(response.cards, response.current_turn);
                                previousState.cards = response.cards;
                            }

                            if (hasTableStatusChanged) {
                                updateTableStatus(response.table);
                                previousState.table_status = response.table;
                            }

                        }
                        const currentUser = '<?php echo htmlspecialchars($nick); ?>';

                        // Enable "Join Game" button if current user is a spectator, not a participant,
                        // participants count is between 1 and 3, and "drawn" flag is false
                        if (currentSpectators.includes(currentUser) &&
                            !Object.values(currentParticipants).includes(currentUser) &&
                            Object.keys(currentParticipants).length >= 1 &&
                            Object.keys(currentParticipants).length <= 3 &&
                            response.drawn === false) {
                            $('#joinGame').show();
                            $('#joinGame').prop('disabled', false); // Show the Join Game button if conditions are met
                        } else {
                            $('#joinGame').hide(); // Hide the Join Game button otherwise
                        }
                    } else {
                        window.location.href = 'index.php';
                    }
                }).fail(function() {
                    console.error("Error checking room status.");
                });
            }

            function updateSpectators(spectators) {
                $('#spectators').empty();
                if (spectators.length > 0) {
                    $.each(spectators, function(index, spectator) {
                        $('#spectators').append('<div>' + spectator + '</div>');
                    });
                } else {
                    $('#spectators').append('<div>No spectators.</div>');
                }
            }

            function clearAllCards() {
                $('.participantSquare').empty();
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

                        $.each(cardList, function(i, card) {
                            const cardImage = $('<img>', {
                                src: card + ".svg",
                                alt: 'Card',
                                class: 'card'
                            });

                            cardImage.click(function() {
                                if (isUserTurn) {
                                    $(this).toggleClass('selected');
                                    updatePutButtonState();
                                }
                            });

                            participantSquare.append(cardImage);
                        });

                        const buttonContainer = $('<div>', {
                            class: 'button-container'
                        });
                        const putButton = $('<button>', {
                            text: 'PUT',
                            id: 'putButton',
                            click: handlePutButtonClick
                        });
                        const callButton = $('<button>', {
                            text: 'CALL',
                            id: 'callButton',
                            click: handleCallButtonClick
                        });

                        buttonContainer.append(putButton, callButton);
                        participantSquare.append(buttonContainer);

                        if (isUserTurn) {
                            putButton.show();
                            callButton.show();
                            updatePutButtonState();
                        } else {
                            putButton.hide();
                            callButton.hide();
                        }

                    } else {
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

            function updatePutButtonState() {
                const selectedCards = $('.card.selected');
                $('#putButton').prop('disabled', (selectedCards.length === 0 || selectedCards.length > 3));
            }

            function handlePutButtonClick() {
                const selectedCards = $('.card.selected').map(function() {
                    return $(this).attr('src').replace(".svg", "");
                }).get();

                const currentUser = '<?php echo htmlspecialchars($nick); ?>';

                $.post('put_cards.php', {
                    current_player: currentUser,
                    cards: selectedCards
                }, function(response) {
                    if (response.success) {
                        checkRoomStatus();
                    } else {
                        alert(response.error || 'An error occurred while putting cards.');
                    }
                }, 'json').fail(function() {
                    alert("Error communicating with the server.");
                });
            }

            function revealLastCards() {
                $.get('reveal.php', function(response) {
                    // Ensure response is treated as JSON
                    if (typeof response === "string") {
                        response = JSON.parse(response); // Parse response if it's in string format
                    }

                    // Check if last_cards exists and is an array
                    if (response.last_cards && Array.isArray(response.last_cards)) {
                        // Clear any existing cards in #deck except for the card back image
                        $('#deck').find('img:not([src="back.svg"])').remove();

                        // Append each of the last cards to the #deck div
                        response.last_cards.forEach(function(card) {
                            const cardImage = $('<img>', {
                                src: card + ".svg",
                                alt: 'Card',
                                class: 'card'
                            });
                            $('#deck').append(cardImage);
                        });
                        // Set a timeout to reset the game after 5 seconds, but only if the current player is the host
                        const currentPlayer = '<?php echo htmlspecialchars($nick); ?>'; // Replace with the variable holding the current player
                        const hostPlayer = response.host; // Replace with response.host or however the host info is accessed

                        if (currentPlayer === hostPlayer) {
                            // Only reset if not already resetting
                            if (!isResetting) {
                                isResetting = true; // Set the flag to true
                                setTimeout(function() {
                                    resetGame(); // Call resetGame after 5 seconds
                                }, 5000); // 5000 milliseconds = 5 seconds
                            }
                        }

                    } else {
                        console.error("Error: last_cards is missing or not an array.");
                    }
                }, 'json').fail(function() {
                    console.error("Error retrieving last cards from reveal.php.");
                });
            }

            function resetGame() {
                $.post('reset_game.php', {}, function(response) {
                    isResetting = false; // Reset the flag once the reset is complete
                    if (response.success) {
                        $('#table-value').text("");
                        $('#messages').text("");
                        clearAllBoards();
                        checkRoomStatus();
                        $('#deck').find('img:not([src="back.svg"])').remove();

                    } else {
                        alert("Error resetting the game: " + response.error);
                    }
                }, 'json').fail(function() {
                    alert("Error communicating with the server.");
                    isResetting = false; // Reset the flag on failure too
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

            function updateTableStatus(tableStatus) {
                $('#table-value').text(tableStatus);
            }

            function clearAllBoards() {
                clearAllCards();
                $('#messages').empty();
                clearHighlights();
            }

            $('#drawCards').click(function() {
                drawCards();
            });

            $('#resetGame').click(function() {
                if (confirm("Are you sure you want to reset the game? This will delete all drawn cards.")) {
                    if (!isResetting) { // Check if not already resetting
                        resetGame();
                    }
                }
            });

            // Handle "Join Game" button click
            $('#joinGame').click(function() {
                const currentUser = '<?php echo htmlspecialchars($nick); ?>';

                $.post('join_game.php', {
                    nickname: currentUser
                }, function(response) {
                    if (response.success) {
                        checkRoomStatus(); // Update the status immediately after joining
                    } else {
                        alert(response.error || 'An error occurred while joining the game.');
                    }
                }, 'json').fail(function() {
                    alert("Error communicating with the server.");
                });
            });


            // Initialize "Join Game" button as disabled
            $('#joinGame').hide();


            checkRoomStatus();
            setInterval(checkRoomStatus, 4000);
        });
    </script>


</body>

</html>