<?php
require_once __DIR__ . '/../assets/includes/engineer_assignment.php';

// Exercise SQL and rollback without touching the application's database.
// SQLite does not support MySQL's row locks; concurrency needs MySQL testing.
class AssignmentTestDatabase extends PDO
{
    public function __construct()
    {
        parent::__construct('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->exec("CREATE TABLE users (user_id INTEGER PRIMARY KEY, role TEXT, is_active INTEGER);
            CREATE TABLE engineer_profiles (engineer_id INTEGER PRIMARY KEY, specialty TEXT, current_status TEXT);
            CREATE TABLE dispatch_requests (request_id INTEGER PRIMARY KEY, service_type TEXT, status TEXT, is_active INTEGER);
            CREATE TABLE deployments (deployment_id INTEGER PRIMARY KEY, request_id INTEGER UNIQUE, admin_id INTEGER, engineer_id INTEGER, deployment_status TEXT);
            INSERT INTO users VALUES (1, 'engineer', 1), (2, 'engineer', 1), (3, 'admin', 1);
            INSERT INTO engineer_profiles VALUES (1, 'Electrical & Automation Engineer', 'available'), (2, 'Hull & Steel Fabricator', 'available');
            INSERT INTO dispatch_requests VALUES (10, 'Electrical & Automation', 'pending', 1), (11, 'Electrical & Automation', 'pending', 1);");
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectRejected(AssignmentTestDatabase $db, int $requestId, int $engineerId): void
{
    $before = $db->query('SELECT COUNT(*) FROM deployments')->fetchColumn();
    try {
        assignEngineerToRequest($db, $requestId, $engineerId, 3);
        throw new RuntimeException('Invalid assignment was accepted.');
    } catch (DomainException $e) {
        check(!$db->inTransaction(), 'Rejected assignment left a transaction open.');
        check($db->query('SELECT COUNT(*) FROM deployments')->fetchColumn() === $before, 'Rejected assignment created a deployment.');
    }
}

$services = [
    'Propulsion & Machinery' => 'Propulsion & Machinery Specialist',
    'Electrical & Automation' => 'Electrical & Automation Engineer',
    'Hydraulics & Deck Gear' => 'Hydraulics & Deck Gear Technician',
    'Hull & Steel Fabrication' => 'Hull & Steel Fabricator',
    'Preventative Maintenance' => 'Preventative Maintenance Expert',
    'General Consultation' => 'General Marine Consultant',
];
$roster = [];
foreach (array_values($services) as $id => $specialty) {
    $roster[] = ['user_id' => $id + 1, 'specialty' => $specialty];
}
$roster[] = ['user_id' => 7, 'specialty' => 'Chief Engineer'];
foreach ($services as $service => $specialty) {
    $matches = matchingAvailableEngineers($roster, $service);
    check(count($matches) === 1 && $matches[0]['specialty'] === $specialty, 'Incorrect match for ' . $service);
}
check(matchingAvailableEngineers($roster, 'Unknown Service') === [], 'Unknown service matched an engineer.');
check(matchingAvailableEngineers([], 'Electrical & Automation') === [], 'Empty roster matched an engineer.');
$roster[] = ['user_id' => 8, 'specialty' => 'Electrical & Automation Engineer'];
check(count(matchingAvailableEngineers($roster, 'Electrical & Automation')) === 2, 'Multiple matching engineers were lost.');

$db = new AssignmentTestDatabase();
expectRejected($db, 10, 2); // Wrong specialty, even with a tampered form.
expectRejected($db, 999, 1);
expectRejected($db, 10, 999);
assignEngineerToRequest($db, 10, 1, 3);
check($db->query('SELECT engineer_id FROM deployments WHERE request_id = 10')->fetchColumn() === 1, 'Wrong engineer deployed.');
check($db->query('SELECT status FROM dispatch_requests WHERE request_id = 10')->fetchColumn() === 'deployed', 'Request status not updated.');
check($db->query('SELECT current_status FROM engineer_profiles WHERE engineer_id = 1')->fetchColumn() === 'deployed', 'Engineer not marked busy.');
expectRejected($db, 10, 1); // Duplicate request.
expectRejected($db, 11, 1); // Already busy on another request.
$db->exec("UPDATE engineer_profiles SET current_status = 'available' WHERE engineer_id = 1");
expectRejected($db, 11, 1); // Active deployment still excludes incorrectly marked availability.

foreach ([
    "UPDATE users SET is_active = 0 WHERE user_id = 1",
    "UPDATE users SET role = 'client' WHERE user_id = 1",
    "UPDATE engineer_profiles SET current_status = 'on_leave' WHERE engineer_id = 1",
    "UPDATE dispatch_requests SET status = 'cancelled' WHERE request_id = 10",
    "UPDATE dispatch_requests SET is_active = 0 WHERE request_id = 10",
    "UPDATE dispatch_requests SET service_type = 'Unknown Service' WHERE request_id = 10",
    "INSERT INTO deployments VALUES (1, 10, 3, 2, 'completed')",
] as $mutation) {
    $db = new AssignmentTestDatabase();
    $db->exec($mutation);
    expectRejected($db, 10, 1);
}

$db = new AssignmentTestDatabase();
$db->exec("CREATE TRIGGER fail_update BEFORE UPDATE ON engineer_profiles BEGIN SELECT RAISE(ABORT, 'simulated failure'); END;");
try {
    assignEngineerToRequest($db, 10, 1, 3);
    throw new RuntimeException('Simulated failure was ignored.');
} catch (PDOException $e) {
    check($db->query('SELECT COUNT(*) FROM deployments')->fetchColumn() === 0, 'Failed assignment was not rolled back.');
    check($db->query('SELECT status FROM dispatch_requests WHERE request_id = 10')->fetchColumn() === 'pending', 'Request change was not rolled back.');
    check(!$db->inTransaction(), 'Failed assignment left a transaction open.');
}

echo "Engineer assignment checks passed.\n";
