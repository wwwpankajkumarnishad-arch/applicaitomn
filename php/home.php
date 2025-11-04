<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
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
        <a href="./home.php" class="active">Home</a>
        <a href="./astrologers.php">Astrologers</a>
        <a href="./bookings.php">Bookings</a>
        <a href="./wallet.php">Wallet</a>
        <a href="./messages.php">Messages</a>
        <a href="./live.php">Live</a>
        <a href="./index.php">Calls</a>
      </nav>
    </div>
  </header>

  <main class="mobile-container">
    <div class="mobile-header">
      <div class="mobile-greeting">Hi, User 👋</div>
      <div class="mobile-actions">
        <div class="icon">🔔</div>
        <img src="<?php echo h($APP_LOGO_URL); ?>" class="mobile-avatar" alt="">
      </div>
    </div>

    <div class="search-bar">
      <span>🔎</span>
      <input id="search" placeholder="Search">
      <span class="mic">🎙️</span>
    </div>

    <div class="quick-cats">
      <div class="quick-cat">🪐</div>
      <div class="quick-cat">📜</div>
      <div class="quick-cat">💖</div>
      <div class="quick-cat">💬</div>
    </div>

    <div class="banner">
      <div>
        <div class="title-sm">Talk to astrologer for free</div>
        <div class="text">Let's open up the thing that matters among the people</div>
      </div>
      <button class="cta" onclick="location.href='./messages.php'">Chat Now</button>
    </div>

    <div class="section-title">Services</div>
    <div class="tiles">
      <div class="tile"><div class="icon">🌟</div><div class="label">Daily Horoscope</div></div>
      <div class="tile"><div class="icon">📘</div><div class="label">Free Kundli</div></div>
      <div class="tile"><div class="icon">🐦</div><div class="label">Kundli Matching</div></div>
      <div class="tile"><div class="icon">💬</div><div class="label">Free Chat</div></div>
    </div>

    <div class="section-title">Astrologers</div>
    <div id="astroRow" class="astro-cards-row"></div>
  </main>

  <div class="tabbar">
    <div class="tab active" onclick="location.href='./home.php'"><div class="icon">🏠</div><div>Home</div></div>
    <div class="tab" onclick="location.href='./messages.php'"><div class="icon">💬</div><div>Chat</div></div>
    <div class="tab" onclick="location.href='./live.php'"><div class="icon">📺</div><div>Live</div></div>
    <div class="tab" onclick="location.href='./index.php'"><div class="icon">📞</div><div>Call</div></div>
    <div class="tab" onclick="location.href='./profile.php'"><div class="icon">👤</div><div>Profile</div></div>
  </div>

  <script>
    const API = "<?php echo h($API); ?>";
    const row = document.getElementById('astroRow');

    async function api(action, payload={}, method='POST') {
      const opts = { method, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } };
      const body = new URLSearchParams({ action, ...payload }).toString();
      if (method === 'POST') opts.body = body;
      const url = method === 'GET' ? API + '?' + body : API;
      const res = await fetch(url, opts);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'api error');
      return data.data;
    }

    function renderAstrologers(items) {
      row.innerHTML = '';
      items.forEach(a => {
        const card = document.createElement('div');
        card.className = 'astro-card small';
        card.innerHTML = `
          <img src="${a.avatar || 'https://placehold.co/96x96'}" class="astro-card-avatar">
          <div class="astro-card-info">
            <div class="astro-card-name">${a.name}</div>
            <div class="astro-card-meta">⭐ ${a.rating} • ₹${a.ratePerMin}/min</div>
            <div class="astro-card-actions">
              <a href="./astrologer.php?id=${a.id}"><button class="astro-btn astro-btn-primary sm">View</button></a>
            </div>
          </div>
        `;
        row.appendChild(card);
      });
    }

    async function load() {
      try {
        const items = await api('list_astrologers', {}, 'GET');
        renderAstrologers(items.slice(0, 10));
      } catch (e) { console.log(e); }
    }

    load();
  </script>
</body>
</html>