
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'message'=>'POST required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid JSON']);
    exit;
}

$room = filter_var($data['room_id'] ?? null, FILTER_VALIDATE_INT);
$type = strtolower(trim($data['sensor_type'] ?? ''));

$expected = [
    1 => 'flame',
    2 => 'mq2',
    3 => 'dht11',
    4 => 'dht11'
];

if (!$room || !isset($expected[$room]) || $expected[$room] !== $type) {
    http_response_code(400);
    echo json_encode([
        'success'=>false,
        'message'=>'Invalid room_id or sensor_type'
    ]);
    exit;
}

$temp = isset($data['temperature']) && is_numeric($data['temperature'])
    ? (float)$data['temperature'] : null;

$humidity = isset($data['humidity']) && is_numeric($data['humidity'])
    ? (float)$data['humidity'] : null;

$gas = isset($data['gas_value']) && is_numeric($data['gas_value'])
    ? (int)$data['gas_value'] : null;

$flame = isset($data['flame_detected'])
    ? (int)$data['flame_detected'] : null;

$valid = (int)($data['valid_reading'] ?? 1);
$valid = $valid === 1 ? 1 : 0;

if ($type === 'flame' && !in_array($flame, [0,1], true)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid flame value']);
    exit;
}

if ($type === 'mq2' && $gas === null) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Missing MQ-2 reading']);
    exit;
}

$names = [
    1=>'Chemistry Lab',
    2=>'Computer Lab',
    3=>'Classroom',
    4=>'Library'
];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO sensor_readings
        (device_id, room_id, sensor_type, temperature, humidity,
         gas_value, flame_detected, valid_reading)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $data['device_id'] ?? 'RESQ-ESP32-001',
        $room, $type, $temp, $humidity, $gas, $flame, $valid
    ]);

    $message = null;

    if ($valid && $room === 1 && $flame === 1) {
        $message = 'Flame detected';
    } elseif ($valid && $room === 2 && $gas !== null && $gas >= 1800) {
        $message = 'MQ-2 reading above configured threshold';
    } elseif ($valid && in_array($room, [3,4], true)
              && $temp !== null && $temp >= 50) {
        $message = 'High temperature detected';
    }

    if ($message !== null) {
        $alert = $pdo->prepare(
            'INSERT INTO alerts (room_id, title, message, severity)
             VALUES (?, ?, ?, ?)'
        );
        $alert->execute([$room, $message, $message, 'HIGH']);
    }

    $pdo->commit();

    echo json_encode([
        'success'=>true,
        'message'=>'Reading stored',
        'room_id'=>$room,
        'room_name'=>$names[$room]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'Unable to save reading'
    ]);
}
