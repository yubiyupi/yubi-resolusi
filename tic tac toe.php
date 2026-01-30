<?php
session_start();

function initGame() {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['player'] = 'X';
    $_SESSION['winner'] = '';
    $_SESSION['winLine'] = [];
}

if (!isset($_SESSION['board'])) {
    initGame();
    $_SESSION['mode'] = '';
    $_SESSION['difficulty'] = '';
}

if (isset($_POST['mode'])) {
    $_SESSION['mode'] = $_POST['mode'];
}

if (isset($_POST['difficulty'])) {
    $_SESSION['difficulty'] = $_POST['difficulty'];
}

if (isset($_POST['reset'])) {
    initGame();
}

if (isset($_POST['menu'])) {
    initGame();
    $_SESSION['mode'] = '';
    $_SESSION['difficulty'] = '';
}


$wins = [
    [0,1,2],[3,4,5],[6,7,8],
    [0,3,6],[1,4,7],[2,5,8],
    [0,4,8],[2,4,6]
];

function checkWinner($b) {
    global $wins;
    foreach ($wins as $w) {
        if ($b[$w[0]] && $b[$w[0]] == $b[$w[1]] && $b[$w[1]] == $b[$w[2]]) {
            $_SESSION['winLine'] = $w;
            return $b[$w[0]];
        }
    }
    if (!in_array('', $b)) return 'draw';
    return '';
}

// === AI MINIMAX (SULIT) ===
function minimax($board, $isMax) {
    $result = checkWinner($board);
    if ($result == 'O') return 1;
    if ($result == 'X') return -1;
    if ($result == 'draw') return 0;

    $scores = [];

    foreach ($board as $i => $v) {
        if ($v == '') {
            $board[$i] = $isMax ? 'O' : 'X';
            $scores[] = minimax($board, !$isMax);
            $board[$i] = '';
        }
    }
    return $isMax ? max($scores) : min($scores);
}

function bestMove($board) {
    $bestScore = -INF;
    $move = null;
    foreach ($board as $i => $v) {
        if ($v == '') {
            $board[$i] = 'O';
            $score = minimax($board, false);
            $board[$i] = '';
            if ($score > $bestScore) {
                $bestScore = $score;
                $move = $i;
            }
        }
    }
    return $move;
}

// === HANDLE MOVE ===
if (isset($_POST['cell']) && $_SESSION['winner'] == '') {
    $i = $_POST['cell'];

    if ($_SESSION['board'][$i] == '') {
        $_SESSION['board'][$i] = $_SESSION['player'];
        $_SESSION['winner'] = checkWinner($_SESSION['board']);
        $_SESSION['player'] = 'O';

        // CPU MOVE
        if ($_SESSION['mode'] == 'cpu' && $_SESSION['winner'] == '') {
            if ($_SESSION['difficulty'] == 'easy') {
                $empty = array_keys($_SESSION['board'], '');
                $cpu = $empty[array_rand($empty)];
            } else {
                $cpu = bestMove($_SESSION['board']);
            }
            $_SESSION['board'][$cpu] = 'O';
            $_SESSION['winner'] = checkWinner($_SESSION['board']);
            $_SESSION['player'] = 'X';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Tic Tac Toe</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap');

body {
    background: #050d12;
    color: #00ffe1;
    font-family: 'Orbitron', sans-serif;
    text-align: center;
}

.board {
    display: grid;
    grid-template-columns: repeat(3, 100px);
    gap: 8px;
    justify-content: center;
    margin: 20px auto;
    position: relative;
}

.cell {
    width: 100px;
    height: 100px;
    font-size: 42px;
    background: #0a1a22;
    color: #00ffe1;
    border: 2px solid #00ffe1;
    cursor: pointer;
    box-shadow: 0 0 10px #00ffe1;
}

.cell:disabled {
    cursor: default;
}

.win {
    color: #ff00ff;
}

.info {
    margin: 15px;
    font-size: 18px;
}

button {
    background: transparent;
    color: #00ffe1;
    border: 2px solid #00ffe1;
    padding: 8px 18px;
    margin: 5px;
    cursor: pointer;
    box-shadow: 0 0 8px #00ffe1;
}
</style>
</head>

<body>

<h1> TIC TAC TOE </h1>

<?php if ($_SESSION['mode'] == ''): ?>
<form method="post">
    <button name="mode" value="2p">👥 2 PLAYER</button>
    <button name="mode" value="cpu">👤 VS COMPUTER</button>
</form>

<?php elseif ($_SESSION['mode'] == 'cpu' && $_SESSION['difficulty'] == ''): ?>
<form method="post">
    <button name="difficulty" value="easy">🟢 MUDAH</button>
    <button name="difficulty" value="hard">🔴 SULIT</button>
</form>

<?php else: ?>

<div class="info">
<?php
if ($_SESSION['winner'] == 'draw') echo "⚠ DRAW";
elseif ($_SESSION['winner']) echo "🏆 WINNER: ".$_SESSION['winner'];
else echo "TURN: ".$_SESSION['player'];
?>
</div>

<form method="post">
<div class="board">
<?php foreach ($_SESSION['board'] as $i => $v): ?>
<button class="cell <?= in_array($i, $_SESSION['winLine']) ? 'win' : '' ?>"
    name="cell" value="<?= $i ?>"
    <?= $v || $_SESSION['winner'] ? 'disabled' : '' ?>>
    <?= $v ?>
</button>
<?php endforeach; ?>
</div>
</form>

<form method="post">
    <button name="reset"> RESET</button>
    <button name="menu"> MENU</button>
</form>


<?php endif; ?>

</body>
</html>
