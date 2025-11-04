<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Kundli Calculator (Demo)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Kundli Calculator (Demo)</h2>
      <div class="grid">
        <div class="form">
          <label>Full Name <input id="name" placeholder="e.g., Rahul Kumar"></label>
          <label>Date of Birth <input id="dob" type="date"></label>
          <label>Time of Birth <input id="tob" type="time"></label>
          <label>Place of Birth <input id="pob" placeholder="City"></label>
          <div class="actions"><button id="calc">Calculate</button></div>
        </div>
        <div>
          <div id="result" class="card">
            <div class="title-sm">Your Kundli Highlights</div>
            <p class="muted">Enter details and click Calculate.</p>
          </div>
        </div>
      </div>
      <p class="muted" style="margin-top:10px">Note: This is a simplified demo. For accurate charts (lagna, navamsa, planetary positions), integrate an astrology calculation library or API.</p>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const els = {
      name: document.getElementById('name'),
      dob: document.getElementById('dob'),
      tob: document.getElementById('tob'),
      pob: document.getElementById('pob'),
      calc: document.getElementById('calc'),
      result: document.getElementById('result'),
    };

    function numerologyNumber(name) {
      const map = { a:1,b:2,c:3,d:4,e:5,f:6,g:7,h:8,i:9,j:1,k:2,l:3,m:4,n:5,o:6,p:7,q:8,r:9,s:1,t:2,u:3,v:4,w:5,x:6,y:7,z:8 };
      return (name.toLowerCase().split('').reduce((sum,ch)=> sum + (map[ch]||0), 0) % 9) || 9;
    }
    function zodiacFromDate(d) {
      const date = new Date(d);
      const month = date.getUTCMonth()+1, day = date.getUTCDate();
      const z = [
        ["Capricorn",1,20],["Aquarius",2,19],["Pisces",3,20],["Aries",4,20],["Taurus",5,21],["Gemini",6,21],
        ["Cancer",7,22],["Leo",8,22],["Virgo",9,23],["Libra",10,23],["Scorpio",11,22],["Sagittarius",12,21],["Capricorn",12,31]
      ];
      for (let i=0;i<z.length-1;i++){
        const [name,mEnd,dEnd]=z[i]; const [_,mNext,dNext]=z[i+1];
        const afterStart = (month>mEnd) || (month===mEnd && day>dEnd);
        const beforeNext = (month<mNext) || (month===mNext && day<=dNext);
        if (!afterStart && beforeNext) return name;
      }
      return "Capricorn";
    }

    els.calc.onclick = () => {
      const name = els.name.value.trim();
      const dob = els.dob.value;
      const tob = els.tob.value;
      const pob = els.pob.value.trim();
      if (!name || !dob || !tob || !pob) return alert('Fill all fields');

      const num = numerologyNumber(name);
      const zodiac = zodiacFromDate(dob);

      els.result.innerHTML = `
        <div class="title-sm">Kundli Highlights</div>
        <p><strong>Name:</strong> ${name}</p>
        <p><strong>Birth:</strong> ${dob} ${tob}, ${pob}</p>
        <p><strong>Zodiac:</strong> ${zodiac}</p>
        <p><strong>Numerology Number:</strong> ${num}</p>
        <p class="muted">Recommendation: Speak to a ${zodiac} specialist. Numerology (${num}) suggests focus on ${num%3===0 ? 'relationships' : num%2===0 ? 'career' : 'spiritual growth'}.</p>
        <div class="actions">
          <a href="./astrologers.php"><button class="astro-btn astro-btn-primary">Find an Expert</button></a>
          <a href="./messages.php"><button class="astro-btn">Chat Now</button></a>
        </div>
      `;
    };
  </script>
</body>
</html>