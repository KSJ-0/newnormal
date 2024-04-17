<?php

$pdo = new PDO('mysql:host=localhost;dbname=newnormal', 'root', '1124');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "MySQL 연결 성공";

$ch = curl_init();

//주기를 얼마나 두고 날씨 데이터를 DB에 저장할지 생각해봐야 함
//전일(D-1) 자료까지 제공하되, 전일 자료는 조회시간 기준 11시 이후에 조회 가능
//매일 오후 12시 혹은 여유를 두고 오후 1시에 전날 날씨 데이터를 조회하여 저장하면 괜찮을 것 같음

$date = new DateTime();
$date->modify('-1 day');
$yesterdayDate = $date->format('Ymd');
$startHh = '00';
$endHh = '23';
$currentLocation = '239'; //현재는 세종시로 해두었는데 위치에 따라 바뀌도록 해야할 듯

$url = 'http://apis.data.go.kr/1360000/AsosHourlyInfoService/getWthrDataList'; /*URL*/
$queryParams = '?' . urlencode('serviceKey') . '=PgHyGfQcPIcM2nnrr7PoX0NGR3ilWlWtDy29WgpXj3uUxmqE0mvSnd03%2Bxi56s9VktxiKv7bmyq0r1BHJJQGhQ%3D%3D'; /*Service Key*/
$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1'); /*페이지 번호*/
$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('100'); /*한 페이지 결과 수*/
$queryParams .= '&' . urlencode('dataType') . '=' . urlencode('JSON'); /*요청자료형식*/
$queryParams .= '&' . urlencode('dataCd') . '=' . urlencode('ASOS'); /*자료 분류 코드*/
$queryParams .= '&' . urlencode('dateCd') . '=' . urlencode('HR'); /*날짜 분류 코드*/
$queryParams .= '&' . urlencode('startDt') . '=' . urlencode($yesterdayDate); /*시작일*/
$queryParams .= '&' . urlencode('startHh') . '=' . urlencode($startHh); /*시작 시간*/
$queryParams .= '&' . urlencode('endDt') . '=' . urlencode($yesterdayDate); /*종료일*/
$queryParams .= '&' . urlencode('endHh') . '=' . urlencode($endHh); /*종료 시간*/
$queryParams .= '&' . urlencode('stnIds') . '=' . urlencode($currentLocation); /*세종시*/

curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
foreach ($data['response']['body']['items']['item'] as $item) {
    $newItem = [
        'tm' => $item['tm'],
        'hm' => (int)$item['hm'],
        'dc10Tca'=> (int)$item['dc10Tca'],
        'rn' => (float)$item['rn']    
    ];

    $sql = "INSERT INTO weather (date, hm, dc10Tca, rn) VALUES (:date, :hm, :dc10Tca, :rn)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':date', $newItem['tm']);
    $stmt->bindParam(':hm', $newItem['hm']);
    $stmt->bindParam(':dc10Tca', $newItem['dc10Tca']);
    $stmt->bindParam(':rn', $newItem['rn']);;
    $stmt->execute();
    echo "전날 날씨 데이터 저장 완료";
}

?>