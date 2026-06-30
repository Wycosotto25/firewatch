<?php
// team.php — F.I.R.E.W.A.T.C.H Meet the Team Page
// Link your existing style.css in the <head> below.

$members = [
  'nikka' => [
    'name'    => 'Nikka Faith R. Domingo',
    'nickname'=> 'Niks',
    'role'    => 'Team Member',
    'course'  => 'BSIT 2-2 · ISU Echague Campus',
    'initials'=> 'NF',
    'photo'   => 'profile1.jpg',
  ],
  'wyco' => [
    'name'    => 'Wyco Sotto',
    'nickname'=> 'Wyco',
    'role'    => 'Team Member',
    'course'  => 'BSIT · ISU Echague Campus',
    'initials'=> 'WS',
    'photo'   => 'profile2.JPG',
  ],
  'jilian' => [
    'name'    => 'Jilian S. Labasan',
    'nickname'=> 'Jil',
    'role'    => 'Team Member',
    'course'  => 'BSIT · ISU Main Campus',
    'initials'=> 'JL',
    'photo'   => 'profile3.jpeg',
  ],
  'johnmark' => [
    'name'    => 'John Mark P. Noleal',
    'nickname'=> 'JM',
    'role'    => 'Team Member',
    'course'  => 'BSIT · ISU Echague Campus',
    'initials'=> 'JM',
    'photo'   => 'profile4.jpeg',
  ],
  'cj' => [
    'name'    => 'CJ-Win D. Rillera',
    'nickname'=> 'CJ',
    'role'    => 'Team Member',
    'course'  => 'BSIT · ISU Echague Campus',
    'initials'=> 'CJ',
    'photo'   => 'profile5.jpg',
  ],
];

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Meet the Team — F.I.R.E.W.A.T.C.H</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <!-- Link your existing stylesheet -->
  <link rel="stylesheet" href="style2.css" />
  <style>
    /* ── Team-page specific styles ── */
    .team-body {
      background:
        radial-gradient(ellipse 80% 40% at 50% 0%, #1a060055 0%, transparent 60%),
        var(--bg-deep);
      min-height: 100vh;
    }

    /* Hero */
    .team-hero {
      position: relative;
      padding: 72px 24px 56px;
      text-align: center;
      overflow: hidden;
    }
    .team-hero::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg, transparent, var(--ember-bright), transparent);
      opacity: 0.35;
    }
    .hero-eyebrow {
      display: inline-block;
      font-family: var(--font-display);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--ember-bright);
      border: 1px solid var(--ember-dim);
      padding: 5px 16px;
      border-radius: 20px;
      background: var(--ember-glow);
      margin-bottom: 22px;
    }
    .hero-title {
      font-family: var(--font-display);
      font-size: clamp(2.4rem, 6vw, 4rem);
      font-weight: 700;
      letter-spacing: 0.04em;
      line-height: 1.1;
      color: var(--text-primary);
      margin-bottom: 14px;
    }
    .hero-title span { color: var(--ember-bright); }
    .hero-sub {
      font-size: 0.95rem;
      color: var(--text-secondary);
      max-width: 600px;
      margin: 0 auto 12px;
      line-height: 1.8;
    }
    .hero-meta {
      font-size: 0.75rem;
      color: var(--text-dim);
      letter-spacing: 0.06em;
    }

    /* About section */
    .about-section {
      max-width: 960px;
      margin: 0 auto;
      padding: 52px 24px 40px;
    }
    .about-card {
      background: var(--bg-card);
      border: 1px solid var(--border-glow);
      border-radius: var(--radius-lg);
      padding: 36px 40px;
      display: flex;
      flex-direction: column;
      gap: 32px;
      box-shadow: 0 2px 20px rgba(255,75,26,0.07);
    }
    @media (max-width: 680px) {
      .about-card { padding: 24px 20px; }
    }
    .about-label {
      font-family: var(--font-display);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--ember-bright);
      border-left: 3px solid var(--ember-bright);
      padding-left: 10px;
      margin-bottom: 12px;
    }
    .about-heading {
      font-family: var(--font-display);
      font-size: 1.55rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 14px;
      line-height: 1.25;
    }
    .about-text {
      font-size: 0.875rem;
      color: var(--text-secondary);
      line-height: 1.85;
    }
    .stat-list { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    @media (max-width: 700px) { .stat-list { grid-template-columns: repeat(2, 1fr); } }
    .stat-item {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .stat-icon { font-size: 1.5rem; }
    .stat-num {
      font-family: var(--font-display);
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--ember-bright);
      line-height: 1;
    }
    .stat-lbl {
      font-size: 0.72rem;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-top: 2px;
    }

    /* Members grid */
    .members-section {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 24px 72px;
    }
    .section-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 26px;
    }
    .section-title {
      font-family: var(--font-display);
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-secondary);
      white-space: nowrap;
      border-left: 3px solid var(--ember-bright);
      padding-left: 10px;
    }
    .section-rule {
      flex: 1;
      height: 1px;
      background: linear-gradient(90deg, var(--border), transparent);
    }
    .members-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 18px;
    }
    .member-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 28px 18px 20px;
      text-align: center;
      text-decoration: none;
      display: block;
      transition: all 0.22s ease;
      position: relative;
      overflow: visible;
      isolation: isolate;
    }
    /* Top ember line */
    .member-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--ember-bright), transparent);
      opacity: 0;
      transition: opacity 0.22s;
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }
    .member-card:hover::before { opacity: 1; }
    .member-card:hover {
      border-color: var(--ember-dim);
      transform: translateY(-4px);
      box-shadow: 0 8px 32px rgba(255,107,26,0.14);
      text-decoration: none;
    }
    .member-card.tba { opacity: 0.65; pointer-events: none; }

    /* ── Flame containers on left & right ── */
    .flame-left,
    .flame-right {
      position: absolute;
      bottom: 0;
      width: 20px;
      height: 100%;
      pointer-events: none;
      display: flex;
      flex-direction: column-reverse;
      align-items: center;
      gap: 3px;
      overflow: visible;
    }
    .flame-left  { left: -6px; }
    .flame-right { right: -6px; }

    .flame-particle {
      /* Teardrop / flame tongue: wide fat base, pinched to a point at the top */
      width: 11px;
      border-radius: 42% 42% 0% 0% / 55% 55% 0% 0%;
      clip-path: polygon(50% 0%, 90% 55%, 100% 80%, 85% 100%, 15% 100%, 0% 80%, 10% 55%);
      opacity: 0;
      animation: flame-rise var(--dur, 1.4s) ease-in infinite;
      animation-delay: var(--delay, 0s);
      flex-shrink: 0;
    }
    /* Three layers: base (red), mid (orange), tip (yellow) */
    .flame-particle.f1 {
      height: 20px;
      background: linear-gradient(to top, #e02020 0%, #ff3300 50%, #ff6600 100%);
      filter: blur(0.4px);
    }
    .flame-particle.f2 {
      height: 15px;
      background: linear-gradient(to top, #ff4400 0%, #ff6b1a 50%, #ff9900 100%);
      filter: blur(0.3px);
    }
    .flame-particle.f3 {
      height: 10px;
      background: linear-gradient(to top, #ff9900 0%, #ffcc00 60%, #fff176 100%);
      filter: blur(0.2px);
    }

    @keyframes flame-rise {
      0%   { opacity: 0;   transform: translateY(0)     scaleX(1)    scaleY(0.4) rotate(0deg); }
      10%  { opacity: 1;   transform: translateY(-4px)  scaleX(1.1)  scaleY(0.9) rotate(-2deg); }
      40%  { opacity: 0.85;transform: translateY(-16px) scaleX(0.9)  scaleY(1.2) rotate(2deg); }
      70%  { opacity: 0.45;transform: translateY(-30px) scaleX(0.6)  scaleY(1.5) rotate(-1deg); }
      100% { opacity: 0;   transform: translateY(-46px) scaleX(0.25) scaleY(1.8) rotate(1deg); }
    }
.member-avatar {
  width: 82px;
  height: 82px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--ember-dim), var(--alarm-red));
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 1.7rem;
  font-weight: 700;
  color: #fff;
  border: 2px solid var(--border);
  transition: border-color 0.22s;
  overflow: hidden;
}
.member-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

    .member-card:hover .member-avatar { border-color: var(--ember-bright); }
    .member-name {
      font-family: var(--font-display);
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.03em;
      line-height: 1.2;
      margin-bottom: 4px;
      background: linear-gradient(135deg, #fff 30%, var(--ember-bright) 70%, #ff3300 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 0 6px rgba(255,107,26,0.5));
      animation: fire-flicker 3s ease-in-out infinite alternate;
    }
    @keyframes fire-flicker {
      0%   { filter: drop-shadow(0 0 4px rgba(255,107,26,0.4)); }
      50%  { filter: drop-shadow(0 0 10px rgba(255,80,0,0.7)); }
      100% { filter: drop-shadow(0 0 6px rgba(255,150,0,0.5)); }
    }
    .member-nick {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--ember-bright);
      margin-bottom: 6px;
    }
    .member-course {
      font-size: 0.72rem;
      color: var(--text-dim);
      margin-bottom: 16px;
      line-height: 1.45;
    }
    .view-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.74rem;
      font-weight: 600;
      color: var(--ember-bright);
      border: 1px solid var(--ember-dim);
      border-radius: 6px;
      padding: 6px 14px;
      background: var(--ember-glow);
      transition: all 0.18s;
    }
    .member-card:hover .view-btn {
      background: var(--ember-mid);
      color: #fff;
      border-color: var(--ember-bright);
    }
    .tba-badge {
      font-size: 0.7rem;
      color: var(--text-dim);
      border: 1px dashed var(--border);
      border-radius: 6px;
      padding: 5px 12px;
    }

    /* Light mode fixes */
    [data-theme="light"] .team-body {
      background: var(--bg-deep);
    }
    [data-theme="light"] .member-card {
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    [data-theme="light"] .stat-item {
      box-shadow: 0 1px 6px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body class="team-body">

  <!-- ── Topbar (matching your existing dashboard topbar) ── -->
  <header class="topbar">
    <div class="topbar-left">
      <span class="topbar-fire">🔥</span>
      <span class="topbar-title">F.I.R.E.W.A.T.C.H</span>
    </div>
    <div class="topbar-right">
      <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
      <button class="theme-toggle-btn" id="themeToggle" title="Toggle theme">🌙</button>
    </div>
  </header>

  <!-- ── Hero ── -->
  <section class="team-hero">
    <div class="hero-eyebrow">🔥 F.I.R.E.W.A.T.C.H System</div>
    <h1 class="hero-title">MEET THE <span>TEAM</span></h1>
    <p class="hero-sub">
      The people behind the real-time fire detection &amp; emergency control system —
      a group of passionate BSIT students building smarter, safer systems.
    </p>
    <p class="hero-meta">Isabela State University &nbsp;·&nbsp; BSIT 2-2 &nbsp;·&nbsp; 2026</p>
  </section>

  <!-- ── About Group ── -->
  <section class="about-section">
    <div class="about-card">
      <div>
        <div class="about-label">About Our Group</div>
        <h2 class="about-heading">Fire Watchers,<br/>Future Builders</h2>
        <p class="about-text">
          We are a team of five second-year Information Technology students from Isabela State University who share a passion for technology and innovation. Together, we developed F.I.R.E.W.A.T.C.H., a real-time fire detection and emergency control system powered by Arduino, designed to improve safety through intelligent automation and real-time sensor monitoring.
          <br/><br/>
          By combining our skills in web development, UI design, hardware integration, and programming, we created a system that aims to provide a reliable and efficient solution for fire detection and emergency response.
        </p>
      </div>
      <div class="stat-list">
        <div class="stat-item">
          <span class="stat-icon">👥</span>
          <div><div class="stat-num">5</div><div class="stat-lbl">Team Members</div></div>
        </div>
        <div class="stat-item">
          <span class="stat-icon">🎓</span>
          <div><div class="stat-num">BSIT</div><div class="stat-lbl">2nd Year · ISU Echague</div></div>
        </div>
        <div class="stat-item">
          <span class="stat-icon">🔥</span>
          <div><div class="stat-num">1</div><div class="stat-lbl">System Built Together</div></div>
        </div>
        <div class="stat-item">
          <span class="stat-icon">📍</span>
          <div><div class="stat-num">Isabela</div><div class="stat-lbl">Philippines</div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Members ── -->
  <section class="members-section">
    <div class="section-header">
      <span class="section-title">Group Members</span>
      <div class="section-rule"></div>
    </div>
    <div class="members-grid">
      <?php foreach ($members as $id => $m): ?>
        <?php $isTba = !empty($m['tba']); ?>
        <a
          href="<?= $isTba ? '#' : 'profile.php?member=' . urlencode($id) ?>"
          class="member-card<?= $isTba ? ' tba' : '' ?>"
        >
          <!-- Left fire -->
          <div class="flame-left">
            <div class="flame-particle f1" style="--dur:1.3s;--delay:0.0s"></div>
            <div class="flame-particle f2" style="--dur:1.1s;--delay:0.2s"></div>
            <div class="flame-particle f3" style="--dur:0.9s;--delay:0.4s"></div>
            <div class="flame-particle f1" style="--dur:1.5s;--delay:0.6s"></div>
            <div class="flame-particle f2" style="--dur:1.2s;--delay:0.1s"></div>
            <div class="flame-particle f3" style="--dur:1.0s;--delay:0.7s"></div>
          </div>
          <!-- Right fire -->
          <div class="flame-right">
            <div class="flame-particle f1" style="--dur:1.4s;--delay:0.3s"></div>
            <div class="flame-particle f2" style="--dur:1.0s;--delay:0.5s"></div>
            <div class="flame-particle f3" style="--dur:1.2s;--delay:0.0s"></div>
            <div class="flame-particle f1" style="--dur:1.6s;--delay:0.8s"></div>
            <div class="flame-particle f2" style="--dur:0.9s;--delay:0.2s"></div>
            <div class="flame-particle f3" style="--dur:1.3s;--delay:0.6s"></div>
          </div>
          <div class="member-avatar">
  <?php if (!empty($m['photo'])): ?>
    <img src="<?= htmlspecialchars($m['photo']) ?>" alt="<?= htmlspecialchars($m['name']) ?>">
  <?php else: ?>
    <?= htmlspecialchars($m['initials']) ?>
  <?php endif; ?>
</div>
          <div class="member-name"><?= htmlspecialchars($m['name']) ?></div>
          <div class="member-nick"><?= htmlspecialchars($m['nickname']) ?></div>
          <div class="member-course"><?= htmlspecialchars($m['course']) ?></div>
          <?php if ($isTba): ?>
            <span class="tba-badge">Profile Coming Soon</span>
          <?php else: ?>
            <span class="view-btn">View Profile →</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <script>
    // ── Theme toggle (mirrors your existing dashboard logic) ──
    const html = document.documentElement;
    const btn  = document.getElementById('themeToggle');
    const saved = localStorage.getItem('firewatch_theme') || 'dark';
    html.setAttribute('data-theme', saved);
    btn.textContent = saved === 'dark' ? '🌙' : '☀️';

    btn.addEventListener('click', () => {
      const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('firewatch_theme', next);
      btn.textContent = next === 'dark' ? '🌙' : '☀️';
    });
  </script>

</body>
</html>