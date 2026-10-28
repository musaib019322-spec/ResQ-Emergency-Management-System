
<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/database.php';

$names = [
    1=>'Chemistry Lab',
    2=>'Computer Lab',
    3=>'Classroom',
    4=>'Library'
];

$types = [
    1=>'flame',
    2=>'mq2',
    3=>'dht11',
    4=>'dht11'
];

try {
    $stmt = $pdo->prepare(
        'SELECT * FROM sensor_readings
         WHERE room_id = ?
         ORDER BY id DESC LIMIT 1'
    );

    $rooms = [];
    $deviceOnline = false;

    foreach ($names as $id => $name) {
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        $online = $r &&
            (time() - strtotime($r['created_at']) <= 30);

        if ($online) {
            $deviceOnline = true;
        }

        $valid = $r && (int)$r['valid_reading'] === 1;
        $alert = false;

        if ($valid) {
            if ($id === 1 &&
                (int)$r['flame_detected'] === 1) {
                $alert = true;
            }

            if ($id === 2 && $r['gas_value'] !== null &&
                (int)$r['gas_value'] >= 1800) {
                $alert = true;
            }

            if (in_array($id, [3,4], true) &&
                $r['temperature'] !== null &&
                (float)$r['temperature'] >= 50) {
                $alert = true;
            }
        }

        $rooms[] = [
            'room_id' => $id,
            'room_name' => $name,
            'sensor_type' => $types[$id],
            'temperature' => $r['temperature'] ?? null,
            'humidity' => $r['humidity'] ?? null,
            'gas_value' => $r['gas_value'] ?? null,
            'flame_detected' => $r['flame_detected'] ?? null,
            'valid_reading' => $r['valid_reading'] ?? 0,
            'updated_at' => $r['created_at'] ?? null,
            'is_online' => $online ? 1 : 0,
            'is_alert' => $alert ? 1 : 0
        ];
    }

    echo json_encode([
        'success' => true,
        'device_online' => $deviceOnline,
        'rooms' => $rooms
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'Unable to fetch readings'
    ]);
}
