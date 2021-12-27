<?php

require_once 'vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

date_default_timezone_set('Europe/Moscow');

$googleJsonKey = __DIR__ . '/' . getenv('GOOGLE_JSON_KEY');
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleJsonKey);

$redis = new Redis();
$redis->connect('trade_redis');

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope('https://www.googleapis.com/auth/spreadsheets');
$service = new Google_Service_Sheets($client);
$spreadsheetId = getenv('SPREADSHEET_ID');

$response = $service->spreadsheets_values->clear($spreadsheetId, 'Лист1!A1:F9999', new Google_Service_Sheets_ClearValuesRequest([]));

$response = $service->spreadsheets->get($spreadsheetId);
$values =  [
    ['Время', 'Операция', 'Цена операции', 'Баланс'],
];
$body = new Google_Service_Sheets_ValueRange(['values' => $values]);

$service->spreadsheets_values->update(
    $spreadsheetId,
    'A1',
    new Google_Service_Sheets_ValueRange(['values' => $values]),
    ['valueInputOption' => 'RAW']
);

$lastTime = 0;
$lastPosition = 2;

while (true)
{
    $values = [];
    $data = $redis->hGetAll(getenv('TIKER'));

    if ($data) {
        ksort($data);
        foreach ($data as $time => $body) {
            if ($time && $lastTime < $time && $body) {
                $data = json_decode($body, true);
                $values[] = [
                    date('Y-m-d H:i:s', $time),
                    $data['operation'],
                    $data['price'],
                    $data['balance']
                ];
                $lastTime = $time;
            }
        }
    }

    if ($values) {
        $service->spreadsheets_values->update(
            $spreadsheetId,
            'A' . ($lastPosition++),
            new Google_Service_Sheets_ValueRange(['values' => $values]),
            ['valueInputOption' => 'RAW']
        );
    }

    sleep(1);
}