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
<body>
  <header class="header">
    <div class="container" style="display:flex;align-items:center;gap:12px">
      <img src="<?php echo h($APP_LOGO_URL); ?>" alt="" width="48" height="48" style="border-radius:10px;border:1px solid #2a335a">
      <div>
        <h1 style="margin:0">Find Astrologers</h1>
        <p style="margin:0" class="muted">Browse experts and book a consultation.</p>
        <nav style="margin-top:8px">
          <a href="./feed.php">Feed</a> |
          <a href="./messages.php">Messages</a> |
          <a href="./live.php">Live Rooms</a> |
          <a href="./index.php">Calls</a> |
          <a href="./astrologers.php">Astrologers</a> |
          <a href="./bookings.php">Bookings</a> |
          <a href="./wallet.php">Wallet</a>
        </nav>
      </div>
    </div>
  </header>

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

  <footer class="footer"><div class="container"><small>Demo directory</small></div></footer>

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
        card.className = 'card';
        card.innerHTML = `
          <div style="display:flex;gap:12px;align-items:center">
            <img src="${a.avatar || 'https://placehold.co/64x64'}" width="64" height="64" style="border-radius:12px;border:1px solid #2a335a">
            <div>
              <div class="title">${a.name}</div>
              <div class="muted">${a.skills.join(', ')}</div>
              <div class="muted">⭐ ${a.rating} • ${a.reviewsCount} reviews • ₹${a.ratePerMin}/min</div>
            </div>
            <div style="margin-left:auto" class="actions">
              <a href="./astrologer.php?id=${a.id}"><button>View Profile</button></a>
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