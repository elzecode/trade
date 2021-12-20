<?php

namespace app;

use Dotenv\Dotenv;
use jamesRUS52\TinkoffInvest\TICandle;
use jamesRUS52\TinkoffInvest\TICandleIntervalEnum;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIOrderBook;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use WebSocket\Client;

require_once 'vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

class Trade
{
    public string $token;

    const KEY_ESC = 27;

    public $tradePositions = [
        'BBG006L8G4H1' => [],
    ];

    public $mainWin;
    public $debugWin;
    public $money = 10000;

    public Client $wsClient;

    public function __construct($token)
    {
        $this->token = $token;
        $this->wsConnect();
        $this->initCliInterface();
    }

    public function wsConnect()
    {
        try {
            $this->wsClient = new Client(
                "wss://api-invest.tinkoff.ru/openapi/md/v1/md-openapi/ws",
                [
                    "timeout" => 60,
                    "headers" => ["authorization" => "Bearer {$this->token}"],
                ]
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        $this->wsClient->send('{
            "event": "candle:subscribe",
            "figi": "BBG006L8G4H1",
            "interval": "1min"
        }');



    }

    public function initCliInterface()
    {
        // ncurses_newwin(y - докуда, х - докуда, y - откуда, х - откуда);
        $ncurses = ncurses_init();

        $this->mainWin = ncurses_newwin( 0, 0, 0, 0);
        //ncurses_wborder($this->mainWin,0,0, 0,0, 0,0, 0,0);
        ncurses_wrefresh($this->mainWin);
        ncurses_getmaxyx($this->mainWin, $y, $x);

        $this->debugWin = ncurses_newwin(2, abs(2 - $x), abs(3 - $y), 1);
        ncurses_wborder($this->debugWin,0,0, 0,0, 0,0, 0,0);


        $i = 0;
        foreach ($this->tradePositions as $figi => &$info) {
            $fromX = $i > 0 ? floor($x / count($this->tradePositions)) * $i : 0;
            $info['win'] = ncurses_newwin($y - 4, floor($x / count($this->tradePositions)), 1, $fromX + 1);
            ncurses_wborder($info['win'],0,0, 0,0, 0,0, 0,0);
            ncurses_mvwaddstr($info['win'], 0, 1, $figi);
            ncurses_wrefresh($info['win']);
            $i++;
        }


        while (true) {
            if($this->loadTradePositionData()) {
                $this->trade();
                $this->showTradePositionData();
                $inPos = $this->tradePositions['BBG006L8G4H1']['data']['buyCount'] * array_reverse($this->tradePositions['BBG006L8G4H1']['data']['lastPrices'])[0]['price'];
                $this->seyToDebug('Balance: ' . $this->money . ' | ' . $this->tradePositions['BBG006L8G4H1']['data']['buyCount'] . ' (' . $inPos . ') | Sum: ' . ($inPos + $this->money));
            }
        }

//        do {
//
//
//            //$this->trade();
//            //sleep(1);
//            //$this->inputProcessing(ncurses_getch());
//        } while(true);

         // выходим из режима ncurses, чистим экран
    }

    public function trade()
    {
        foreach ($this->tradePositions as $figi => &$info) {
            if (!isset($info['data']['buyCount'])) {
                $info['data']['buyCount'] = 0;
            }
            if (!isset($info['data']['buyPrice'])) {
                $info['data']['buyPrice'] = 0;
            }
            $last = array_reverse($info['data']['lastPrices'])[0];
            $action = false;

            if ($info['data']['buyCount'] > 0 & $info['data']['buyPrice'] < $last['price']) {
                $action = 'sale';
            } else {
                $action = 'buy';
            }

            if ($action == 'buy') {
                if ($last['price'] < $this->money) {
                    $info['data']['buyCount']++;
                    $info['data']['buyPrice'] = $last['price'];
                    $this->money = $this->money - $last['price'];
                    $last['buy'] = true;
                }
            }

            if ($action == 'sale') {
                $this->money = $this->money + ($info['data']['buyCount'] * $last['price']);
                $info['data']['buyCount'] = 0;
                $info['data']['buyPrice'] = 0;
                $last['sale'] = true;
            }

            $info['data']['lastPrices'][count($info['data']['lastPrices']) - 1] = $last;
        }
    }

    public function loadTradePositionData()
    {
        $response = $this->wsClient->receive();
        $json = json_decode($response, true);
        if (!isset($json['event']) || $json === null) {
            return false;
        }

        foreach ($this->tradePositions as &$info) {
            $newValue = [
                'price' => $json['payload']['c'],
                'buy' => false,
                'sale' => false
            ];
            if (!isset($info['data']['lastPrices'])) {
                $info['data']['lastPrices'] = [];
            }
            if (count($info['data']['lastPrices']) >= 10) {
                array_shift($info['data']['lastPrices']);
            }
            $info['data']['lastPrices'][] = $newValue;
        }

        return true;
    }

    public function showTradePositionData()
    {
        foreach ($this->tradePositions as $figi => &$info) {
            $i = 1;
            ncurses_wmove($info['win'], 1, 2);
            ncurses_waddstr($info['win'], 'Price:');
            foreach (array_reverse($info['data']['lastPrices']) as $item) {
                ncurses_wmove($info['win'], (1 + $i), 2);
                ncurses_waddstr($info['win'], $item['price']);
                ncurses_wmove($info['win'], (1 + $i), 10);
                ncurses_waddstr($info['win'], '------');
                if ($item['buy'] === true) {
                    ncurses_wmove($info['win'], (1 + $i), 10);
                    ncurses_waddstr($info['win'], 'BUY!');
                }
                if ($item['sale'] === true) {
                    ncurses_wmove($info['win'], (1 + $i), 10);
                    ncurses_waddstr($info['win'], 'SALE!');
                }
                ncurses_wrefresh($info['win']);
                $i++;
            }
        }


        //$this->seyToDebug(implode(', ', $this->tradePositions['DDD']['data']['lastPrices']));

    }

    public function seyToDebug($str)
    {
        ncurses_wclear($this->debugWin);
        ncurses_mvwaddstr($this->debugWin, 1, 1, $str);
        ncurses_wrefresh($this->debugWin);
    }

    public function inputProcessing($input)
    {
        if ($input == self::KEY_ESC) {
            ncurses_end();
            exit();
        } else {
            ncurses_mvwaddstr(
                $this->tradePositions['GOLD']['win'], 5, 5, $input);
        }
    }
}

new Trade(getenv('TOKEN'));
