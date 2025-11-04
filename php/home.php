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

  <main class="container">
    <!-- Hero -->
    <section class="astro-hero">
      <div class="astro-hero-content">
        <h1>Talk to India’s Top Astrologers</h1>
        <p>Instant chat and call. Authentic guidance on love, career, finance, and health.</p>
        <div class="astro-hero-actions">
          <a href="./astrologers.php"><button class="astro-btn astro-btn-primary">Consult Now</button></a>
          <a href="./live.php"><button class="astro-btn">Join Live</button></a>
        </div>
      </div>
      <div class="astro-hero-illustration"></div>
    </section>

    <!-- Categories -->
    <section class="astro-section">
      <h2 class="astro-section-title">Services</h2>
      <div class="astro-categories">
        <div class="astro-category-card">
          <div class="astro-category-icon">🔯</div>
          <div class="astro-category-name">Kundli</div>
        </div>
        <div class="astro-category-card">
          <div class="astro-category-icon">💞</div>
          <div class="astro-category-name">Matchmaking</div>
        </div>
        <div class="astro-category-card">
          <div class="astro-category-icon">💼</div>
          <div class="astro-category-name">Career</div>
        </div>
        <div class="astro-category-card">
          <div class="astro-category-icon">💰</div>
          <div class="astro-category-name">Finance</div>
        </div>
        <div class="astro-category-card">
          <div class="astro-category-icon">🕉️</div>
          <div class="astro-category-name">Vedic Remedies</div>
        </div>
      </div>
    </section>

    <!-- Top Astrologers -->
    <section class="astro-section">
      <div class="astro-section-head">
        <h2 class="astro-section-title">Top Astrologers</h2>
        <div class="astro-filters">
          <button class="astro-chip active" data-sort="rating">Top Rated</button>
          <button class="astro-chip" data-sort="price">Best Value</button>
          <button class="astro-chip" data-sort="name">A–Z</button>
        </div>
      </div>
      <div id="astroGrid" class="astro-grid"></div>
    </section>

    <!-- Why Us -->
    <section class="astro-section">
      <h2 class="astro-section-title">Why Choose Us</h2>
      <div class="astro-why">
        <div class="astro-why-card">
          <div class="astro-why-icon">✅</div>
          <div>
            <div class="astro-why-title">Verified Experts</div>
            <div class="astro-why-desc">Profiles vetted for authenticity and experience.</div>
          </div>
        </div>
        <div class="astro-why-card">
          <div class="astro-why-icon">⚡</div>
          <div>
            <div class="astro-why-title">Instant Connect</div>
            <div class="astro-why-desc">Chat or call in seconds, anytime.</div>
          </div>
        </div>
        <div class="astro-why-card">
          <div class="astro-why-icon">🔒</div>
          <div>
            <div class="astro-why-title">Secure & Private</div>
            <div class="astro-why-desc">Protected sessions with safe payments.</div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="astro-footer">
    <div class="container astro-footer-inner">
      <div>© <?php echo date('Y'); ?> <?php echo h($APP_NAME); ?></div>
      <div class="astro-footer-links">
        <a href="./home.php">Home</a>
        <a href="./astrologers.php">Astrologers</a>
        <a href="./wallet.php">Wallet</a>
      </div>
    </div>
  </footer>

  <script>
    const API = "<?php echo h($API); ?>";
    const grid = document.getElementById('astroGrid');
    const chips = document.querySelectorAll('.astro-chip');

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
      grid.innerHTML = '';
      items.forEach(a => {
        const card = document.createElement('div');
        card.className = 'astro-card';
        card.innerHTML = `
          <img src="${a.avatar || 'https://placehold.co/96x96'}" class="astro-card-avatar">
          <div class="astro-card-info">
            <div class="astro-card-name">${a.name}</div>
            <div class="astro-card-meta">⭐ ${a.rating} • ₹${a.ratePerMin}/min</div>
            <div class="astro-card-skills">${a.skills.join(', ')}</div>
            <div class="astro-card-actions">
              <a href="./astrologer.php?id=${a.id}"><button class="astro-btn astro-btn-primary sm">View Profile</button></a>
              <a href="./messages.php"><button class="astro-btn sm">Chat</button></a>
              <a href="./index.php"><button class="astro-btn sm">Call</button></a>
            </div>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    async function load(sort='rating') {
      try {
        const items = await api('list_astrologers', {}, 'GET');
        let sorted = items.slice();
        if (sort === 'rating') sorted.sort((a,b)=> (b.rating||0) - (a.rating||0));
        if (sort === 'price') sorted.sort((a,b)=> (a.ratePerMin||0) - (b.ratePerMin||0));
        if (sort === 'name') sorted.sort((a,b)=> (a.name||'').localeCompare(b.name||''));
        renderAstrologers(sorted);
      } catch (e) { console.log(e); }
    }

    chips.forEach(chip => {
      chip.onclick = () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        load(chip.dataset.sort);
      };
    });

    load();
  </script>
</body>
</html>