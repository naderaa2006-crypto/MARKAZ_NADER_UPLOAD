
<?php
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query(
        "SELECT id, name, description, price
         FROM services
         WHERE is_active = 1
         ORDER BY id ASC"
    );

    $services = $stmt->fetchAll();

} catch (PDOException $e) {
    $services = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ede4d3">

    <title>مركز نادر | صيانة السيارات</title>

    <meta name="description"
          content="مركز نادر لصيانة السيارات. احجز موعدك بسهولة واستمتع بخدمات صيانة احترافية.">

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap"
          rel="stylesheet">
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="container nav-content">

        <a href="index.php" class="logo">
            <span class="logo-symbol">N.</span>
            <span>MARKAZ NADER</span>
        </a>

        <nav class="nav-links" id="navLinks">
            <a href="#home">الرئيسية</a>
            <a href="#services">خدماتنا</a>
            <a href="#about">عن المركز</a>
            <a href="#contact">تواصل معنا</a>
        </nav>

        <div class="nav-actions">

            <button class="theme-button" id="themeButton"
                    aria-label="تغيير المظهر">☾</button>

            <a href="login.php" class="nav-booking">
                دخول المدير
                <span>↗</span>
            </a>

            <a href="booking.php" class="nav-booking">
                احجز موعدك
                <span>↗</span>
            </a>

            <button class="menu-button" id="menuButton"
                    aria-label="القائمة">☰</button>

        </div>

    </div>
</header>


<!-- HERO -->
<main>

<section class="hero" id="home">

    <div class="hero-image"></div>
    <div class="hero-overlay"></div>

    <div class="container hero-content">

        <div class="hero-label">
            <span class="status-dot"></span>
            العناية بسيارتك تبدأ من هنا
        </div>

        <h1>
            سيارتك تستحق
            <span>عناية أفضل.</span>
        </h1>

        <p>
            في مركز نادر، نقدم خدمات صيانة السيارات
            باهتمام بالتفاصيل وجودة يمكنك الاعتماد عليها.
        </p>

        <div class="hero-actions">

            <a href="booking.php" class="primary-button">
                احجز موعد الصيانة
                <span>←</span>
            </a>

            <a href="#services" class="secondary-button">
                اكتشف خدماتنا
            </a>

        </div>

        <div class="hero-bottom">
            <span>MARKAZ NADER</span>
            <span>01 — AUTOMOTIVE CARE</span>
        </div>

    </div>

</section>


<!-- INTRO -->
<section class="intro section-padding">

    <div class="container intro-grid">

        <div>
            <span class="section-tag">01 / من نحن</span>

            <h2>
                أكثر من مجرد
                <span>صيانة.</span>
            </h2>
        </div>

        <div class="intro-description">
            <p>
                نؤمن أن تجربة صيانة السيارة يجب أن تكون
                سهلة وواضحة ومريحة.

                لذلك صممنا مركز نادر ليجمع بين جودة الخدمة
                وسهولة الحجز والاهتمام بكل تفصيل.
            </p>

            <a href="#about" class="text-link">
                اكتشف المركز <span>←</span>
            </a>
        </div>

    </div>

</section>


<!-- SERVICES -->
<section class="services-section section-padding" id="services">

    <div class="container">

        <div class="section-heading">

            <div>
                <span class="section-tag">02 / خدماتنا</span>
                <h2>كل ما تحتاجه سيارتك.</h2>
            </div>

            <p>
                خدمات متخصصة لتبقى سيارتك
                في أفضل حالة ممكنة.
            </p>

        </div>


        <div class="services-grid">

            <?php if (!empty($services)): ?>

                <?php foreach ($services as $index => $service): ?>

                    <article class="service-card">

                        <div class="service-image service-image-<?php echo ($index % 3) + 1; ?>">

                            <span class="service-number">
                                0<?php echo $index + 1; ?>
                            </span>

                            <span class="service-arrow">↗</span>

                        </div>

                        <div class="service-info">

                            <h3>
                                <?php echo htmlspecialchars($service['name']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($service['description'] ?? ''); ?>
                            </p>

                            <div class="service-bottom">

                                <span class="service-price">
                                    <?php echo number_format((float)$service['price'], 0); ?>
                                    <small>ر.س</small>
                                </span>

                                <a href="booking.php?service=<?php echo (int)$service['id']; ?>">
                                    احجز الخدمة ←
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-services">
                    لا توجد خدمات متاحة حالياً.
                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- FEATURE IMAGE -->
<section class="feature-section" id="about">

    <div class="feature-image"></div>

    <div class="feature-content">

        <span class="section-tag">03 / تجربة نادر</span>

        <h2>
            اهتمام بالتفاصيل.
            <span>ثقة في كل رحلة.</span>
        </h2>

        <p>
            نهدف إلى تقديم تجربة صيانة منظمة،
            تبدأ من سهولة الحجز وتنتهي باستلام سيارتك.
        </p>

        <a href="booking.php" class="primary-button">
            ابدأ بحجز موعدك <span>←</span>
        </a>

    </div>

</section>


<!-- BOOKING CTA -->
<section class="booking-section section-padding">

    <div class="container booking-box">

        <div>
            <span class="section-tag">04 / احجز الآن</span>

            <h2>
                سيارتك تحتاج عناية؟
                <span>نحن هنا.</span>
            </h2>

            <p>
                احجز موعدك بسهولة، واترك الباقي علينا.
            </p>
        </div>

        <a href="booking.php" class="booking-cta">
            احجز موعدك الآن
            <span>↗</span>
        </a>

    </div>

</section>

</main>


<!-- FOOTER -->
<footer class="footer" id="contact">

    <div class="container">

        <div class="footer-main">

            <div class="footer-brand">

                <a href="index.php" class="logo">
                    <span class="logo-symbol">N.</span>
                    <span>MARKAZ NADER</span>
                </a>

                <p>
                    العناية بسيارتك، باهتمام يستحق ثقتك.
                </p>

            </div>

            <div class="footer-links">

                <h4>روابط سريعة</h4>

                <a href="#home">الرئيسية</a>
                <a href="#services">خدماتنا</a>
                <a href="#about">عن المركز</a>
                <a href="booking.php">حجز موعد</a>
                <a href="login.php">دخول المدير ↗</a>

            </div>

            <div class="footer-links">

                <h4>خدماتنا</h4>

                <a href="#services">تغيير الزيت</a>
                <a href="#services">الميكانيكا</a>
                <a href="#services">السمكرة</a>

            </div>

        </div>

        <div class="footer-bottom">

            <span>© <?php echo date('Y'); ?> MARKAZ NADER</span>
            <span>صنع بعناية.</span>

        </div>

    </div>

</footer>


<!-- JAVASCRIPT -->
<script>

const menuButton = document.getElementById('menuButton');
const navLinks = document.getElementById('navLinks');

menuButton.addEventListener('click', function () {
    navLinks.classList.toggle('active');
});

document.querySelectorAll('.nav-links a').forEach(function(link) {
    link.addEventListener('click', function() {
        navLinks.classList.remove('active');
    });
});

// الوضع الفاتح والداكن
const themeButton = document.getElementById('themeButton');

themeButton.addEventListener('click', function() {

    document.body.classList.toggle('dark-mode');

    const isDark = document.body.classList.contains('dark-mode');

    themeButton.textContent = isDark ? '☀' : '☾';

    document.querySelector('meta[name="theme-color"]')
        .setAttribute('content', isDark ? '#202a28' : '#ede4d3');

    localStorage.setItem('markazTheme', isDark ? 'dark' : 'light');

});

if (localStorage.getItem('markazTheme') === 'dark') {
    document.body.classList.add('dark-mode');
    themeButton.textContent = '☀';
    document.querySelector('meta[name="theme-color"]')
        .setAttribute('content', '#202a28');
}

</script>

</body>
</html>
