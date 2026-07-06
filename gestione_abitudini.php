<?php
require 'config.php';
checkLogin();
$uid = $_SESSION['user_id'];
$msg = "";

// --- API PER GLI HABITS (Chiamata da index.php) ---
if (isset($_GET['get_habits'])) {
    header('Content-Type: application/json');
    $oggi = date('Y-m-d');
    $lun = getLunedi();
    $dom = getDomenica();

    function getHabits($conn, $uid, $tipo, $oggi, $lun, $dom)
    {
        $s = $conn->prepare("SELECT * FROM abitudini WHERE utente_id=? AND tipo=? AND attiva=1 ORDER BY ordine ASC, id ASC");
        $s->bind_param("is", $uid, $tipo);
        $s->execute();
        $res = $s->get_result();
        $arr = [];
        while ($ab = $res->fetch_assoc()) {
            $ck = null;
            if ($tipo === 'settimanale') {
                $sc = $conn->prepare("SELECT * FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check BETWEEN ? AND ?");
                $sc->bind_param("iiss", $ab['id'], $uid, $lun, $dom);
            } else {
                $sc = $conn->prepare("SELECT * FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check=?");
                $sc->bind_param("iis", $ab['id'], $uid, $oggi);
            }
            $sc->execute();
            $ck = $sc->get_result()->fetch_assoc();
            $arr[] = ['id' => $ab['id'], 'nome' => $ab['nome'], 'modalita' => $ab['modalita'], 'obiettivo' => $ab['obiettivo'], 'unita' => $ab['unita'], 'inverso' => $ab['inverso'], 'xp_ricompensa' => $ab['xp_ricompensa'], 'icona' => $ab['icona'], 'check' => $ck];
        }
        return $arr;
    }
    echo json_encode([
        'giornaliere' => getHabits($conn, $uid, 'giornaliera', $oggi, $lun, $dom),
        'settimanali' => getHabits($conn, $uid, 'settimanale', $oggi, $lun, $dom)
    ]);
    exit;
}

// --- API SALVATAGGIO ORDINE DRAG & DROP ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_order') {
    header('Content-Type: application/json');
    $ordini = $_POST['ordini'] ?? [];
    $ordine = 1;
    foreach ($ordini as $ab_id) {
        $ab_id = (int) $ab_id;
        if ($ab_id > 0) {
            $stmt = $conn->prepare("UPDATE abitudini SET ordine = ? WHERE id = ? AND utente_id = ?");
            $stmt->bind_param("iii", $ordine, $ab_id, $uid);
            $stmt->execute();
            $ordine++;
        }
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

// --- AZIONI GET STANDARD ---
if (isset($_GET['toggle_attiva'])) {
    $id = (int) $_GET['toggle_attiva'];
    $stmt = $conn->prepare("UPDATE abitudini SET attiva=IF(attiva=1,0,1) WHERE id=? AND utente_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    header("Location: gestione_abitudini.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM abitudini WHERE id=? AND utente_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    header("Location: gestione_abitudini.php");
    exit;
}

// --- INSERIMENTO NUOVA ABITUDINE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_habit'])) {
    $nome = trim($_POST['nome'] ?? '');
    $tipo = in_array($_POST['tipo'] ?? '', ['giornaliera', 'settimanale']) ? $_POST['tipo'] : 'giornaliera';
    $modalita = in_array($_POST['modalita'] ?? '', ['check', 'contatore']) ? $_POST['modalita'] : 'check';
    $icona = trim($_POST['icona'] ?? '✅');
    $xp = (int) ($_POST['xp'] ?? 15);
    $inverso = isset($_POST['inverso']) ? 1 : 0;

    $obiettivo = NULL;
    $unita = '';
    if ($modalita === 'contatore') {
        $obiettivo = isset($_POST['obiettivo']) && $_POST['obiettivo'] !== '' ? (int) $_POST['obiettivo'] : 10;
        $unita = trim($_POST['unita'] ?? '');
    }

    if (!empty($nome) && $xp > 0) {
        $sOrd = $conn->prepare("SELECT MAX(ordine) as m FROM abitudini WHERE utente_id=?");
        $sOrd->bind_param("i", $uid);
        $sOrd->execute();
        $rOrd = $sOrd->get_result()->fetch_assoc();
        $next_ord = ($rOrd['m'] ?? 0) + 1;

        $stmt = $conn->prepare("INSERT INTO abitudini (utente_id, nome, tipo, modalita, obiettivo, unita, inverso, xp_ricompensa, icona, ordine) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issisiisii", $uid, $nome, $tipo, $modalita, $obiettivo, $unita, $inverso, $xp, $icona, $next_ord);
        $stmt->execute();
        $msg = "Abitudine aggiunta!";
    }
}

// --- MODIFICA ABITUDINE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_habit'])) {
    $id = (int) $_POST['edit_id'];
    $nome = trim($_POST['nome'] ?? '');
    $tipo = in_array($_POST['tipo'] ?? '', ['giornaliera', 'settimanale']) ? $_POST['tipo'] : 'giornaliera';
    $modalita = in_array($_POST['modalita'] ?? '', ['check', 'contatore']) ? $_POST['modalita'] : 'check';
    $icona = trim($_POST['icona'] ?? '✅');
    $xp = (int) ($_POST['xp'] ?? 15);
    $inverso = isset($_POST['inverso']) ? 1 : 0;

    $obiettivo = NULL;
    $unita = '';
    if ($modalita === 'contatore') {
        $obiettivo = isset($_POST['obiettivo']) && $_POST['obiettivo'] !== '' ? (int) $_POST['obiettivo'] : 10;
        $unita = trim($_POST['unita'] ?? '');
    }

    if (!empty($nome) && $xp > 0) {
        $stmt = $conn->prepare("UPDATE abitudini SET nome=?, tipo=?, modalita=?, obiettivo=?, unita=?, inverso=?, xp_ricompensa=?, icona=? WHERE id=? AND utente_id=?");
        $stmt->bind_param("sssisiisii", $nome, $tipo, $modalita, $obiettivo, $unita, $inverso, $xp, $icona, $id, $uid);
        $stmt->execute();
        $msg = "Abitudine aggiornata!";
    }
}

