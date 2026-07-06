<?php require 'config.php';
checkLogin();
$uid = $_SESSION['user_id']; ?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="description"
        content="Costruisci abitudini, guadagna XP, sale di livello. Il tuo habit tracker gamificato.">
    <title>GamifyLife</title>

    <!-- Icona del tab del browser e home screen iOS/Android -->
    <link rel="icon" href="icona.png" type="image/png" sizes="32x32">
    <link rel="icon" href="icona.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="icona.png">

    <!-- Immagine anteprima per quando condividi il link (WhatsApp, Telegram, Facebook, Discord) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="GamifyLife">
    <meta property="og:description"
        content="Costruisci abitudini, guadagna XP, sale di livello. Il tuo habit tracker gamificato.">
    <meta property="og:image" content="<?= APP_URL; ?>/icona.png">
    <meta property="og:url" content="<?= APP_URL; ?>/index.php">

    <!-- Anteprima per Twitter/X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GamifyLife">
    <meta name="twitter:image" content="<?= APP_URL; ?>/icona.png">

    <!-- Stile e PWA -->
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
</head>

<body>

    <div class="container">
        <div class="card profile-card">
            <div class="profile-top">
                <div>
                    <div class="profile-name" id="pNome">Caricamento...</div>
                    <div class="profile-title" id="pTitolo"></div>
                    <div class="profile-level" id="pLivello"></div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <div id="scudoIcon" class="icon-btn"
                        style="font-size:.9em;width:auto;padding:0 8px;cursor:default;display:none;"
                        title="Scudi antifallas">🛡️ <span id="scudoNum">0</span></div>
                    <a href="cronologia.php" class="icon-btn">📊</a>
                    <a href="impostazioni.php" class="icon-btn">⚙️</a>
                </div>
            </div>
            <div class="profile-stats-row">
                <div class="stat-chip"><span class="stat-val" style="color:var(--primary);" id="pMolt">x1.00</span><span
                        class="stat-label">Molt.</span></div>
                <div class="stat-chip"><span class="stat-val" style="color:var(--warning);" id="pStreak">🔥
                        0</span><span class="stat-label">Streak</span></div>
            </div>
            <div class="xp-container">
                <div class="xp-text"><span id="pXp">XP: 0</span><span id="pXpManc">-0 al lv</span></div>
                <div class="xp-bar-bg">
                    <div class="xp-bar-fill" id="pXpBar" style="width:0%;"></div>
                </div>
            </div>
        </div>

        <div id="bannerBonus" class="bonus-banner" style="display:none;"></div>

        <div class="section-header" id="secGiorn"><span>📅 Oggi</span><span class="section-badge"
                id="badgeGiorn">0/0</span></div>
        <div class="daily-progress-bar" id="progGiorn">
            <div class="daily-progress-fill" id="progGiornFill" style="width:0%;"></div>
        </div>
        <div id="listGiorn"></div>

        <div class="section-header" id="secSett" style="display:none;margin-top:24px;"><span>📆 Questa
                Settimana</span><span class="section-badge" id="badgeSett">0/0</span></div>
        <div id="listSett"></div>

        <div class="card" style="margin-top:24px;">
            <div class="section-header" style="margin:0 0 10px;"><span>✍️ Memorable Moments</span></div>
            <textarea id="momentoTxt" placeholder="Come è andata la giornata? Cosa hai imparato..."
                style="width:100%;height:80px;resize:none;background:var(--input);border:1.5px solid var(--border);border-radius:12px;padding:12px;color:var(--text);font-size:15px;outline:none;font-family:inherit;transition:all .2s;"></textarea>
            <button id="momentoBtn" class="btn-primary" style="margin-top:8px;font-size:14px;padding:10px;"
                onclick="saveMoment()">Salva Pensiero</button>
        </div>
    </div>

    <a href="gestione_abitudini.php" class="fab">✨</a>

    <div class="modal-overlay" id="counterModal" onclick="closeCounter(event)">
        <div class="modal-sheet" onclick="event.stopPropagation()">
            <div class="modal-handle"></div>
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:2.5em;" id="mIcon">💧</div>
                <h3 style="margin:6px 0 2px;" id="mTitle">Nome</h3>
                <div style="color:#888;font-size:.85em;" id="mTarget">Obiettivo</div>
            </div>
            <div class="slider-wrap"><input type="range" id="mSlider" min="0" max="20" value="0" oninput="syncSlider()">
            </div>
            <div class="counter-row">
                <button type="button" class="counter-btn" onclick="adj(-1)">−</button>
                <div class="counter-display" id="mDisplay">0</div>
                <button type="button" class="counter-btn" onclick="adj(1)">+</button>
            </div>
            <button class="btn-primary" style="margin-top:16px;" onclick="submitCounter()">SALVA</button>
            <button class="btn-text" onclick="closeCounter()" style="margin-top:8px;">Annulla</button>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const uid = <?= $uid ?>;
        let currentState = {};
        let activeCounterId = null;
        let activeCounterTipo = '';
        let activeCounterMeta = {}; // FIX BUG: Salva i metadati senza parsare il DOM

        async function init() {
            try {
                const res = await fetch('api.php?action=get_state');
                if (!res.ok) throw new Error();
                currentState = await res.json();
                updateUI();
                await loadHabits();
            } catch (e) { console.error("Init error", e); }
        }

        function updateUI() {
            const s = currentState, u = s.user;
            document.getElementById('pNome').textContent = getGreeting() + ' ' + '<?= htmlspecialchars($_SESSION["username"] ?? "") ?>';
            document.getElementById('pTitolo').textContent = s.titolo;
            document.getElementById('pLivello').textContent = 'Livello ' + s.level.livello;
            document.getElementById('pMolt').textContent = 'x' + parseFloat(u.moltiplicatore).toFixed(2);
            document.getElementById('pStreak').textContent = '🔥 ' + u.giorni_streak;
            document.getElementById('pXp').textContent = 'XP: ' + Number(s.xp_totale).toLocaleString();
            document.getElementById('pXpManc').textContent = '-' + s.level.xp_mancanti + ' al lv';
            document.getElementById('pXpBar').style.width = Math.min(s.level.percentuale, 100) + '%';

            if (u.scudi_rimanenti > 0) { document.getElementById('scudoIcon').style.display = 'flex'; document.getElementById('scudoNum').textContent = u.scudi_rimanenti; }
            else document.getElementById('scudoIcon').style.display = 'none';

            if (s.bonus_oggi) {
                const b = document.getElementById('bannerBonus');
                b.style.display = 'block'; b.textContent = '🔥 Giornata Perfetta! +' + s.bonus_oggi.xp_guadagnato + ' XP';
                if (!window.confettiFired) { fireConfetti(); window.confettiFired = true; }
            } else { document.getElementById('bannerBonus').style.display = 'none'; window.confettiFired = false; }

            const txt = document.getElementById('momentoTxt'), btn = document.getElementById('momentoBtn');
            if (s.momento_oggi && s.momento_oggi.trim() !== '') {
                txt.value = s.momento_oggi; txt.disabled = true; txt.classList.add('disabled');
                btn.textContent = '✏️ Modifica'; btn.className = 'btn-secondary';
                btn.onclick = function () { txt.disabled = false; txt.classList.remove('disabled'); btn.textContent = 'Salva Pensiero'; btn.className = 'btn-primary'; btn.onclick = saveMoment; txt.focus(); };
            } else {
                txt.value = ''; txt.disabled = false; txt.classList.remove('disabled');
                btn.textContent = 'Salva Pensiero'; btn.className = 'btn-primary'; btn.onclick = saveMoment;
            }
        }

        async function loadHabits() {
            const res = await fetch('gestione_abitudini.php?get_habits=1');
            const data = await res.json();
            renderList('giornaliera', data.giornaliere, 'listGiorn', 'secGiorn', 'badgeGiorn', 'progGiornFill');
            renderList('settimanale', data.settimanali, 'listSett', 'secSett', 'badgeSett', null);
        }

        function renderList(tipo, habits, listId, secId, badgeId, fillId) {
            const list = document.getElementById(listId), sec = document.getElementById(secId);
            if (habits.length === 0) { sec.style.display = 'none'; list.innerHTML = ''; return; }
            sec.style.display = 'flex';
            let html = '', completate = 0;

            habits.forEach(h => {
                const ck = h.check;
                if (h.modalita === 'check') {
                    if (ck) completate++;
                    html += `<div class="habit-row ${ck ? 'checked' : ''}" onclick="toggleCheck(${h.id}, '${tipo}')" id="hr_${h.id}">
                <span class="habit-icon">${h.icona}</span>
                <span class="habit-name">${h.nome}</span>
                <span class="habit-xp">+${h.xp_ricompensa}</span>
                <span class="habit-check">${ck ? '✓' : ''}</span>
            </div>`;
                } else {
                    const val = ck ? (ck.valore_inserito || 0) : 0;
                    const done = !h.inverso ? (val >= h.obiettivo) : (val > 0 && val <= h.obiettivo);
                    const partial = val > 0 && !done;
                    if (done) completate++;
                    html += `<div class="habit-row ${done ? 'checked' : (partial ? 'partial' : '')} ${tipo === 'settimanale' ? 'settimanale' : ''}" onclick="openCounter(${h.id},'${h.nome.replace(/'/g, "\\'")}','${h.icona}',${h.obiettivo},'${h.unita}',${h.xp_ricompensa},'${tipo}',${val})" id="hr_${h.id}">
                <span class="habit-icon">${h.icona}</span>
                <span class="habit-name">${h.nome}</span>
                <span class="habit-counter-val ${done ? 'done' : (partial ? 'partial' : '')}" id="hcv_${h.id}">${val}/${h.obiettivo} ${h.unita}</span>
                <span class="habit-check" id="hck_${h.id}">${done ? '✓' : ''}</span>
            </div>`;
                }
            });
            list.innerHTML = html;
            document.getElementById(badgeId).textContent = completate + '/' + habits.length;
            if (fillId) document.getElementById(fillId).style.width = (completate / habits.length * 100) + '%';
        }

        function updateLocalBadges() {
            ['Giorn', 'Sett'].forEach(type => {
                const list = document.getElementById('list' + type);
                if (!list || list.children.length === 0) return;
                let done = 0; let total = list.children.length;
                Array.from(list.children).forEach(row => { if (row.classList.contains('checked')) done++; });
                document.getElementById('badge' + type).textContent = done + '/' + total;
                if (type === 'Giorn') document.getElementById('progGiornFill').style.width = (total > 0 ? done / total * 100 : 0) + '%';
            });
        }

        async function refreshStatsOnly() {
            try {
                const res = await fetch('api.php?action=get_state');
                if (!res.ok) throw new Error();
                currentState = await res.json();
                updateUI();
            } catch (e) { console.error(e); }
        }

        async function toggleCheck(id, tipo) {
            const row = document.getElementById('hr_' + id);
            if (!row) return;
            vibrate(10); row.style.transform = 'scale(0.95)';

            try {
                const fd = new FormData(); fd.append('action', 'toggle_check'); fd.append('ab_id', id); fd.append('tipo', tipo);
                const res = await fetch('api.php', { method: 'POST', body: fd });
                if (!res.ok) throw new Error();
                const data = await res.json();

                if (data.status === 'checked') {
                    row.classList.add('checked'); row.querySelector('.habit-check').textContent = '✓';
                    showToast('+' + data.xp + ' XP');
                    if (data.scudo_usato) setTimeout(() => showToast('🛡️ Scudo usato!'), 300);
                    if (data.bonus) {
                        setTimeout(() => { showToast('🔥 Giornata Perfetta! +' + data.bonus + ' XP'); fireConfetti(); window.confettiFired = true; }, 600);
                    }
                } else {
                    row.classList.remove('checked'); row.querySelector('.habit-check').textContent = '';
                }
                row.style.transform = '';
                updateLocalBadges();
                refreshStatsOnly();
            } catch (e) { row.style.transform = ''; showToast('Errore di rete'); }
        }

        function openCounter(id, nome, icona, obiettivo, unita, xp, tipo, val) {
            activeCounterId = id; activeCounterTipo = tipo; vibrate(10);

            // FIX BUG: Salvo i metadati in modo pulito, senza parsare il DOM dopo
            activeCounterMeta = { obiettivo: parseInt(obiettivo), unita: unita, inverso: false }; // l'inverso si gestisce lato server per l'xp

            document.getElementById('mIcon').textContent = icona;
            document.getElementById('mTitle').textContent = nome;
            document.getElementById('mTarget').textContent = obiettivo ? 'Obiettivo: ' + obiettivo + ' ' + unita : 'Inserisci valore';
            const sl = document.getElementById('mSlider');
            sl.max = Math.max(obiettivo * 2, obiettivo + 5); sl.value = val;
            document.getElementById('mDisplay').textContent = val;
            syncSlider();
            document.getElementById('counterModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeCounter(e) { if (e && e.target !== e.currentTarget) return; document.getElementById('counterModal').classList.remove('show'); document.body.style.overflow = ''; }
        function adj(d) { const sl = document.getElementById('mSlider'); sl.value = Math.max(0, parseInt(sl.value || 0) + d); syncSlider(); }
        function syncSlider() {
            const sl = document.getElementById('mSlider'), v = sl.value;
            document.getElementById('mDisplay').textContent = v;
            const pct = (v - sl.min) / (sl.max - sl.min) * 100;
            sl.style.background = 'linear-gradient(to right,var(--primary) ' + pct + '%,var(--input) ' + pct + '%)';
        }

        async function submitCounter() {
            const val = document.getElementById('mSlider').value;
            const fd = new FormData(); fd.append('action', 'save_counter'); fd.append('ab_id', activeCounterId); fd.append('val', val); fd.append('tipo', activeCounterTipo);

            try {
                const res = await fetch('api.php', { method: 'POST', body: fd });
                if (!res.ok) throw new Error();
                const data = await res.json();
                closeCounter();

                // FIX BUG: Aggiornamento visivo robusto usando i metadati salvati
                const row = document.getElementById('hr_' + activeCounterId);
                if (row) {
                    const { obiettivo, unita } = activeCounterMeta;
                    const hcv = document.getElementById('hcv_' + activeCounterId);
                    const hck = document.getElementById('hck_' + activeCounterId);

                    // Calcolo lo stato visivo basandomi sui dati che ho già lato client
                    const done = val >= obiettivo;
                    const partial = val > 0 && !done;

                    hcv.textContent = val + '/' + obiettivo + ' ' + unita;
                    hcv.className = 'habit-counter-val ' + (done ? 'done' : (partial ? 'partial' : ''));

                    if (done) { row.classList.add('checked'); row.classList.remove('partial'); hck.textContent = '✓'; }
                    else { row.classList.remove('checked'); row.classList.toggle('partial', partial); hck.textContent = ''; }
                }

                if (data.status === 'saved' && data.xp) showToast('+' + data.xp + ' XP');
                if (data.scudo_usato) setTimeout(() => showToast('🛡️ Scudo usato!'), 300);
                if (data.bonus) {
                    setTimeout(() => { showToast('🔥 Perfetta! +' + data.bonus + ' XP'); fireConfetti(); window.confettiFired = true; }, 600);
                }

                updateLocalBadges();
                refreshStatsOnly();
            } catch (e) { closeCounter(); showToast('Errore di rete'); }
        }

        async function saveMoment() {
            const txt = document.getElementById('momentoTxt').value;
            const fd = new FormData(); fd.append('action', 'save_moment'); fd.append('testo', txt);
            await fetch('api.php', { method: 'POST', body: fd });
            vibrate(5); showToast('Salvato!');

            const btn = document.getElementById('momentoBtn'), area = document.getElementById('momentoTxt');
            area.disabled = true; area.classList.add('disabled');
            btn.textContent = '✏️ Modifica'; btn.className = 'btn-secondary';
            btn.onclick = function () { area.disabled = false; area.classList.remove('disabled'); btn.textContent = 'Salva Pensiero'; btn.className = 'btn-primary'; btn.onclick = saveMoment; area.focus(); };
        }

        function vibrate(ms) { if (navigator.vibrate) navigator.vibrate(ms); }
        function fireConfetti() { confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 }, colors: ['#30d158', '#ff9f0a', '#fff'] }); }
        function getGreeting() { const h = new Date().getHours(); if (h < 6) return '🌙 Buonanotte'; if (h < 12) return '☀️ Buongiorno'; if (h < 18) return '🌤️ Buon pomeriggio'; return '🌙 Buonasera'; }
        function showToast(msg) { const t = document.getElementById('toast'); t.textContent = msg; t.classList.add('show'); setTimeout(() => t.classList.remove('show'), 2500); }

        init();
    </script>
</body>

</html>