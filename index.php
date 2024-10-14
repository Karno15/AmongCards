<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ost</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@100;400&display=swap');

        BODY {
            margin: auto 0;
            text-align: center;
            background-color: black;
            color: white;
            font-family: "Lato", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        div {
            outline: 1px solid black;
        }

        #main {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            align-content: center;
            width: 65%;
            margin-left: auto;
            margin-right: auto;
        }

        #joinContainer,
        #hostContrainer {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            padding: 50px;
            margin: 20px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
    <h1>Ost</h1>
    <div id="main">
        <div id="joinContainer">
            <form action='joined.php' method='post'>
                JOIN<br><br>
                Nick:<br>
                <input type="text" name="nick" id="join"><br></input>
                Code:<br>
                <input type="number" name="code" id="codeJoin"></input><br>
                <input type="hidden" name="host" id="hostJoin" value='0'><br><br>
                <button id='joinButton'>JOIN</button>
            </form>
        </div>
        <div id="hostContrainer">
            <form action='joined.php' method='post'>
                HOST<br><br>
                Nick:<br>
                <input type="text" name="nick" id="host"><br></input>
                Code:<br>
                <input type="number" name="code" id="codeHost"></input><br>
                <input type="hidden" name="host" id="hostHost" value='1'><br><br>
                <button id='hostButton'>HOST</button>
            </form>
        </div>
    </div>
</body>

</html>