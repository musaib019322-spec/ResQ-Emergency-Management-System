
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$names = [
    1=>'Chemistry Lab',
    2=>'Computer Lab',
    3=>'Classroom',
    4=>'Library'
];

try {
    $stmt = $pdo->query(
        'SELECT id, room_id, title, message, severity, created_at
         FROM alerts ORDER BY id DESC LIMIT 8'
    );

    $alerts = $stmt->fetchAll();

    foreach ($alerts as &$alert) {
        $alert['room_name'] =
            $names[(int)$alert['room_id']] ?? 'Unknown room';
    }
    unset($alert);

    echo json_encode([
        'success'=>true,
        'alerts'=>$alerts
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'alerts'=>[]
    ]);
}
