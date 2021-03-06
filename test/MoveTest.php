<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use \Ryanhs\Chess\Chess;

class MoveTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildMove()
    {
        $chess = new ChessPublicator();
        $chess->clear();
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'a2');
        $chess->put(['type' => Chess::KING, 'color' => Chess::WHITE], 'e7');
        $chess->put(['type' => Chess::KING, 'color' => Chess::BLACK], 'a7');
        $chess->put(['type' => Chess::QUEEN, 'color' => Chess::BLACK], 'f4');
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['a2'],
            Chess::SQUARES['a4'],
            Chess::BITS['NORMAL']
        ));
        
        $this->assertSame($move['piece'], Chess::PAWN);
        $this->assertSame($move['color'], $chess->turn());
        $this->assertSame($move['from'], Chess::SQUARES['a2']);
        $this->assertSame($move['to'], Chess::SQUARES['a4']);
        $this->assertSame($move['flags'], Chess::BITS['NORMAL']);
    }
    
    /**
     * @depends testBuildMove
     */
    public function testMakeMoveAndCheckHistory()
    {
        $chess = new ChessPublicator();
        $chess->clear();
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'a2');
        $chess->put(['type' => Chess::KING, 'color' => Chess::WHITE], 'e7');
        $chess->put(['type' => Chess::KING, 'color' => Chess::BLACK], 'a7');
        $chess->put(['type' => Chess::QUEEN, 'color' => Chess::BLACK], 'f4');
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['a2'],
            Chess::SQUARES['a4'],
            Chess::BITS['NORMAL']
        ));
        $chess->makeMovePublic($move);
        
        $lastHistory = $chess->getLastHistory();
        $this->assertSame($lastHistory['move'], $move);
        $this->assertSame($lastHistory['turn'], Chess::WHITE);
        $this->assertSame($lastHistory['kings'][Chess::WHITE], Chess::SQUARES['e7']);
        $this->assertSame($lastHistory['kings'][Chess::BLACK], Chess::SQUARES['a7']);
        $this->assertEquals($lastHistory['castling'][Chess::WHITE], 0);
        $this->assertEquals($lastHistory['castling'][Chess::BLACK], 0);
        $this->assertSame($lastHistory['halfMoves'], 0);
        $this->assertSame($lastHistory['moveNumber'], 1);
        
        // promotions
        $chess->load('8/P7/8/8/8/8/8/K6k w - - 0 1');
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['a7'],
            Chess::SQUARES['a8'],
            Chess::BITS['PROMOTION'],
            Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $this->assertSame($chess->fen(), 'Q7/8/8/8/8/8/8/K6k b - - 0 1');
    }
    
    public function testUndoMoveAndCheckHistory()
    {
        $chess = new ChessPublicator();
        $chess->clear();
        $chess->put(['type' => Chess::KING, 'color' => Chess::WHITE], 'a1');
        $chess->put(['type' => Chess::KING, 'color' => Chess::BLACK], 'h1');
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'a7');
        $fenStart = $chess->fen();
        
        // normal move
        $chess->load($fenStart);
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['a7'],
            Chess::SQUARES['a8'],
            Chess::BITS['PROMOTION'],
            Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenStart);
        
        // big pawn
        $chess->load($fenStart);
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'd2');
        $fenStart = $chess->fen();
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['d2'],
            Chess::SQUARES['d4'],
            Chess::BITS['BIG_PAWN'],
            Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenStart);
        
        // capture
        $chess->load($fenStart);
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::BLACK], 'e5');
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'd4');
        $fenStart = $chess->fen();
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['d4'],
            Chess::SQUARES['e5'],
            Chess::BITS['CAPTURE'],
            Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenStart);
        
        // en passant
        $chess->load($fenStart);
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::BLACK], 'g4');
        $chess->put(['type' => Chess::PAWN, 'color' => Chess::WHITE], 'h2');
        $fenTmp = $chess->fen();
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['h2'],
            Chess::SQUARES['h4'],
            Chess::BITS['NORMAL']//,
            //~ Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $fenTmp1 = $chess->fen();
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['g4'],
            Chess::SQUARES['h3'],
            Chess::BITS['EP_CAPTURE'],
            Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenTmp1);
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenTmp);
        $chess->remove('g4');
        $chess->remove('h2');
        $this->assertSame($chess->fen(), $fenStart);
        
        
        // castling king side
        $fenTmp = 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4';
        $chess->load($fenTmp);
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['e1'],
            Chess::SQUARES['g1'],
            Chess::BITS['KSIDE_CASTLE']//,
            //~ Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $this->assertSame($chess->fen(), 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/5N2/PPPP1PPP/RNBQ1RK1 b kq - 5 4');
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenTmp);
        $chess->load($fenStart);
        $this->assertSame($chess->fen(), $fenStart);
        
        // castling queen side
        $fenTmp = 'r3kb1r/pppq1ppp/2np1n2/1B2p2b/4P3/3P1N1P/PPPB1PP1/RN1QR1K1 b kq - 2 8';
        $chess->load($fenTmp);
        $move = (ChessPublicator::buildMovePublic(
            $chess->turn(),
            $chess->getBoard(),
            Chess::SQUARES['e8'],
            Chess::SQUARES['c8'],
            Chess::BITS['QSIDE_CASTLE']//,
            //~ Chess::QUEEN
        ));
        $chess->makeMovePublic($move);
        $this->assertSame($chess->fen(), '2kr1b1r/pppq1ppp/2np1n2/1B2p2b/4P3/3P1N1P/PPPB1PP1/RN1QR1K1 w - - 3 9');
        $chess->undoMovePublic();
        $this->assertSame($chess->fen(), $fenTmp);
        $chess->load($fenStart);
        $this->assertSame($chess->fen(), $fenStart);
    }
    
    public function testMoveToSAN()
    {
        $chess = new ChessPublicator();
    
        // normal pawn move
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['e2'],
                    Chess::SQUARES['e4'],
                    Chess::BITS['NORMAL']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'e4');
        
        // normal knight move
        $chess->makeMovePublic($move);
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['g8'],
                    Chess::SQUARES['f6'],
                    Chess::BITS['NORMAL']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Nf6');
        
        // normal pawn capture
        $chess->load('rnbqkbnr/ppp1pppp/8/3p4/4P3/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 2');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['e4'],
                    Chess::SQUARES['d5'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'exd5');
        
        // en passant capture
        $chess->load('rnbqkbnr/ppp2ppp/8/3Pp3/8/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['d5'],
                    Chess::SQUARES['e6'],
                    Chess::BITS['EP_CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'dxe6');
        
        // normal knight capture
        $chess->load('rnbqkb1r/ppp1pppp/5n2/3P4/8/5N2/PPPP1PPP/RNBQKB1R b KQkq - 2 3');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['f6'],
                    Chess::SQUARES['d5'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Nxd5');
        
        // promotion
        $chess->load('8/2KP4/8/5k2/8/8/8/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['d7'],
                    Chess::SQUARES['d8'],
                    Chess::BITS['PROMOTION'],
                    Chess::ROOK
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'd8=R');
        
        // check
        $chess->load('3R4/2K5/8/5k2/8/8/8/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['d8'],
                    Chess::SQUARES['f8'],
                    Chess::BITS['NORMAL']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Rf8+');
        
        // checkmate
        $chess->load('5k2/8/1R3K2/8/8/8/8/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['b6'],
                    Chess::SQUARES['b8'],
                    Chess::BITS['NORMAL']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Rb8#');
        
        
        // ambiguous moves: row
        $chess->load('2N2k2/8/3p4/8/2N5/8/1K6/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['c4'],
                    Chess::SQUARES['d6'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'N4xd6');
        
        
        // ambiguous moves: rank > 0 & file > 0
        $chess->load('8/8/8/2qqq3/2qPq3/2qqq3/1n6/K6k b - - 0 1'); // this one is really ambiguous haha
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['d5'],
                    Chess::SQUARES['d4'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Qd5xd4');

        // ambiguous moves: col
        $chess->load('5k2/8/3p4/8/2N1N3/8/1K6/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['e4'],
                    Chess::SQUARES['d6'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Nexd6');
        
        // ambiguous moves: col
        $chess->load('5k2/8/3p4/8/2N1N3/8/1K6/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['c4'],
                    Chess::SQUARES['d6'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Ncxd6');
        
        // ambiguous moves: normal capture
        $chess->load('5k2/8/3p2R1/8/2N5/8/1K6/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['c4'],
                    Chess::SQUARES['d6'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Nxd6');
        
        // ambiguous moves: normal capture
        $chess->load('5k2/8/3p2R1/8/2N5/8/1K6/8 w - - 0 1');
        $move = ($chess->buildMovePublic(
                    $chess->turn(),
                    $chess->getBoard(),
                    Chess::SQUARES['g6'],
                    Chess::SQUARES['d6'],
                    Chess::BITS['CAPTURE']
                ));
        $chess->makeMovePublic($move);
        $undo = $chess->undo();
        $this->assertSame($undo['san'], 'Rxd6');
        
        // generate moves test
        $chess->load('8/ppp2P2/pkp5/ppp5/5PPP/5PKP/5PPP/8 w - - 0 1');
        $moves = $chess->generateMovesPublic();
        array_walk($moves, function (&$move) use ($chess) {
            $move = $chess->moveToSANPublic($move);
        });
        $this->assertContains('f8=Q', $moves);
        $this->assertContains('f8=R', $moves);
        $this->assertContains('f8=B', $moves);
        $this->assertContains('f8=N', $moves);
        $this->assertContains('f5', $moves);
        $this->assertContains('g5', $moves);
        $this->assertContains('h5', $moves);
    }
    
    public function testMoveFromSAN()
    {
        $chess = new ChessPublicator();
        $chess->reset();
        $this->assertSame($chess->moveToSANPublic($chess->moveFromSANPublic('Nf3?!+#')), 'Nf3');
        
        $chess->reset();
        $this->assertSame($chess->moveToSANPublic($chess->moveFromSANPublic('e4')), 'e4');
        
        $chess->reset();
        $this->assertSame($chess->moveToSANPublic($chess->moveFromSANPublic('Nf3')), 'Nf3');
        
        // really heavy function, if this is not a test, then it have to be refactored
        $moveWrapper = function ($san) use ($chess) {
            $move = $chess->moveFromSANPublic($san);
            $sanRepaired = $chess->moveToSANPublic($move);
            $chess->makeMovePublic($move);

            return $sanRepaired;
        };
        $chess->reset();
        $this->assertSame($moveWrapper('e4'), 'e4');
        $this->assertSame($moveWrapper('d5'), 'd5');
        $this->assertSame($moveWrapper('exd5'), 'exd5');
        $this->assertSame($moveWrapper('c6??'), 'c6');
        $this->assertSame($moveWrapper('dxc6!'), 'dxc6');
        $this->assertSame($moveWrapper('Qd5?'), 'Qd5');
        $this->assertSame($moveWrapper('cxb7!!'), 'cxb7');
        $this->assertSame($moveWrapper('Nh6'), 'Nh6');
        $this->assertSame($moveWrapper('bxa8=Q'), 'bxa8=Q');
        $this->assertSame($moveWrapper('Bd7'), 'Bd7');
        $this->assertSame($moveWrapper('Qxb8+'), 'Qxb8+');
        $this->assertSame($moveWrapper('Bc8'), 'Bc8');
        $this->assertSame($moveWrapper('Qxc8'), 'Qxc8+');
        $this->assertSame($moveWrapper('Qd8+#'), 'Qd8');
        $this->assertSame($moveWrapper('Bb5#'), 'Bb5#');
        
        $chess->reset();
        $this->assertSame($chess->moveToSANPublic($chess->moveFromSANPublic('e4=Q')), 'e4');
    }
    
    public function testSANMove()
    {
        // Ruy Lopez (C70)
        $chess = new ChessPublicator();
        $chess->move('e4');
        $chess->move('e5');
        $chess->move('Nf3');
        $chess->move('Nc6');
        $chess->move('Bb5');
        $chess->move('a6');
        $chess->move('Ba4');
        $chess->move('Bc5');
        $this->assertSame($chess->fen(), 'r1bqk1nr/1ppp1ppp/p1n5/2b1p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 2 5');
    }
    
    public function testArrayMove()
    {
        // Ruy Lopez (C70)
        $chess = new ChessPublicator();
        $chess->move(['from' => 'e2', 'to' => 'e4']);
        $chess->move(['from' => 'e7', 'to' => 'e5']);
        $chess->move(['from' => 'g1', 'to' => 'f3']);
        $chess->move(['from' => 'b8', 'to' => 'c6']);
        $chess->move(['from' => 'f1', 'to' => 'b5']);
        $chess->move(['from' => 'a7', 'to' => 'a6']);
        $chess->move(['from' => 'b5', 'to' => 'a4']);
        $chess->move(['from' => 'f8', 'to' => 'c5']);
        $this->assertSame($chess->fen(), 'r1bqk1nr/1ppp1ppp/p1n5/2b1p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 2 5');
    }
    
    public function testSANMoveFromRealGame()
    {
        $chess = new ChessPublicator();
        
        /* [Event "Earl tourn"]
         * [Site "?"]
         * [Date "1906.??.??"]
         * [Round "?"]
         * [White "Alekhine, Alexander"]
         * [Black "Ljubimov, T."]
         * [Result "1-0"]
         * [ECO "C58"]
         */
        $match = '1.e4 e5 2.Nf3 Nc6 3.d4 exd4 4.Nxd4 Nf6 5.Nc3 Bb4 6.Nxc6 bxc6 7.Qd4 Qe7 8.f3 d5 9.Bg5 O-O 10.O-O-O Bc5 11.Bxf6 gxf6 12.Qa4 Be3+ 13.Kb1 d4 14.Ne2 c5 15.Nc1 Be6 16.Bc4 Rfb8 17.Nd3 Rb6';
        $moves = preg_replace("/([0-9]{0,})\./", '', $match);
        $moves = str_replace('  ', ' ', str_replace("\r", ' ', str_replace("\n", ' ', str_replace("\t", '', $moves))));
        $moves = explode(' ', trim($moves));
        //~ foreach($moves as $move) if($chess->move($move) === null) { echo $move . PHP_EOL; break; }
        foreach ($moves as $move) {
            $this->assertNotNull($chess->move($move), $move);
        }
        $this->assertSame($chess->fen(), 'r5k1/p1p1qp1p/1r2bp2/2p5/Q1BpP3/3NbP2/PPP3PP/1K1R3R w - - 6 18');
        
        
        
        
        return; // somehow it have to be turn off, just for faster unit testing, but if you want just comment this line
        
        
        
        
        $chess->reset();
        /* [Event "Earl tourn"]
         * [Site "?"]
         * [Date "1906.??.??"]
         * [Round "?"]
         * [White "Zubakin, N."]
         * [Black "Alekhine, Alexander"]
         * [Result "1/2-1/2"]
         * [ECO "C55"]
         */
        $match = '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.O-O d6 5.c3 Nf6 6.d4 exd4 7.cxd4 Bb6 8.Nc3 O-O
		9.h3 h6 10.Be3 Re8 11.d5 Ne5 12.Nxe5 dxe5 13.Bxb6 axb6 14.f4 Qd6 15.fxe5 Rxe5
		16.Qe2 Rg5 17.Rf3 Qc5+ 18.Kh1 Ng4 19.Rg3 Ne5 20.Rxg5 hxg5 21.Bb3 g4 22.Rf1 Qe7
		23.Qe3 Qh4 24.Qf4 Qh5 25.Nb5 gxh3 26.Qe3 hxg2+ 27.Kxg2 Qg6+ 28.Kh1 Bd7 29.Nxc7 Rc8
		30.d6 Kh7';
        $moves = preg_replace("/([0-9]{0,})\./", '', $match);
        $moves = str_replace('  ', ' ', str_replace("\r", ' ', str_replace("\n", ' ', str_replace("\t", '', $moves))));
        $moves = explode(' ', trim($moves));
        foreach ($moves as $move) {
            $this->assertNotNull($chess->move($move), $move);
        }
        $this->assertSame($chess->fen(), '2r5/1pNb1ppk/1p1P2q1/4n3/4P3/1B2Q3/PP6/5R1K w - - 1 31');
        
        
        $chess->reset();
        /* [Event "World Championship 35th-KK5"]
         * [Site "Lyon/New York"]
         * [Date "1990.??.??"]
         * [Round "16"]
         * [White "Kasparov, Gary"]
         * [Black "Karpov, Anatoly"]
         * [Result "1-0"]
         * [WhiteElo "2800"]
         * [BlackElo "2730"]
         * [ECO "C45"]
         */
        $match = '1.e4 e5 2.Nf3 Nc6 3.d4 exd4 4.Nxd4 Nf6 5.Nxc6 bxc6 6.e5 Qe7 7.Qe2 Nd5 8.c4 Nb6
		9.Nd2 Qe6 10.b3 a5 11.Bb2 Bb4 12.a3 Bxd2+ 13.Qxd2 d5 14.cxd5 cxd5 15.Rc1 O-O
		16.Rxc7 Qg6 17.f3 Bf5 18.g4 Bb1 19.Bb5 Rac8 20.Rxc8 Rxc8 21.O-O h5 22.h3 hxg4
		23.hxg4 Bc2 24.Qd4 Qe6 25.Rf2 Rc7 26.Rh2 Nd7 27.b4 axb4 28.axb4 Nf8 29.Bf1 Bb3
		30.Bd3 Bc4 31.Bf5 Qe7 32.Qd2 Rc6 33.Bd4 Ra6 34.Bb1 Ra3 35.Rh3 Rb3 36.Bc2 Qxb4
		37.Qf2 Ng6 38.e6 Rb1+ 39.Bxb1 Qxb1+ 40.Kh2 fxe6 41.Qb2 Qxb2+ 42.Bxb2 Nf4
		43.Rh4 Nd3 44.Bc3 e5 45.Kg3 d4 46.Bd2 Bd5 47.Rh5 Kf7 48.Ba5 Ke6 49.Rh8 Nb2
		50.Re8+ Kd6 51.Bb4+ Kc6 52.Rc8+ Kd7 53.Rc5 Ke6 54.Rc7 g6 55.Re7+ Kf6 56.Rd7 Ba2
		57.Ra7 Bc4 58.Ba5 Bd3 59.f4 exf4+ 60.Kxf4 Bc2 61.Ra6+ Kf7 62.Ke5 Nd3+ 63.Kxd4 Nf2
		64.g5 Bf5 65.Bd2 Ke7 66.Kd5 Ne4 67.Ra7+ Ke8 68.Be3 Nc3+ 69.Ke5 Kd8 70.Bb6+ Ke8
		71.Rc7 Ne4 72.Be3 Ng3 73.Bf4 Nh5 74.Ra7 Kf8 75.Bh2 Ng7 76.Bg1 Nh5 77.Bc5+ Kg8
		78.Kd6 Kf8 79.Bd4 Bg4 80.Be5 Bf5 81.Rh7 Kg8 82.Rc7 Kf8 83.Kc6 Kg8 84.Re7 Kf8
		85.Bd6 Kg8 86.Re8+ Kf7 87.Re7+ Kg8 88.Be5 Kf8 89.Ra7 Bg4 90.Kd6 Bh3 91.Ra3 Bg4
		92.Re3 Bf5 93.Kc7 Kf7 94.Kd8 Bg4 95.Bb2 Be6 96.Bc3 Bf5 97.Re7+ Kf8 98.Be5 Bd3
		99.Ra7 Be4 100.Rc7 Bb1 101.Bd6+ Kg8 102.Ke7';
        $moves = preg_replace("/([0-9]{0,})\./", '', $match);
        $moves = str_replace('  ', ' ', str_replace("\r", ' ', str_replace("\n", ' ', str_replace("\t", '', $moves))));
        $moves = explode(' ', trim($moves));
        foreach ($moves as $move) {
            $this->assertNotNull($chess->move($move), $move);
        }
        $this->assertSame($chess->fen(), '6k1/2R1K3/3B2p1/6Pn/8/8/8/1b6 b - - 76 102');
    }
}
