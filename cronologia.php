<?php
error_reporting(0);
ini_set('display_errors', '0');

require 'config.php';
checkLogin();
$uid = $_SESSION['user_id'];

// --- API DETTAGLIO GIORNO ---
if (isset($_GET['get_day'])) {
    ob_start();
    header('Content-Type: application/json');

    $day = $_GET['get_day'];
    $checks = [];

    $s = $conn->prepare("SELECT a.nome, a.icona, a.modalita, ca.valore_inserito, ca.xp_guadagnato FROM check_abitudini ca JOIN abitudini a ON ca.abitudine_id=a.id WHERE ca.utente_id=? AND ca.data_check=?");
    $s->bind_param("is", $uid, $day);
    $s->execute();
    $res = $s->get_result();
    while ($r = $res->fetch_assoc()) {
        $checks[] = $r;
    }

    $sm = $conn->prepare("SELECT testo FROM momenti WHERE utente_id=? AND data_momento=?");
    $sm->bind_param("is", $uid, $day);
    $sm->execute();
    $mom = $sm->get_result()->fetch_assoc();

    $testo_dec = '';
    if ($mom && !empty($mom['testo'])) {
        $testo_dec = @decryptText($mom['testo']);
        if ($testo_dec === false)
            $testo_dec = '[Errore di lettura]';
    }

    ob_end_clean();

    echo json_encode(['checks' => $checks, 'momento' => $testo_dec]);
    exit;
}

// --- GENERAZIONE HEATMAP DIVISA PER MESI ---
$mesi_it = ['January' => 'Gennaio', 'February' => 'Febbraio', 'March' => 'Marzo', 'April' => 'Aprile', 'May' => 'Maggio', 'June' => 'Giugno', 'July' => 'Luglio', 'August' => 'Agosto', 'September' => 'Settembre', 'October' => 'Ottobre', 'November' => 'Novembre', 'December' => 'Dicembre'];
$heatmap_per_mese = [];

