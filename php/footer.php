<?php
require_once __DIR__ . '/config.php';
?>
<footer class="astro-footer">
  <div class="container astro-footer-inner">
    <div>© <?php echo date('Y'); ?> <?php echo h($APP_NAME); ?></div>
    <div class="astro-footer-links">
      <a href="./home.php">Home</a>
      <a href="./astrologers.php">Astrologers</a>
      <a href="./wallet.php">Wallet</a>
      <a href="./horoscope.php">Horoscope</a>
      <a href="./blog.php">Blog</a>
      <a href="./kundli.php">Kundli</a>
    </div>
  </div>
</footer>