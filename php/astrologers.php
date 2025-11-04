<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Astrologers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Astrologer Directory</h2>
      <div id="list"></div>
    </section>

    <section class="card">
      <h2>Add Astrologer (Demo)</h2>
      <div class="grid">
        <div class="form">
          <label>Name <input id="name" placeholder="e.g., Guruji Sharma"></label>
          <label>Bio <input id="bio" placeholder="Vedic astrology, numerology, vastu"></label>
          <label>Avatar URL <input id="avatar" placeholder="https://..."></label>
          <label>Skills (comma separated) <input id="skills" placeholder="Vedic,Kundli,Matchmaking"></label>
          <label>Rate per minute (₹) <input id="rate" placeholder="50"></label>
          <div class="actions"><button id="add">Add</button></div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";

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

    const els = {
      list: document.getElementById('list'),
      name: document.getElementById('name'),
      bio: document.getElementById('bio'),
      avatar: document.getElementById('avatar'),
      skills: document.getElementById('skills'),
      rate: document.getElementById('rate'),
      add: document.getElementById('add'),
    };

    function renderList(items) {
      els.list.innerHTML = '';
      items.forEach(a => {
        const card = document.createElement('div');
        card.className = 'astro-card';
        card.innerHTML = `
          <img src="${a.avatar || 'https://placehold.co/96x96'}" class="astro-card-avatar">
          <div class="astro-card-info">
            <div class="astro-card-name">${a.name}</div>
            <div class="astro-card-meta">⭐ ${a.rating} • ${a.reviewsCount} reviews • ₹${a.ratePerMin}/min</div>
            <div class="astro-card-skills">${a.skills.join(', ')}</div>
            <div class="astro-card-actions">
              <a href="./astrologer.php?id=${a.id}"><button class="astro-btn astro-btn-primary sm">View Profile</button></a>
            </div>
          </div>
        `;
        els.list.appendChild(card);
      });
    }

    async function load() {
      try {
        const items = await api('list_astrologers', {}, 'GET');
        renderList(items);
      } catch (e) { console.log(e); }
    }

    els.add.onclick = async () => {
      try {
        await api('upsert_astrologer', {
          name: els.name.value.trim(),
          bio: els.bio.value.trim(),
          avatar: els.avatar.value.trim(),
          skills: els.skills.value.trim(),
          ratePerMin: els.rate.value.trim(),
        });
        els.name.value = els.bio.value = els.avatar.value = els.skills.value = els.rate.value = '';
        load();
      } catch (e) { alert(e.message); }
    };

    load();
  </script>
</body>
</html>