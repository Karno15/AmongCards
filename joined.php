<?php
session_start();

$roomsFile = 'rooms.json';
$rooms = file_exists($roomsFile) ? json_decode(file_get_contents($roomsFile), true) ?? [] : [];

if (isset($_POST['exit']) && isset($_SESSION['room_code'], $_SESSION['nickname'])) {
    $code = $_SESSION['room_code'];
    $nick = $_SESSION['nickname'];

    if (isset($rooms[$code])) {
        if ($rooms[$code]['host'] === $nick) {
            unset($rooms[$code]);
            $info = "Host has left the room. Room closed.";
        } else {
            $rooms[$code]['participants'] = array_filter($rooms[$code]['participants'], fn($participant) => $participant !== $nick);
            $info = "You have left the room.";
        }
        file_put_contents($roomsFile, json_encode($rooms, JSON_PRETTY_PRINT));
    }
    session_unset();
    session_destroy();
    header("Location: index.php?info=" . $info);
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

                        // If cards have already been drawn, load them immediately
                        if (response.drawn) {
                            loadInitialCards();
                        } else {
                            clearAllBoards();
                        }
                    } else {
                        window.location.href = 'index.php';
                    }
                }).fail(function() {
                    console.error("Error checking room status.");
                });
            }


            function clearAllBoards() {
                $('.participantSquare').empty();
                $('#messages').empty();
            }

            // Draw cards on button click
            $('#drawCards').click(function() {
                $.post('draw_cards.php', {}, function(data) {
                    clearAllBoards();

                    // Check if there's an error in the response
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Display the random "table" value
                    $('#table-value').text(data.table);

                    setTimeout(function() {
                        $('#messages').text(data.message);
                    }, 4000);


                    const currentUser = '<?php echo htmlspecialchars($nick); ?>'; // Store the current user's nickname

                    // Iterate over participants to display drawn cards
                    $.each(data.participants, function(participant, cards) {
                        let playerPosition = 0; // Track the player position

                        // Find the index of the current participant for displaying their cards
                        $('.nickname').each(function(index) {
                            if (participant === $(this).text().trim()) {
                                playerPosition = index; // Store the player position
                            }
                        });

                        // Add the cards for the participant
                        if (participant === currentUser) {
                            $.each(cards, function(i, card) {
                                const cardImage = $('<img>', {
                                    src: card + ".svg",
                                    alt: 'Card',
                                    class: 'card'
                                });
                                $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardImage); // Show actual cards for current user
                            });
                        } else {
                            // Show the back of the card for others
                            for (let i = 0; i < 5; i++) {
                                const cardBack = $('<img>', {
                                    src: 'back.svg',
                                    alt: 'Card Back',
                                    class: 'card'
                                });
                                $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardBack);
                            }
                        }
                    });
                }, 'json');
            });

            // Function to load cards if they have already been drawn
            function loadInitialCards() {
                $.post('draw_cards.php', {}, function(data) {
                    // Clear participant squares before displaying new cards
                    $('.participantSquare').empty();

                    // Check if there's an error in the response
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    $('#table-value').text(data.table);
                    setTimeout(function() {
                        $('#messages').text(data.message);
                    }, 4000);

                    const currentUser = '<?php echo htmlspecialchars($nick); ?>'; // Store the current user's nickname

                    // Iterate over participants to display drawn cards
                    $.each(data.participants, function(participant, cards) {
                        let playerPosition = 0; // Track the player position

                        // Find the index of the current participant for displaying their cards
                        $('.nickname').each(function(index) {
                            if (participant === $(this).text().trim()) {
                                playerPosition = index; // Store the player position
                            }
                        });

                        // Add the cards for the participant
                        if (participant === currentUser) {
                            $.each(cards, function(i, card) {
                                const cardImage = $('<img>', {
                                    src: card + '.svg',
                                    alt: 'Card',
                                    class: 'card'
                                });
                                $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardImage); // Show actual cards for current user
                            });
                        } else {
                            // Show the back of the card for others
                            for (let i = 0; i < 5; i++) {
                                const cardBack = $('<img>', {
                                    src: 'back.svg',
                                    alt: 'Card Back',
                                    class: 'card'
                                });
                                $('.player' + (playerPosition + 1) + ' .participantSquare').append(cardBack);
                            }
                        }
                    });
                }, 'json');
            }

            // Reset game on button click
            $('#resetGame').click(function() {
                if (confirm("Are you sure you want to reset the game? This will delete all drawn cards.")) {
                    $.post('reset_game.php', {}, function(response) {
                        if (response.success) {
                            alert("Game has been reset.");
                            // Reload the room status
                            $('#table-value').text("");

                            $('#messages').text("");
                            checkRoomStatus();
                        } else {
                            alert("Error resetting the game: " + response.error);
                        }
                    }, 'json').fail(function() {
                        alert("Error communicating with the server.");
                    });
                }
            });

            // Check room status initially and every 4 seconds
            checkRoomStatus();
            setInterval(checkRoomStatus, 4000);
        });
    </script>

</body>

</html>