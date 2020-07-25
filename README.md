# Chess.php

This project is a fork of [ryanhs/chess.php](https://github.com/ryanhs/chess.php), which is abandonned. chess.php itself was a port of [chess.js](https://github.com/jhlywa/chess.js). Main changes compared to the original ryanhs project are:
- It is compatible with PHP 7.4 without deprecations.
- PHP Unit has been updated to version 9
- The documentation has been imported right in the project

chess.php is a PHP chess library that is used for chess move
generation/validation, piece placement/movement, and check/checkmate/stalemate
detection - basically everything but the AI.

NOTE: this is a port of [chess.js](https://github.com/jhlywa/chess.js) for php  

[![Latest Stable Version](https://poser.pugx.org/ryanhs/chess.php/v/stable)](https://packagist.org/packages/ryanhs/chess.php)
[![Build Status](https://travis-ci.org/ryanhs/chess.php.svg?branch=master)](https://travis-ci.org/ryanhs/chess.php)
[![MIT License](https://poser.pugx.org/ryanhs/chess.php/license)](https://packagist.org/packages/ryanhs/chess.php)  

## Installation

use composer with `composer require aglaia-resident/chess.php`   
or put in your composer.json  
```
"require": {
	"aglaia-resident/chess.php": "^1.0"
}
```


if you doesn't know composer, its a really usefull tools in php for package management,   
try to learn composer [here](https://getcomposer.org/doc/00-intro.md)

## Example Code
The code below plays a complete game of chess ... randomly.

```php
<?php

require 'vendor/autoload.php';
use \Ryanhs\Chess\Chess;

$chess = new Chess();
while (!$chess->gameOver()) {
	$moves = $chess->moves();
	$move = $moves[mt_rand(0, count($moves) - 1)];
	$chess->move($move);
}

echo $chess->ascii() . PHP_EOL;
```

## Documentation Summary

### Common
- [Clear](doc/common/clear.md)
- [Constructor](doc/common/constructor.md)
- [Print Board (ASCII)](doc/common/print-board.md)

### FEN
- [FEN generation](doc/fen/generation.md)
- [FEN load](doc/fen/load.md)
- [FEN validation](doc/fen/validation.md)

### MISC
- [Square Color ?](doc/misc/square-color.md)
- [Header ?](doc/misc/header.md)
- [History ?](doc/misc/history.md)

### MOVE
- [Get all possible moves](doc/move/get-all-possible-moves.md)
- [Move](doc/move/move.md)
- [Undo :-p](doc/move/undo.md)

### PGN
- [PGN generation](doc/pgn/generation.md)
- [PGN load](doc/pgn/load.md)
- [PGN parse](doc/pgn/parse.md)
- [PGN validation](doc/pgn/validation.md)

### PIECE PLACEMENT
- [Get](doc/piece-placement/get.md)
- [Put](doc/piece-placement/put.md)
- [Remove](doc/piece-placement/remove.md)

### STATUS
- [Game Over ?](doc/status/game-over.md)
- [Half Moves Exceeded ?](doc/status/half-moves-exceeded.md)
- [Check ?](doc/status/check.md)
- [Checkmate ?](doc/status/checkmate.md)
- [Draw ?](doc/status/draw.md)
- [Stalemate ?](doc/status/stalemate.md)
- [Threefold Repetition ?](doc/status/threefold-repetition.md)
- [Insufficient Material ?](doc/status/insufficient-material.md)

