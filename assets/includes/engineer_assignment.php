<?php

function engineerSpecialtyForService(string $service): ?string
{
    $specialties = [
        'Propulsion & Machinery' => 'Propulsion & Machinery Specialist',
        'Electrical & Automation' => 'Electrical & Automation Engineer',
        'Hydraulics & Deck Gear' => 'Hydraulics & Deck Gear Technician',
        'Hull & Steel Fabrication' => 'Hull & Steel Fabricator',
        'Preventative Maintenance' => 'Preventative Maintenance Expert',
        'General Consultation' => 'General Marine Consultant',
    ];

    return $specialties[trim($service)] ?? null;
}

function matchingAvailableEngineers(array $engineers, string $service): array
{
    $specialty = engineerSpecialtyForService($service);
    if ($specialty === null) {
        return [];
    }

    return array_values(array_filter($engineers, static function (array $engineer) use ($specialty): bool {
        return $engineer['specialty'] === $specialty;
    }));
}

function assignEngineerToRequest(PDO $pdo, int $requestId, int $engineerId, int $adminId): void
{
    if ($requestId < 1 || $engineerId < 1) {
        throw new DomainException('Invalid request or engineer. Reload the assignment page.');
    }

    $pdo->beginTransaction();
    try {
        // Serialize assignments to the same request before checking its state.
        $stmt = $pdo->prepare('SELECT service_type, status, is_active FROM dispatch_requests WHERE request_id = ? FOR UPDATE');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request || !(int)$request['is_active'] || !in_array($request['status'], ['pending', 'acknowledged'], true)) {
            throw new DomainException('This request is no longer awaiting deployment.');
        }

        $specialty = engineerSpecialtyForService($request['service_type']);
        if ($specialty === null) {
            throw new DomainException('No engineer specialty is configured for this service.');
        }

        $stmt = $pdo->prepare('SELECT deployment_id FROM deployments WHERE request_id = ? LIMIT 1');
        $stmt->execute([$requestId]);
        if ($stmt->fetch()) {
            throw new DomainException('This request has already been assigned.');
        }

        // Lock the engineer so two requests cannot deploy the same person at once.
        $stmt = $pdo->prepare('SELECT ep.specialty, ep.current_status, u.role, u.is_active FROM engineer_profiles ep JOIN users u ON u.user_id = ep.engineer_id WHERE ep.engineer_id = ? FOR UPDATE');
        $stmt->execute([$engineerId]);
        $engineer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$engineer || $engineer['role'] !== 'engineer' || !(int)$engineer['is_active']
            || $engineer['current_status'] !== 'available' || $engineer['specialty'] !== $specialty) {
            throw new DomainException('The engineer must be available and specialize in the requested service. Reload to see current matches.');
        }

        $stmt = $pdo->prepare("SELECT deployment_id FROM deployments WHERE engineer_id = ? AND deployment_status <> 'completed' LIMIT 1");
        $stmt->execute([$engineerId]);
        if ($stmt->fetch()) {
            throw new DomainException('This engineer already has an active deployment.');
        }

        $stmt = $pdo->prepare("INSERT INTO deployments (request_id, admin_id, engineer_id, deployment_status) VALUES (?, ?, ?, 'en_route')");
        $stmt->execute([$requestId, $adminId, $engineerId]);
        $stmt = $pdo->prepare("UPDATE dispatch_requests SET status = 'deployed' WHERE request_id = ?");
        $stmt->execute([$requestId]);
        $stmt = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'deployed' WHERE engineer_id = ?");
        $stmt->execute([$engineerId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
