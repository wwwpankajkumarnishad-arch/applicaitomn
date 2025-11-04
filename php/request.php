<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Become an Astrologer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Apply to Become an Astrologer</h2>
      <div class="grid">
        <div class="form">
          <label>Full Name <input id="name" placeholder="e.g., Guruji Sharma"></label>
          <label>Contact (Email/Phone) <input id="contact" placeholder="e.g., +91... / email@domain.com"></label>
          <label>Bio <input id="bio" placeholder="Experience, certifications, specialties"></label>
          <label>Avatar URL <input id="avatar" placeholder="https://..."></label>
          <label>Skills (comma separated) <input id="skills" placeholder="Vedic,Kundli,Matchmaking"></label>
          <label>Rate per minute (₹) <input id="rate" placeholder="50"></label>
          <div class="actions"><button id="submit">Submit Request</button></div>
        </div>
        <div>
          <div class="card">
            <div class="title-sm">What happens next?</div>
            <p class="muted">Our admin team reviews your application and may contact you for verification. If approved, your profile will appear in the directory.</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";
    const els = {
      name: document.getElementById('name'),
      contact: document.getElementById('contact'),
      bio: document.getElementById('bio'),
      avatar: document.getElementById('avatar'),
      skills: document.getElementById('skills'),
      rate: document.getElementById('rate'),
      submit: document.getElementById('submit'),
    };

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

    els.submit.onclick = async () => {
      try {
        await api('create_astrologer_request', {
          name: els.name.value.trim(),
          contact: els.contact.value.trim(),
          bio: els.bio.value.trim(),
          avatar: els.avatar.value.trim(),
          skills: els.skills.value.trim(),
          ratePerMin: els.rate.value.trim(),
        });
        alert('Request submitted! We will review and revert.');
        els.name.value = els.contact.value = els.bio.value = els.avatar.value = els.skills.value = els.rate.value = '';
      } catch (e) { alert(e.message); }
    };
  </script>
</body>
</html>