// --- PRELEVO TUTTE LE ABITUDINI ---
$stmt = $conn->prepare("SELECT * FROM abitudini WHERE utente_id=? ORDER BY tipo, ordine ASC, id ASC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$abitudini = $stmt->get_result();

$icone = ['💧', '🏃', '📖', '😴', '🧘', '🍎', '💪', '🧹', '💊', '📝', '🎯', '⭐', '🔥', '🌿', '💤', '🍽️', '🚶', '🧠', '💰', '🎵', '💻', '🎨', '🚿', '🥗', '☕', '📱', '🐕', '🚴', '🏊', '🧊'];
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0a0a0a">

<title>Gestisci Abitudini - GamifyLife</title>

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
            <a href="index.php" class="back-btn" title="Indietro">←</a>
            <h2 style="font-size:1.1em;margin:0;">Abitudini</h2>
            <div style="width:40px;"></div>
        </div>

        <?php if ($msg): ?>
            <div class="msg-ok"><?= $msg; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-toggle-header" onclick="toggleSection('addForm')">
                <span>+ Nuova Abitudine</span>
                <span id="addFormArrow" class="arrow">▾</span>
            </div>
            <div id="addForm" class="card-body" style="display:none;">
                <form method="POST">
                    <input type="text" name="nome" placeholder="Nome abitudine..." required>

                    <div class="seg-row" id="add_tipo_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;">Quando:</span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn active" data-val="giornaliera"
                                onclick="setSeg('add', 'tipo', 'giornaliera', this)">📅 Giorno</button>
                            <button type="button" class="seg-btn" data-val="settimanale"
                                onclick="setSeg('add', 'tipo', 'settimanale', this)">📆 Settimana</button>
                        </div>
                    </div>
                    <input type="hidden" name="tipo" id="add_tipo" value="giornaliera">

                    <div class="seg-row" id="add_mode_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;">Come:</span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn active" data-val="check"
                                onclick="setSeg('add', 'mode', 'check', this)">✓ Check</button>
                            <button type="button" class="seg-btn" data-val="contatore"
                                onclick="setSeg('add', 'mode', 'contatore', this)">🔢 Contatore</button>
                        </div>
                    </div>
                    <input type="hidden" name="modalita" id="add_mode" value="check">

                    <div id="add_cf" style="display:none;">
                        <div style="display:flex;gap:8px;">
                            <input type="number" name="obiettivo" value="10" min="1" placeholder="Obiettivo"
                                style="flex:1;">
                            <input type="text" name="unita" value="" placeholder="unità (es. ore)" style="flex:1;">
                        </div>
                    </div>

                    <label
                        style="display:flex;align-items:center;gap:8px;margin:12px 0;font-size:.9em;color:var(--muted);cursor:pointer;">
                        <input type="checkbox" name="inverso" value="1"
                            style="width:20px;height:20px;accent-color:var(--danger);"> Logica inversa (es. Ore
                        telefono)
                    </label>

                    <div class="seg-row" id="add_diff_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;display:flex;align-items:center;">
                            Sforzo:
                            <div class="tooltip-trigger" onclick="toggleTooltip(event, this)">
                                ?
                                <div class="tooltip-box">
                                    <p style="margin:0 0 6px;"><strong>Quanto sforzo ti costa?</strong></p>
                                    <p style="margin:0 0 4px;">😌 <strong>Facile (15 XP):</strong> Quasi automatico.</p>
                                    <p style="margin:0 0 4px;">🤔 <strong>Medio (30 XP):</strong> Richiede un po' di
                                        spinta.</p>
                                    <p style="margin:0;">💪 <strong>Difficile (50 XP):</strong> Una vera sfida.</p>
                                </div>
                            </div>
                        </span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn active" data-xp="15"
                                onclick="setDiff('add', 15, this)">😌 Facile</button>
                            <button type="button" class="seg-btn" data-xp="30" onclick="setDiff('add', 30, this)">🤔
                                Medio</button>
                            <button type="button" class="seg-btn" data-xp="50" onclick="setDiff('add', 50, this)">💪
                                Difficile</button>
                        </div>
                    </div>
                    <input type="hidden" name="xp" id="add_xp" value="15">

                    <input type="hidden" name="icona" id="add_icona" value="💧">
                    <div class="icon-grid" id="addIconGrid">
                        <?php foreach ($icone as $ic): ?>
                            <span class="icon-option <?= $ic === '💧' ? 'selected' : ''; ?>"
                                onclick="pickIcon('add','<?= $ic; ?>',this)"><?= $ic; ?></span>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="add_habit" class="btn-primary">Aggiungi</button>
                </form>
            </div>
        </div>

        <?php
        $lt = '';
        while ($ab = $abitudini->fetch_assoc()):
            if ($ab['tipo'] !== $lt):
                $lt = $ab['tipo'];
                $list_id = $ab['tipo'] === 'giornaliera' ? 'sort-giorn' : 'sort-sett';
                $titolo = $ab['tipo'] === 'giornaliera' ? '📅 Trascina per riordinare (Giornaliere)' : '📆 Trascina per riordinare (Settimanali)';
                ?>
                <div class="list-section-title"><?= $titolo; ?></div>
                <div id="<?= $list_id; ?>" class="sort-list">
                <?php endif; ?>

                <div class="habit-manage-row <?= $ab['attiva'] ? '' : 'disabled'; ?>" data-id="<?= $ab['id']; ?>"
                    data-nome="<?= htmlspecialchars($ab['nome']); ?>" data-tipo="<?= $ab['tipo']; ?>"
                    data-modalita="<?= $ab['modalita']; ?>" data-obiettivo="<?= $ab['obiettivo']; ?>"
                    data-unita="<?= htmlspecialchars($ab['unita']); ?>" data-inverso="<?= $ab['inverso']; ?>"
                    data-xp="<?= $ab['xp_ricompensa']; ?>" data-icona="<?= $ab['icona']; ?>">
                    <div class="drag-handle" title="Trascina">⠿</div>
                    <div class="hmr-main" onclick="openEdit(<?= $ab['id']; ?>)">
                        <span style="font-size:1.4em;margin-right:10px;"><?= $ab['icona']; ?></span>
                        <div style="flex:1;min-width:0;">
                            <div
                                style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;<?= $ab['attiva'] ? 'color:white;' : 'color:#555;'; ?>">
                                <?= htmlspecialchars($ab['nome']); ?>
                            </div>
                            <div style="font-size:.75em;color:#666;">
                                <?php if ($ab['modalita'] === 'contatore'): ?>
                                    <?= $ab['inverso'] ? '⬇️' : '⬆️'; ?>         <?= $ab['obiettivo']; ?>         <?= $ab['unita']; ?> •
                                <?php endif; ?>
                                +<?= $ab['xp_ricompensa']; ?> XP
                            </div>
                        </div>
                    </div>
                    <div class="hmr-actions">
                        <a href="?toggle_attiva=<?= $ab['id']; ?>" class="toggle-btn <?= $ab['attiva'] ? 'on' : 'off'; ?>"
                            onclick="event.stopPropagation()"><?= $ab['attiva'] ? 'ON' : 'OFF'; ?></a>
                        <a href="?delete_id=<?= $ab['id']; ?>" class="delete-btn"
                            onclick="event.stopPropagation();return confirm('Eliminare?')">🗑️</a>
                    </div>
                </div>

            <?php endwhile; ?>
            <?php if ($lt !== '')
                echo "</div>"; ?>
        </div>

        <!-- MODAL MODIFICA (Popolato interamente via JS) -->
        <div class="modal-overlay" id="editModal" onclick="closeEdit(event)">
            <div class="modal-sheet" onclick="event.stopPropagation()">
                <div class="modal-handle"></div>
                <h3 style="margin-bottom:16px;">Modifica Abitudine</h3>
                <form method="POST" id="editForm">
                    <input type="hidden" name="edit_id" id="e_id">
                    <input type="text" name="nome" id="e_nome" required>

                    <div class="seg-row" id="edit_tipo_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;">Quando:</span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn" data-val="giornaliera"
                                onclick="setSeg('edit', 'tipo', 'giornaliera', this)">📅 Giorno</button>
                            <button type="button" class="seg-btn" data-val="settimanale"
                                onclick="setSeg('edit', 'tipo', 'settimanale', this)">📆 Settimana</button>
                        </div>
                    </div>
                    <input type="hidden" name="tipo" id="edit_tipo" value="giornaliera">

                    <div class="seg-row" id="edit_mode_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;">Come:</span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn" data-val="check"
                                onclick="setSeg('edit', 'mode', 'check', this)">✓ Check</button>
                            <button type="button" class="seg-btn" data-val="contatore"
                                onclick="setSeg('edit', 'mode', 'contatore', this)">🔢 Contatore</button>
                        </div>
                    </div>
                    <input type="hidden" name="modalita" id="edit_mode" value="check">

                    <div id="edit_cf" style="display:none;">
                        <div style="display:flex;gap:8px;">
                            <input type="number" name="obiettivo" id="e_obiettivo" value="10" min="1"
                                placeholder="Obiettivo" style="flex:1;">
                            <input type="text" name="unita" id="e_unita" value="" placeholder="unità (es. ore)"
                                style="flex:1;">
                        </div>
                    </div>

                    <label
                        style="display:flex;align-items:center;gap:8px;margin:12px 0;font-size:.9em;color:var(--muted);cursor:pointer;">
                        <input type="checkbox" name="inverso" id="e_inverso" value="1"
                            style="width:20px;height:20px;accent-color:var(--danger);"> Logica inversa
                    </label>

                    <div class="seg-row" id="edit_diff_row">
                        <span style="font-size:.85em;color:#888;margin-right:8px;display:flex;align-items:center;">
                            Sforzo:
                            <div class="tooltip-trigger" onclick="toggleTooltip(event, this)">
                                ?
                                <div class="tooltip-box">
                                    <p style="margin:0 0 6px;"><strong>Quanto sforzo ti costa?</strong></p>
                                    <p style="margin:0 0 4px;">😌 <strong>Facile (15 XP):</strong> Quasi automatico.</p>
                                    <p style="margin:0 0 4px;">🤔 <strong>Medio (30 XP):</strong> Richiede un po' di
                                        spinta.</p>
                                    <p style="margin:0;">💪 <strong>Difficile (50 XP):</strong> Una vera sfida.</p>
                                </div>
                            </div>
                        </span>
                        <div class="seg-control">
                            <button type="button" class="seg-btn" data-xp="15" onclick="setDiff('edit', 15, this)">😌
                                Facile</button>
                            <button type="button" class="seg-btn" data-xp="30" onclick="setDiff('edit', 30, this)">🤔
                                Medio</button>
                            <button type="button" class="seg-btn" data-xp="50" onclick="setDiff('edit', 50, this)">💪
                                Difficile</button>
                        </div>
                    </div>
                    <input type="hidden" name="xp" id="edit_xp" value="15">


                    <input type="hidden" name="icona" id="edit_icona" value="✅">
                    <div class="icon-grid" id="editIconGrid">
                        <?php foreach ($icone as $ic): ?>
                            <span class="icon-option" onclick="pickIcon('edit','<?= $ic; ?>',this)"><?= $ic; ?></span>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="edit_habit" class="btn-primary" style="margin-top:12px;">Salva
                        Modifiche</button>
                </form>
                <button class="btn-text" onclick="closeEdit()" style="margin-top:8px;">Annulla</button>
            </div>
        </div>

        <script>
            // --- DRAG & DROP ---
            function initSortable(listId) {
                const el = document.getElementById(listId);
                if (!el) return;
                new Sortable(el, {
                    handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost',
                    onEnd: function (evt) {
                        const items = el.querySelectorAll('.habit-manage-row');
                        const newOrder = [];
                        items.forEach(item => newOrder.push(item.getAttribute('data-id')));
                        const fd = new FormData();
                        fd.append('action', 'save_order');
                        newOrder.forEach(id => fd.append('ordini[]', id));
                        fetch('gestione_abitudini.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => { if (data.status === 'ok') console.log("Ordine salvato"); });
                    }
                });
            }
            initSortable('sort-giorn');
            initSortable('sort-sett');

            // --- TOOLTIP MOBILE ---
            function toggleTooltip(event, el) {
                event.stopPropagation(); // Impedisce che il click chiuda il tooltip subito
                document.querySelectorAll('.tooltip-trigger.active').forEach(t => { if (t !== el) t.classList.remove('active'); });
                el.classList.toggle('active');
            }
            // Chiudi il tooltip se tocchi qualsiasi altra parte dello schermo
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.tooltip-trigger')) {
                    document.querySelectorAll('.tooltip-trigger.active').forEach(t => t.classList.remove('active'));
                }
            });

            // --- FUNZIONI UNIVERSALI PER I BOTTONI SEGMENTATI ---
            function setSeg(prefix, type, val, btn) {
                document.getElementById(prefix + '_' + type).value = val;
                const row = document.getElementById(prefix + '_' + type + '_row');
                row.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (type === 'mode') {
                    document.getElementById(prefix + '_cf').style.display = val === 'contatore' ? 'block' : 'none';
                }
            }

            function setDiff(prefix, xp, btn) {
                document.getElementById(prefix + '_xp').value = xp;
                const row = document.getElementById(prefix + '_diff_row');
                row.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }

            function toggleSection(id) { const el = document.getElementById(id), ar = document.getElementById(id + 'Arrow'); if (el.style.display === 'none') { el.style.display = 'block'; ar.style.transform = 'rotate(180deg)'; } else { el.style.display = 'none'; ar.style.transform = ''; } }

            function pickIcon(p, i, el) {
                document.getElementById(p + '_icona').value = i;
                document.querySelectorAll('#' + p + 'IconGrid .icon-option').forEach(e => e.classList.remove('selected'));
                el.classList.add('selected');
            }

            // --- MODIFICA POPUP ---
            function openEdit(id) {
                const row = document.querySelector(`.habit-manage-row[data-id="${id}"]`);
                if (!row) return;

                // 1. Popolo i campi base
                document.getElementById('e_id').value = row.dataset.id;
                document.getElementById('e_nome').value = row.dataset.nome;
                document.getElementById('e_inverso').checked = row.dataset.inverso === '1';
                document.getElementById('edit_icona').value = row.dataset.icona;

                // 2. Gestione "Quando" (Tipo)
                const tipo = row.dataset.tipo;
                document.getElementById('edit_tipo').value = tipo;
                document.querySelectorAll('#edit_tipo_row .seg-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.val === tipo);
                });

                // 3. Gestione "Come" (Modalità)
                const modalita = row.dataset.modalita;
                document.getElementById('edit_mode').value = modalita;
                document.querySelectorAll('#edit_mode_row .seg-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.val === modalita);
                });
                document.getElementById('edit_cf').style.display = modalita === 'contatore' ? 'block' : 'none';

                // 4. Popolo campi contatore
                document.getElementById('e_obiettivo').value = row.dataset.obiettivo;
                document.getElementById('e_unita').value = row.dataset.unita;

                // 5. Gestione "Sforzo" (Aggiornato ai 3 livelli: 15, 30, 50)
                const xp = parseInt(row.dataset.xp) || 15;
                document.getElementById('edit_xp').value = xp;
                let defaultDiff = 15;
                if (xp > 30) defaultDiff = 50;      // 31-99 -> Difficile
                else if (xp > 15) defaultDiff = 30;  // 16-30 -> Medio

                document.querySelectorAll('#edit_diff_row .seg-btn').forEach(btn => {
                    btn.classList.toggle('active', parseInt(btn.dataset.xp) === defaultDiff);
                });

                // 6. Gestione Icone
                document.querySelectorAll('#editIconGrid .icon-option').forEach(el => {
                    el.classList.toggle('selected', el.textContent.trim() === row.dataset.icona);
                });

                // 7. Apro il popup
                document.getElementById('editModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeEdit(e) {
                if (e && e.target !== e.currentTarget) return;
                document.getElementById('editModal').classList.remove('show');
                document.body.style.overflow = '';
            }
        </script>
</body>

</html>