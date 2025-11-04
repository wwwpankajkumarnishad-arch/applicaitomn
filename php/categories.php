<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
$skill = $_GET['skill'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Categories</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Browse by Category</h2>
      <div class="astro-categories">
        <a href="./categories.php?skill=Vedic" class="astro-category-card"><div class="astro-category-icon">🕉️</div><div class="astro-category-name">Vedic</div></a>
        <a href="./categories.php?skill=Kundli" class="astro-category-card"><div class="astro-category-icon">🔯</div><div class="astro-category-name">Kundli</div></a>
        <a href="./categories.php?skill=Matchmaking" class="astro-category-card"><div class="astro-category-icon">💞</div><div class="astro-category-name">Matchmaking</div></a>
        <a href="./categories.php?skill=Numerology" class="astro-category-card"><div class="astro-category-icon">🔢</div><div class="astro-category-name">Numerology</div></a>
        <a href="./categories.php?skill=Tarot" class="astro-category-card"><div class="astro-category-icon">🃏</div><div class="astro-category-name">Tarot</div></a>
      </div>
    </section>

    <section class="card">
      <div class="astro-section-head">
        <h2 class="astro-section-title">Astrologers <?php echo $skill ? '• ' . h($skill) : ''; ?></h2>
        <div class="astro-filters">
          <button class="astro-chip active" data-sort="rating">Top Rated</button>
          <button class="astro-chip" data-sort="price">Best Value</button>
          <button class="astro-chip" data-sort="name">A–Z</button>
        </div>
      </div>
      <div id="grid" class="astro-grid"></div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";
    const skill = "<?php echo h($skill); ?>";
    const grid = document.getElementById('grid');
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

    function render(items) {
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
      const items = await api('list_astrologers', {}, 'GET');
      let filtered = items.slice();
      if (skill) { filtered = filtered.filter(a => (a.skills||[]).includes(skill)); }
      if (sort === 'rating') filtered.sort((a,b)=> (b.rating||0) - (a.rating||0));
      if (sort === 'price') filtered.sort((a,b)=> (a.ratePerMin||0) - (b.ratePerMin||0));
      if (sort === 'name') filtered.sort((a,b)=> (a.name||'').localeCompare(b.name||''));
      render(filtered);
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