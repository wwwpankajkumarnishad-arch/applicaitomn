<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
$id = $_GET['id'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Astrologer Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card" id="profile"></section>

    <section class="card">
      <h2>Available Slots</h2>
      <div id="slots"></div>
    </section>

    <section class="card">
      <h2>Reviews</h2>
      <div id="reviews"></div>
      <div class="grid">
        <div class="form">
          <label>Your Username <input id="me" placeholder="e.g., rahul"></label>
          <label>Rating (1-5) <input id="rating" placeholder="5"></label>
          <label>Text <input id="text" placeholder="Share your experience"></label>
          <div class="actions"><button id="addReview">Add Review</button></div>
        </div>
        <div class="form">
          <h3>Add Slot (Demo)</h3>
          <label>Start ISO <input id="start" placeholder="2025-11-20T10:00:00"></label>
          <label>End ISO <input id="end" placeholder="2025-11-20T10:30:00"></label>
          <label>Price (₹) <input id="price" placeholder="299"></label>
          <div class="actions"><button id="addSlot">Add Slot</button></div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";
    const astrologerId = "<?php echo h($id); ?>";

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
      profile: document.getElementById('profile'),
      slots: document.getElementById('slots'),
      reviews: document.getElementById('reviews'),
      me: document.getElementById('me'),
      rating: document.getElementById('rating'),
      text: document.getElementById('text'),
      addReview: document.getElementById('addReview'),
      start: document.getElementById('start'),
      end: document.getElementById('end'),
      price: document.getElementById('price'),
      addSlot: document.getElementById('addSlot'),
    };

    function renderProfile(a) {
      els.profile.innerHTML = `
        <div style="display:flex;gap:12px;align-items:center">
          <img src="${a.avatar || 'https://placehold.co/80x80'}" width="80" height="80" style="border-radius:14px;border:1px solid #e6e8ef">
          <div>
            <div class="title">${a.name}</div>
            <div class="muted">${a.bio}</div>
            <div class="muted">Skills: ${a.skills.join(', ')}</div>
            <div class="muted">⭐ ${a.rating} • ${a.reviewsCount} reviews • ₹${a.ratePerMin}/min</div>
          </div>
        </div>
      `;
    }

    function renderSlots(list) {
      els.slots.innerHTML = '';
      if (list.length === 0) {
        els.slots.innerHTML = '<p class="muted">No slots yet.</p>';
        return;
      }
      list.forEach(s => {
        const card = document.createElement('div');
        card.className = 'astro-card';
        card.innerHTML = `
          <div style="display:flex;align-items:center;gap:12px">
            <div>
              <div class="title-sm">${new Date(s.start).toLocaleString()} - ${new Date(s.end).toLocaleString()}</div>
              <div class="muted">₹${s.price} • ${s.status}</div>
            </div>
            <div style="margin-left:auto" class="astro-card-actions">
              ${s.status === 'available' ? `<button class="astro-btn astro-btn-primary sm bookBtn" data-id="${s.slotId}">Book</button>` : `<button class="astro-btn sm" disabled>Booked</button>`}
            </div>
          </div>
        `;
        els.slots.appendChild(card);
      });

      els.slots.querySelectorAll('.bookBtn').forEach(btn => {
        btn.onclick = async () => {
          const username = prompt('Your username (must exist and have wallet balance):');
          if (!username) return;
          try {
            const booking = await api('create_booking', { username, astrologerId, slotId: btn.dataset.id });
            alert('Booking confirmed: ' + booking.id);
          } catch (e) { alert(e.message); }
        };
      });
    }

    function renderReviews(list) {
      els.reviews.innerHTML = '';
      if (list.length === 0) {
        els.reviews.innerHTML = '<p class="muted">No reviews yet.</p>';
        return;
      }
      list.forEach(r => {
        const div = document.createElement('div');
        div.className = 'astro-card';
        div.innerHTML = `
          <div><strong>${r.user}</strong> • ⭐ ${r.rating}</div>
          <div>${r.text}</div>
          <div class="muted">${new Date(r.createdAt*1000).toLocaleString()}</div>
        `;
        els.reviews.appendChild(div);
      });
    }

    async function load() {
      try {
        const a = await api('get_astrologer', { id: astrologerId }, 'GET');
        renderProfile(a);
        const ss = await api('list_slots', { astrologerId }, 'GET');
        renderSlots(ss);
        const rs = await api('list_reviews', { astrologerId }, 'GET');
        renderReviews(rs);
      } catch (e) { console.log(e); }
    }

    els.addReview.onclick = async () => {
      try {
        await api('add_review', {
          astrologerId,
          user: els.me.value.trim(),
          rating: els.rating.value.trim(),
          text: els.text.value.trim(),
        });
        els.me.value = els.rating.value = els.text.value = '';
        load();
      } catch (e) { alert(e.message); }
    };

    els.addSlot.onclick = async () => {
      try {
        await api('add_slot', {
          astrologerId,
          start: els.start.value.trim(),
          end: els.end.value.trim(),
          price: els.price.value.trim(),
        });
        els.start.value = els.end.value = els.price.value = '';
        load();
      } catch (e) { alert(e.message); }
    };

    load();
  </script>
</body>
</html>