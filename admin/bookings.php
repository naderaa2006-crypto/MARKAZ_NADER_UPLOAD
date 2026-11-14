
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// التأكد من تسجيل دخول المدير
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = '';

// تحديث حالة الحجز
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {

    $bookingId = (int) $_POST['booking_id'];
    $newStatus = $_POST['status'];

    $allowedStatuses = [
        'pending',
        'confirmed',
        'in_progress',
        'ready',
        'completed',
        'cancelled'
    ];

    if (in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare(
            "UPDATE bookings SET status = ? WHERE id = ?"
        );
        $stmt->execute([$newStatus, $bookingId]);

        $message = 'تم تحديث حالة الحجز بنجاح.';
    }
}

// جلب الحجوزات
$stmt = $pdo->query("
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.problem_description,
        b.status,
        b.created_at,
        customers.name AS customer_name,
        customers.phone,
        cars.brand,
        cars.model,
        cars.year,
        cars.plate_number,
        services.name AS service_name,
        services.price
    FROM bookings b
    INNER JOIN customers ON b.customer_id = customers.id
    INNER JOIN cars ON b.car_id = cars.id
    INNER JOIN services ON b.service_id = services.id
    ORDER BY b.created_at DESC
");

$bookings = $stmt->fetchAll();

$statusLabels = [
    'pending' => 'بانتظار التأكيد',
    'confirmed' => 'تم التأكيد',
    'in_progress' => 'قيد التنفيذ',
    'ready' => 'جاهزة للاستلام',
    'completed' => 'مكتملة',
    'cancelled' => 'ملغاة'
];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>إدارة الحجوزات | MARKAZ NADER</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #ede4d3;
            color: #292f2d;
            font-family: Arial, sans-serif;
        }

        .admin-page {
            max-width: 1250px;
            margin: auto;
            padding: 35px 22px 70px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .admin-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .admin-header p {
            color: #777;
            margin: 0;
        }

        .admin-logo {
            color: #5f8c89;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .admin-actions {
            display: flex;
            gap: 10px;
        }

        .admin-actions a {
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 9px;
            background: #5f8c89;
            color: white;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 15px;
        }

        .stat-card span {
            color: #777;
            font-size: 14px;
        }

        .stat-card strong {
            display: block;
            font-size: 30px;
            margin-top: 12px;
            color: #5f8c89;
        }

        .booking-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 18px;
        }

        .booking-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 17px;
            margin-bottom: 20px;
        }

        .booking-title h2 {
            margin: 0;
            font-size: 19px;
        }

        .booking-number {
            color: #5f8c89;
            font-weight: bold;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .detail span {
            display: block;
            color: #888;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .detail strong {
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .problem {
            margin-top: 20px;
            padding: 15px;
            background: #f7f6f2;
            border-radius: 10px;
            line-height: 1.8;
        }

        .booking-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .booking-images img {
            width: 100px;
            height: 85px;
            object-fit: cover;
            border-radius: 9px;
        }

        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #eee;
        }

        .status-form select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font: inherit;
            background: white;
            flex: 1;
            max-width: 260px;
        }

        .status-form button {
            padding: 12px 20px;
            background: #5f8c89;
            color: white;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font: inherit;
        }

        .notice {
            background: #e3eee8;
            color: #315b50;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .empty {
            background: white;
            padding: 50px 20px;
            text-align: center;
            border-radius: 16px;
            color: #777;
        }

        .empty h2 {
            color: #5f8c89;
        }

        @media(max-width: 700px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .booking-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .admin-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .status-form {
                align-items: stretch;
                flex-direction: column;
            }

            .status-form select {
                max-width: none;
            }
        }

        @media(max-width: 420px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <div class="admin-logo">MARKAZ NADER / ADMIN</div>
            <h1>إدارة الحجوزات</h1>
            <p>مرحباً <?= h($_SESSION['admin_username']) ?>، هذه جميع طلبات الحجز.</p>
        </div>

        <div class="admin-actions">
            <a href="../index.php">الموقع الرئيسي</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="notice"><?= h($message) ?></div>
    <?php endif; ?>

    <section class="stats">

        <div class="stat-card">
            <span>إجمالي الحجوزات</span>
            <strong><?= count($bookings) ?></strong>
        </div>

        <div class="stat-card">
            <span>بانتظار التأكيد</span>
            <strong>
                <?= count(array_filter($bookings, fn($b) => $b['status'] === 'pending')) ?>
            </strong>
        </div>

        <div class="stat-card">
            <span>الحجوزات المكتملة</span>
            <strong>
                <?= count(array_filter($bookings, fn($b) => $b['status'] === 'completed')) ?>
            </strong>
        </div>

    </section>

    <?php if (empty($bookings)): ?>

        <div class="empty">
            <h2>لا توجد حجوزات حتى الآن</h2>
            <p>ستظهر طلبات العملاء هنا بمجرد حجز موعد جديد.</p>
        </div>

    <?php else: ?>

        <?php foreach ($bookings as $booking): ?>

            <article class="booking-card">

                <div class="booking-title">
                    <h2>حجز العميل: <?= h($booking['customer_name']) ?></h2>
                    <span class="booking-number">
                        #<?= h($booking['id']) ?>
                    </span>
                </div>

                <div class="booking-details">

                    <div class="detail">
                        <span>رقم الجوال</span>
                        <strong><?= h($booking['phone']) ?></strong>
                    </div>

                    <div class="detail">
                        <span>السيارة</span>
                        <strong>
                            <?= h($booking['brand']) ?>
                            <?= h($booking['model']) ?>
                        </strong>
                    </div>

                    <div class="detail">
                        <span>سنة الصنع</span>
                        <strong><?= h($booking['year']) ?></strong>
                    </div>

                    <div class="detail">
                        <span>رقم اللوحة</span>
                        <strong><?= h($booking['plate_number'] ?: 'غير محدد') ?></strong>
                    </div>

                    <div class="detail">
                        <span>الخدمة المطلوبة</span>
                        <strong><?= h($booking['service_name']) ?></strong>
                    </div>

                    <div class="detail">
                        <span>سعر الخدمة</span>
                        <strong><?= number_format((float)$booking['price'], 0) ?> ر.س</strong>
                    </div>

                    <div class="detail">
                        <span>تاريخ الموعد</span>
                        <strong><?= h($booking['booking_date']) ?></strong>
                    </div>

                    <div class="detail">
                        <span>وقت الموعد</span>
                        <strong><?= h(substr($booking['booking_time'], 0, 5)) ?></strong>
                    </div>

                    <div class="detail">
                        <span>حالة الحجز</span>
                        <strong><?= h($statusLabels[$booking['status']] ?? $booking['status']) ?></strong>
                    </div>

                </div>

                <?php if (!empty($booking['problem_description'])): ?>
                    <div class="problem">
                        <strong>وصف المشكلة:</strong><br>
                        <?= nl2br(h($booking['problem_description'])) ?>
                    </div>
                <?php endif; ?>

                <?php
                $imageStmt = $pdo->prepare(
                    "SELECT image_path FROM booking_images WHERE booking_id = ?"
                );
                $imageStmt->execute([$booking['id']]);
                $images = $imageStmt->fetchAll();
                ?>

                <?php if ($images): ?>
                    <div class="booking-images">
                        <?php foreach ($images as $image): ?>
                            <?php
                            $imagePath = __DIR__ . '/../' . $image['image_path'];
                            $imageUrl = '../' . $image['image_path'];
                            ?>
                            <?php if (is_file($imagePath)): ?>
                                <a href="<?= h($imageUrl) ?>" target="_blank">
                                    <img src="<?= h($imageUrl) ?>" alt="صورة السيارة">
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="status-form">

                    <input type="hidden" name="booking_id"
                           value="<?= h($booking['id']) ?>">

                    <select name="status">
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= h($value) ?>"
                                <?= $booking['status'] === $value ? 'selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">تحديث الحالة</button>

                </form>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>
