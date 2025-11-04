<?php
require_once __DIR__ . '/config.php';
?>
<header class="header astro-header">
  <div class="container astro-topbar">
    <div class="astro-brand">
      <img src="<?php echo h($APP_LOGO_URL); ?>" alt="" class="astro-logo">
      <div>
        <div class="astro-title"><?php echo h($APP_NAME); ?></div>
        <div class="astro-tagline"><?php echo h($APP_TAGLINE); ?></div>
      </div>
    </div>
    <nav class="astro-nav">
      <a href="./home.php">Home</a>
      <a href="./astrologers.php">Astrologers</a>
      <a href="./bookings.php">Bookings</a>
      <a href="./wallet.php">Wallet</a>
      <a href="./messages.php">Messages</a>
      <a href="./live.php">Live</a>
      <a href="./index.php">Calls</a>
      <a href="./horoscope.php">Horoscope</a>
      <a href="./blog.php">Blog</a>
      <a href="./kundli.php">Kundli</a>
    </nav>
  </div>
</header>