for ($i = 179; $i >= 0; $i--) {
    $d = new DateTime("-$i days");
    $key = $d->format('Y-m-d');
    $mese_en = $d->format('F Y');
    $mese_it = strtr($mese_en, $mesi_it);

    $s = $conn->prepare("SELECT COUNT(DISTINCT a.id) as tot_ab, COUNT(DISTINCT ca.id) as tot_ck 
                       FROM abitudini a 
                       LEFT JOIN check_abitudini ca ON a.id=ca.abitudine_id AND ca.data_check=? 
                       AND (
                           a.modalita='check' OR 
                           (a.modalita='contatore' AND IF(a.inverso=1, (ca.valore_inserito > 0 AND ca.valore_inserito <= a.obiettivo), ca.valore_inserito >= a.obiettivo))
                       ) 
                       WHERE a.utente_id=? AND a.tipo='giornaliera' AND a.attiva=1");
    $s->bind_param("si", $key, $uid);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();

    $sm = $conn->prepare("SELECT id FROM momenti WHERE utente_id=? AND data_momento=? AND testo IS NOT NULL AND testo != ''");
    $sm->bind_param("is", $uid, $key);
    $sm->execute();
    $has_mom = $sm->get_result()->num_rows > 0;

    $pct = $r['tot_ab'] > 0 ? $r['tot_ck'] / $r['tot_ab'] : 0;
    $lvl = $pct == 0 ? 0 : ($pct < 0.5 ? 1 : ($pct < 1 ? 2 : 3));
    $mom_class = $has_mom ? ' has-mom' : '';

    // Raggruppo nel mese corrispondente
    if (!isset($heatmap_per_mese[$mese_it])) {
        $heatmap_per_mese[$mese_it] = [];
    }
    $heatmap_per_mese[$mese_it][] = ['date' => $key, 'lvl' => $lvl, 'mom_class' => $mom_class];
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0a0a0a">

<title>Storico - GamifyLife</title>

<meta name="description" content="Costruisci abitudini, guadagna XP, sale di livello.">
<link rel="icon" href="icona.png" type="image/png" sizes="32x32">
<link rel="icon" href="icona.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="icona.png">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= APP_URL; ?>/icona.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= APP_URL; ?>/icona.png">

<link rel="stylesheet" href="style.css">
<?php if(basename($_SERVER['PHP_SELF']) === 'gestione_abitudini.php'): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<?php endif; ?>
</head>

<body>

    <div class="container">
        <div class="page-header">
            <a href="index.php" class="back-btn" title="Indietro">←</a>
            <h2 style="font-size:1.1em;margin:0;">Storico</h2>
            <div style="width:40px;"></div>
        </div>

        <div class="card">
            <h3 style="margin:0 0 16px;font-size:.95em;">Heatmap (6 mesi)</h3>

            <!-- Contenitore generale usato da JS per catturare i click -->
            <div class="heatmap-wrapper" id="heatmapWrapper">
                <?php foreach (array_reverse($heatmap_per_mese, true) as $nome_mese => $giorni): ?>
                    <div class="month-label"><?= $nome_mese; ?></div>
                    <div class="heatmap-grid">
                        <?php foreach ($giorni as $h): ?>
                            <div class="hm-cell lvl<?= $h['lvl']; ?><?= $h['mom_class']; ?>" data-date="<?= $h['date']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div
                style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;font-size:.75em;color:var(--dim);">
                <span>Meno</span>
                <div style="display:flex;gap:5px;">
                    <div class="hm-cell lvl0" style="width:14px;aspect-ratio:1/1;cursor:default;pointer-events:none;">
                    </div>
                    <div class="hm-cell lvl1" style="width:14px;aspect-ratio:1/1;cursor:default;pointer-events:none;">
                    </div>
                    <div class="hm-cell lvl2" style="width:14px;aspect-ratio:1/1;cursor:default;pointer-events:none;">
                    </div>
                    <div class="hm-cell lvl3" style="width:14px;aspect-ratio:1/1;cursor:default;pointer-events:none;">
                    </div>
                </div>
                <span>Più</span>
            </div>
        </div>
    </div>

    <!-- MODAL GIORNO -->
    <div class="modal-overlay" id="dayModal" onclick="closeDay(event)">
        <div class="modal-sheet" onclick="event.stopPropagation()">
            <div class="modal-handle"></div>
            <h3 style="text-align:center;margin-bottom:16px;" id="dayTitle">Data</h3>
            <div id="dayContent"></div>
            <button class="btn-text" onclick="closeDay()" style="margin-top:20px;">Chiudi</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ascolto il click sull'intero contenitore che racchiude TUTTI i mesi
            const wrapper = document.getElementById('heatmapWrapper');
            if (!wrapper) {
                console.error("Errore critico: Heatmap non trovata.");
                return;
            }

            wrapper.addEventListener('click', function (e) {
                const cell = e.target.closest('.hm-cell');
                if (cell) {
                    const date = cell.getAttribute('data-date');
                    if (date) showDay(date);
                }
            });
        });

        async function showDay(date) {
            const d = new Date(date + 'T00:00:00');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('dayTitle').textContent = d.toLocaleDateString('it-IT', options);
            document.getElementById('dayContent').innerHTML = '<p style="text-align:center;color:#888;">Caricamento...</p>';

            try {
                const res = await fetch('cronologia.php?get_day=' + date);
                if (!res.ok) throw new Error('Errore Server: ' + res.status);

                const data = await res.json();
                let html = '';

                if (data.checks.length === 0 && !data.momento) {
                    html = '<p style="text-align:center;color:#666;">Nessuna attività registrata.</p>';
                } else {
                    if (data.checks.length > 0) {
                        data.checks.forEach(c => {
                            const val = c.valore_inserito ? ' (' + c.valore_inserito + ')' : '';
                            html += '<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:.95em;">' +
                                '<span>' + c.icona + ' ' + c.nome + val + '</span>' +
                                '<span style="color:var(--primary);font-weight:600;">+' + c.xp_guadagnato + '</span>' +
                                '</div>';
                        });
                    }

                    if (data.momento) {
                        html += '<div style="margin-top:16px;background:var(--input);padding:14px;border-radius:12px;font-size:.9em;line-height:1.5;">' +
                            '<div style="font-size:.8em;color:var(--muted);margin-bottom:6px;">✍️ MEMORABLE MOMENT</div>' +
                            data.momento +
                            '</div>';
                    }
                }
                document.getElementById('dayContent').innerHTML = html;
                document.getElementById('dayModal').classList.add('show');
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Errore fetch giorno:', error);
                document.getElementById('dayContent').innerHTML = '<p style="text-align:center;color:var(--danger);">Impossibile caricare i dati.</p>';
                document.getElementById('dayModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDay(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('dayModal').classList.remove('show');
            document.body.style.overflow = '';
        }
    </script>
</body>

</html>