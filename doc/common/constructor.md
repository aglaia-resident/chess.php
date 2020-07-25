# Constructor

## Example 1

```php
<?php
use \Ryanhs\Chess\Chess;

$chess = new Chess();
```

## Example 2  - (with fen as start)

```php
<?php
use \Ryanhs\Chess\Chess;

$chess = new Chess('fen goes here');
```

## Example 3  - (load fen):

```php
<?php
use \Ryanhs\Chess\Chess;

$chess = new Chess;
$chess->load('4rrk1/8/p1pR4/1p6/1PPKNq2/3P1p2/PB5n/R2Q4 b - - 6 40');
```

## Example 4 - go back to default position

```php
<?php
use \Ryanhs\Chess\Chess;

$chess = new Chess;
$chess->load('4rrk1/8/p1pR4/1p6/1PPKNq2/3P1p2/PB5n/R2Q4 b - - 6 40');
.
.
.
$chess->reset();
```
