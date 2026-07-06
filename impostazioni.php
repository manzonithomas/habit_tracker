<?php
require 'config.php';
checkLogin();
 $uid = $_SESSION['user_id'];
 $msg = "";

// --- ESPORTAZIONE DATI UTENTE (GDPR Art. 20) ---
if (isset($_GET['action']) && $_GET['action'] == 'export_data') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="gamifylife_dati_' . date('Y-m-d') . '.json');
    
    // Raccolgo tutto
    $data['utente'] = $conn->query("SELECT username, moltiplicatore, giorni_streak FROM utenti WHERE id=$uid")->fetch_assoc();
    $data['abitudini'] = [];
    $res = $conn->query("SELECT nome, tipo, modalita FROM abitudini WHERE utente_id=$uid");
    while ($row = $res->fetch_assoc()) $data['abitudini'][] = $row;
    
    $data['check_abitudini'] = [];
    $res = $conn->query("SELECT ca.data_check, a.nome, ca.valore_inserito, ca.xp_guadagnato FROM check_abitudini ca JOIN abitudini a ON ca.abitudine_id=a.id WHERE ca.utente_id=$uid ORDER BY ca.data_check DESC LIMIT 500");
    while ($row = $res->fetch_assoc()) $data['check_abitudini'][] = $row;
    
    $data['memorable_moments'] = [];
    $res = $conn->query("SELECT data_momento, testo FROM momenti WHERE utente_id=$uid ORDER BY data_momento DESC");
    while ($row = $res->fetch_assoc()) {
        $row['testo'] = decryptText($row['testo']); // Decripto per l'esportazione
        $data['memorable_moments'][] = $row;
    }

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- CANCELLAZIONE REALE ACCOUNT (GDPR Art. 17) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['real_delete_account'])) {
    // 1. Preparo query di cancellazione a catena
    $conn->query("DELETE FROM momenti WHERE utente_id=$uid");
    $conn->query("DELETE FROM bonus_giornalieri WHERE utente_id=$uid");
    $conn->query("DELETE FROM check_abitudini WHERE utente_id=$uid");
    $conn->query("DELETE FROM abitudini WHERE utente_id=$uid");
    $conn->query("DELETE FROM utenti WHERE id=$uid");
    
    session_destroy();
    header("Location: login.php");
    exit;
}

// Disattivazione Soft (vecchio sistema, tenuto per retrocompatibilità)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['disattiva_account'])) {
    $stmt = $conn->prepare("UPDATE utenti SET disattivato=1 WHERE id=?");
    $stmt->bind_param("i", $uid); $stmt->execute();
    session_destroy();
    header("Location: login.php"); exit;
}

 $s = $conn->prepare("SELECT * FROM utenti WHERE id=?");
 $s->bind_param("i", $uid); $s->execute();
 $user = $s->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="stylesheet" href="style.css">
<title>Impostazioni</title>
</head>
<body>
<div class="container">
    <div class="page-header">
        <a href="index.php" class="back-btn" title="Indietro">←</a>
        <h2 style="font-size:1.1em;margin:0;">Impostazioni</h2>
        <div style="width:40px;"></div>
    </div>
    
    <div class="card">
        <h3 style="margin-bottom:15px;">Il Tuo Profilo</h3>
        <p><strong>Username:</strong> <?=htmlspecialchars($user['username']);?></p>
        <p><strong>Streak:</strong> <span style="color:var(--warning);">🔥 <?=$user['giorni_streak'];?> giorni</span></p>
        <p><strong>Moltiplicatore:</strong> <span style="color:var(--primary);">x<?=number_format($user['moltiplicatore'],2);?></span></p>
        <p><strong>🛡️ Scudi:</strong> <?=$user['scudi_rimanenti'];?> disponibili</p>
    </div>

    <div class="card">
        <h3 style="margin-bottom:15px;">I Tuoi Dati (Privacy)</h3>
        <p style="font-size:.9em;color:#888;margin-bottom:15px;">Esercita i tuoi diritti secondo il GDPR. Esporta i tuoi dati in qualsiasi momento.</p>
        <a href="?action=export_data" class="btn-primary" style="display:block;text-align:center;text-decoration:none;background-color:#555;">📥 Esporta tutti i miei dati (JSON)</a>
        <a href="privacy.php" style="display:block;text-align:center;margin-top:10px;font-size:.9em;color:var(--primary);text-decoration:none;">Leggi Informativa sulla Privacy</a>
    </div>

    <div class="card danger-zone">
        <h3 style="margin-bottom:15px;color:var(--danger);">Gestione Account</h3>
        <p style="font-size:.9em;color:#888;margin-bottom:12px;">La disattivazione nasconde l'account. La cancellazione elimina fisicamente tutti i dati in modo irreversibile.</p>
        
        <form method="POST" onsubmit="return confirm('Sei sicuro? I dati saranno eliminati PER SEMPRE.')">
            <button type="submit" name="disattiva_account" class="btn-secondary" style="margin-bottom:10px;">Disattiva Account (Nasconde)</button>
        </form>
        
        <form method="POST" onsubmit="return confirm('ATTENZIONE: Cancellando l\'account eliminerai TUTTI i check, le abitudini e i momenti. Questa azione è irreversibile.')">
            <button type="submit" name="real_delete_account" class="btn-danger">Cancella Account Definitivamente (GDPR)</button>
        </form>
    </div>

    <div class="card" style="text-align:center;">
        <a href="logout.php" class="btn-secondary" style="display:block;text-decoration:none;">Esci dal Login</a>
    </div>
</div>
</body>
</html>