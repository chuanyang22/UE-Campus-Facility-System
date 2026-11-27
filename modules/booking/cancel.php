<?php
/**
 * Module 3: Cancel Booking & Release Equipment Holds (Delete)
 * AMCS2194 Mini Project — Process 3.4
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/BookingService.php';

$bookingId = trim((string)($_GET['id'] ?? $_POST['id'] ?? ''));
$service   = new BookingService();
$currentUser = AuthService::getCurrentUser();

if (empty($bookingId)) {
    AuthService::setFlash('danger', 'No booking ID specified for cancellation.');
    header('Location: index.php');
    exit;
}

$result = $service->cancelBooking(
    $bookingId, 
    $currentUser['UserID'], 
    AuthService::isAdmin()
);

if ($result['success']) {
    AuthService::setFlash('success', $result['message']);
} else {
    AuthService::setFlash('danger', $result['message']);
}

header('Location: index.php');
exit;
