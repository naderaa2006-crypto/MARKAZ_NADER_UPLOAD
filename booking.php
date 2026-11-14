
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$errors = [];
$success = false;
$bookingId = null;
$services = [];

try {
    $stmt = $pdo->query(
        "SELECT id, name, description, price
         FROM services
         WHERE is_active = 1
         ORDER BY id ASC"
    );
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    die("تعذر تحميل الخدمات. تحقق من اتصال قاعدة البيانات.");
}

if (empty($_SESSION['booking_token'])) {
    $_SESSION['booking_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['booking_token']) ||
        !hash_equals($_SESSION['booking_token'], $_POST['booking_token'])
    ) {
        $errors[] = "انتهت صلاحية النموذج. حدّث الصفحة وحاول مرة أخرى.";
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $plate = trim($_POST['plate_number'] ?? '');
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $date = $_POST['booking_date'] ?? '';
    $time = $_POST['booking_time'] ?? '';
    $problem = trim($_POST['problem_description'] ?? '');

    if ($name === '') $errors[] = "يرجى كتابة الاسم.";
    if (!preg_match('/^[0-9+\s-]{9,20}$/', $phone)) {
        $errors[] = "رقم الجوال غير صحيح.";
    }
    if ($brand === '') $errors[] = "يرجى كتابة ماركة السيارة.";
    if ($model === '') $errors[] = "يرجى كتابة موديل السيارة.";

    if ($year < 1950 || $year > ((int)date('Y') + 1)) {
        $errors[] = "سنة الصنع غير صحيحة.";
    }

    if ($serviceId <= 0) $errors[] = "يرجى اختيار الخدمة.";

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date ||
        $date < date('Y')) {
        $errors[] = "يرجى اختيار تاريخ صحيح في المستقبل.";
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        $errors[] = "يرجى اختيار وقت صحيح.";
    }

    if (!$errors) {
        $checkService = $pdo->prepare(
            "SELECT id FROM services WHERE id = ? AND is_active = 1"
        );
        $checkService->execute([$serviceId]);

        if (!$checkService->fetch()) {
            $errors[] = "الخدمة المختارة غير متاحة.";
        }
    }

    // التحقق من الصور
    $uploadedFiles = [];
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!$errors && isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $fileCount = count($files['name']);

        if ($fileCount > 4) {
            $errors[] = "الحد الأقصى للصور هو 4 صور.";
        } else {
            $uploadDirectory = __DIR__ . '/uploads/booking-images/';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            for ($i = 0; $i < $fileCount; $i++) {

                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = "حدث خطأ أثناء رفع إحدى الصور.";
                    break;
                }

                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    $errors[] = "حجم الصورة الواحدة يجب ألا يتجاوز 5 ميجابايت.";
                    break;
                }

                $mime = $finfo->file($files['tmp_name'][$i]);

                if (!isset($allowedTypes[$mime])) {
                    $errors[] = "يسمح فقط بصور JPG و PNG و WEBP.";
                    break;
                }

                $extension = $allowedTypes[$mime];
                $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                $destination = $uploadDirectory . $fileName;

                if (!move_uploaded_file($files['tmp_name'][$i], $destination)) {
                    $errors[] = "تعذر حفظ إحدى الصور.";
                    break;
                }

                $uploadedFiles[] = [
                    'path' => 'uploads/booking-images/' . $fileName,
                    'full_path' => $destination
                ];
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // منع حجز نفس التاريخ والوقت مرتين
            $checkBooking = $pdo->prepare(
                "SELECT id FROM bookings
                 WHERE booking_date = ?
                 AND booking_time = ?
                 AND status != 'cancelled'
                 FOR UPDATE"
            );

            $checkBooking->execute([$date, $time]);

            if ($checkBooking->fetch()) {
                throw new Exception(
                    "هذا الموعد محجوز بالفعل. اختر وقتاً آخر."
                );
            }

            // البحث عن العميل باستخدام رقم الجوال
            $customerStmt = $pdo->prepare(
                "SELECT id FROM customers WHERE phone = ?"
            );
            $customerStmt->execute([$phone]);
            $customer = $customerStmt->fetch();

            if ($customer) {
                $customerId = $customer['id'];

                $updateCustomer = $pdo->prepare(
                    "UPDATE customers SET name = ? WHERE id = ?"
                );
                $updateCustomer->execute([$name, $customerId]);

            } else {
                $insertCustomer = $pdo->prepare(
                    "INSERT INTO customers (name, phone)
                     VALUES (?, ?)"
                );
                $insertCustomer->execute([$name, $phone]);
                $customerId = $pdo->lastInsertId();
            }

            // تسجيل السيارة
            $carStmt = $pdo->prepare(
                "INSERT INTO cars
                (customer_id, brand, model, `year`, plate_number)
                VALUES (?, ?, ?, ?, ?)"
            );

            $carStmt->execute([
                $customerId,
                $brand,
                $model,
                $year,
                $plate !== '' ? $plate : null
            ]);

            $carId = $pdo->lastInsertId();

            // تسجيل الحجز
            $bookingStmt = $pdo->prepare(
                "INSERT INTO bookings
                (customer_id, car_id, service_id,
                 booking_date, booking_time,
                 problem_description, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );

            $bookingStmt->execute([
                $customerId,
                $carId,
                $serviceId,
                $date,
                $time,
                $problem !== '' ? $problem : null
            ]);

            $bookingId = $pdo->lastInsertId();

            // حفظ مسارات الصور
            if (!empty($uploadedFiles)) {
                $imageStmt = $pdo->prepare(
                    "INSERT INTO booking_images (booking_id, image_path)
                     VALUES (?, ?)"
                );

                foreach ($uploadedFiles as $file) {
                    $imageStmt->execute([
                        $bookingId,
                        $file['path']
                    ]);
                }
            }

            $pdo->commit();

            $success = true;
            $_SESSION['booking_token'] = bin2hex(random_bytes(32));

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach ($uploadedFiles as $file) {
                if (file_exists($file['full_path'])) {
                    unlink($file['full_path']);
                }
            }

            $errors[] = $e instanceof PDOException
                ? "تعذر حفظ الحجز. يرجى المحاولة مرة أخرى."
                : $e->getMessage();
        }
    }
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>حجز موعد | MARKAZ NADER</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/booking.css">
</head>

