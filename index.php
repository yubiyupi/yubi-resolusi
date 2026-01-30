<?php
// Puzzle & solusi
$puzzle = [
    [5,3,0,0,7,0,0,0,0],
    [6,0,0,1,9,5,0,0,0],
    [0,9,8,0,0,0,0,6,0],
    [8,0,0,0,6,0,0,0,3],
    [4,0,0,8,0,3,0,0,1],
    [7,0,0,0,2,0,0,0,6],
    [0,6,0,0,0,0,2,8,0],
    [0,0,0,4,1,9,0,0,5],
    [0,0,0,0,8,0,0,7,9]
];

$solution = [
    [5,3,4,6,7,8,9,1,2],
    [6,7,2,1,9,5,3,4,8],
    [1,9,8,3,4,2,5,6,7],
    [8,5,9,7,6,1,4,2,3],
    [4,2,6,8,5,3,7,9,1],
    [7,1,3,9,2,4,8,5,6],
    [9,6,1,5,3,7,2,8,4],
    [2,8,7,4,1,9,6,3,5],
    [3,4,5,2,8,6,1,7,9]
];

$message = "";

if (isset($_POST['check'])) {
    $correct = true;
    $incomplete = false;

    for ($i = 0; $i < 9; $i++) {
        for ($j = 0; $j < 9; $j++) {

            if (!isset($_POST['cell'][$i][$j]) || $_POST['cell'][$i][$j] === '') {
                $incomplete = true;
                $correct = false;
            } elseif ($_POST['cell'][$i][$j] != $solution[$i][$j]) {
                $correct = false;
            }
        }
    }

    if ($incomplete) {
        $message = "⚠️ Masih ada kotak kosong!";
    } elseif ($correct) {
        $message = "🎉 Jawaban BENAR!";
    } else {
        $message = "❌ Masih ada yang salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sudoku PHP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Game Sudoku (PHP)</h1>

<form method="post">
<table class="sudoku">
<?php
for ($i=0; $i<9; $i++) {
    echo "<tr>";
    for ($j=0; $j<9; $j++) {
        $value = $puzzle[$i][$j];
        if ($value != 0) {
            echo "<td><input type='text' value='$value' disabled></td>";
        } else {
            $input = $_POST['cell'][$i][$j] ?? "";
            echo "<td><input type='number' name='cell[$i][$j]' min='1' max='9' value='$input'></td>";
        }
    }
    echo "</tr>";
}
?>
</table>

<button type="submit" name="check">Cek Jawaban</button>
</form>

<p class="message"><?= $message ?></p>

</body>
</html>
