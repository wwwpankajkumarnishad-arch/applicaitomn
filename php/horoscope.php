<?php
require_once __DIR__ . '/config.php';
$signs = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Horoscope</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Daily Horoscope</h2>
      <div class="astro-grid">
        <?php foreach ($signs as $s): ?>
          <div class="card">
            <div class="title"><?php echo h($s); ?></div>
            <p class="muted">Your energy favors reflection and planning. Consider long-term goals. A good day for connecting with loved ones.</p>
            <div class="actions">
              <a href="./astrologers.php"><button class="astro-btn">Talk to an Astrologer</button></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>