<body>

<main class="booking-page">

    <div class="booking-top">
        <a href="index.php" class="booking-logo">MARKAZ NADER</a>
        <a href="index.php" class="back-link">العودة للرئيسية ←</a>
    </div>

    <?php if ($success): ?>

        <section class="booking-success">
            <div class="success-icon">✓</div>
            <h1>تم استلام طلب الحجز!</h1>
            <p>شكراً لثقتك بنا. طلبك الآن بانتظار تأكيد المركز.</p>

            <div class="reference-number">
                رقم الحجز
                <strong>#<?= h($bookingId) ?></strong>
            </div>

            <a href="index.php" class="booking-submit">
                العودة إلى الرئيسية
            </a>
        </section>

    <?php else: ?>

        <section class="booking-heading">
            <span class="eyebrow">MARKAZ NADER / BOOKING</span>
            <h1>موعدك يبدأ من هنا.</h1>
            <p>أدخل بياناتك وبيانات سيارتك وسنتولى الباقي.</p>
        </section>

        <?php if ($errors): ?>
            <div class="booking-errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= h($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="booking-form" method="POST"
              action="booking.php" enctype="multipart/form-data">

            <input type="hidden" name="booking_token"
                   value="<?= h($_SESSION['booking_token']) ?>">

            <section class="form-section">
                <div class="section-heading">
                    <span>01</span>
                    <div>
                        <h2>بيانات العميل</h2>
                        <p>كيف يمكننا التواصل معك؟</p>
                    </div>
                </div>

                <div class="form-grid">
                    <label>
                        الاسم الكامل
                        <input type="text" name="name" required
                               maxlength="150"
                               value="<?= h($_POST['name'] ?? '') ?>"
                               placeholder="اكتب اسمك">
                    </label>

                    <label>
                        رقم الجوال
                        <input type="tel" name="phone" required
                               maxlength="20"
                               value="<?= h($_POST['phone'] ?? '') ?>"
                               placeholder="05xxxxxxxx">
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <span>02</span>
                    <div>
                        <h2>بيانات السيارة</h2>
                        <p>أخبرنا قليلاً عن سيارتك.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <label>
                        الشركة المصنعة
                        <input type="text" name="brand" required
                               value="<?= h($_POST['brand'] ?? '') ?>"
                               placeholder="مثال: Toyota">
                    </label>

                    <label>
                        موديل السيارة
                        <input type="text" name="model" required
                               value="<?= h($_POST['model'] ?? '') ?>"
                               placeholder="مثال: Camry">
                    </label>

                    <label>
                        سنة الصنع
                        <input type="number" name="year" required
                               min="1950" max="<?= date('Y') + 1 ?>"
                               value="<?= h($_POST['year'] ?? '') ?>"
                               placeholder="2022">
                    </label>

                    <label>
                        رقم اللوحة
                        <input type="text" name="plate_number"
                               value="<?= h($_POST['plate_number'] ?? '') ?>"
                               placeholder="اختياري">
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <span>03</span>
                    <div>
                        <h2>تفاصيل الموعد</h2>
                        <h2>سعر السمكرة و المكيانيكيا تحدد عند الفحص</h2>
                        <p>اختر الخدمة والموعد المناسب لك.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <label>
                        الخدمة المطلوبة
                        <select name="service_id" required>
                            <option value="">اختر الخدمة</option>

                            <?php foreach ($services as $service): ?>
                                <option value="<?= h($service['id']) ?>"
                                    <?= (string)($_POST['service_id'] ?? '') === (string)$service['id'] ? 'selected' : '' ?>>
                                    <?= h($service['name']) ?>
                                    — <?= number_format((float)$service['price'], 2) ?> ر.س
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </label>

                    <label>
                        تاريخ الحجز
                        <input type="date" name="booking_date" required
                               min="<?= date('Y-m-d') ?>"
                               value="<?= h($_POST['booking_date'] ?? '') ?>">
                    </label>

                    <label>
                        وقت الحجز
                        <input type="time" name="booking_time" required
                               value="<?= h($_POST['booking_time'] ?? '') ?>">
                    </label>
                </div>

                <label class="full-field">
                    وصف المشكلة أو ملاحظات إضافية
                    <textarea name="problem_description" rows="5"
                              placeholder="اكتب تفاصيل المشكلة إن وجدت..."><?= h($_POST['problem_description'] ?? '') ?></textarea>
                </label>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <span>04</span>
                    <div>
                        <h2>صور السيارة</h2>
                        <p>يمكنك إرفاق ما يصل إلى 4 صور، وهذا اختياري.</p>
                    </div>
                </div>

                <label class="upload-box">
                    <span class="upload-icon">＋</span>
                    <strong>اضغط لاختيار الصور</strong>
                    <small>JPG / PNG / WEBP — حتى 5 ميجابايت للصورة</small>
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp"
                           multiple>
                </label>
            </section>

            <div class="booking-bottom">
                <p>سيتم إرسال طلبك للمركز بحالة: بانتظار التأكيد.</p>
                <button type="submit" class="booking-submit">
                    تأكيد طلب الحجز ←
                </button>
            </div>

        </form>

    <?php endif; ?>

</main>

<script>
const imageInput = document.querySelector('input[name="images[]"]');

if (imageInput) {
    imageInput.addEventListener('change', function () {
        if (this.files.length > 4) {
            alert('يمكنك اختيار 4 صور كحد أقصى.');
            this.value = '';
        }
    });
}
</script>

</body>
</html>
