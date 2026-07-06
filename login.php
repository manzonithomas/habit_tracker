<?php
require 'config.php';
if (isset($_SESSION['user_id']))
    header("Location: index.php");
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action == 'register') {
        if (strlen($username) < 3 || strlen($password) < 6) {
            $error = "Username min 3 caratteri, Password min 6.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO utenti (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hash);
            if ($stmt->execute()) {
                $nid = $conn->insert_id;
                $_SESSION['user_id'] = $nid;
                $defaults = [
                    ['💧', 'Bere acqua', 'giornaliera', 'contatore', 8, 'bicchieri', 25],
                    ['🏃', 'Movimento 30min', 'giornaliera', 'check', NULL, '', 30],
                    ['📖', 'Leggere', 'giornaliera', 'contatore', 20, 'minuti', 25],
                    ['😴', 'Dormire 7+ ore', 'giornaliera', 'check', NULL, '', 20],
                    ['🥗', 'Frutta e verdura', 'giornaliera', 'contatore', 5, 'porzioni', 20],
                    ['🧹', 'Pulire casa', 'settimanale', 'check', NULL, '', 50],
                    ['📝', 'Piano settimanale', 'settimanale', 'check', NULL, '', 40],
                ];
                foreach ($defaults as $d) {
                    $s = $conn->prepare("INSERT INTO abitudini (utente_id,nome,tipo,modalita,obiettivo,unita,xp_ricompensa,icona) VALUES (?,?,?,?,?,?,?,?)");
                    $s->bind_param("issisiis", $nid, $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[0]);
                    $s->execute();
                }
                header("Location: index.php");
                exit;
            } else {
                $error = "Username già preso.";
            }
        }
    } else if ($action == 'login') {
        $stmt = $conn->prepare("SELECT id, password, disattivato FROM utenti WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            if ($row['disattivato'] == 1)
                $error = "Account disattivato.";
            elseif (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                header("Location: index.php");
                exit;
            } else
                $error = "Password sbagliata.";
        } else
            $error = "Utente non trovato.";
    }
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

    <title>Accedi - GamifyLife</title>

    <meta name="description" content="Costruisci abitudini, guadagna XP, sale di livello.">
    <link rel="icon" href="icona.png" type="image/png" sizes="32x32">
    <link rel="icon" href="icona.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="icona.png">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= APP_URL; ?>/icona.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= APP_URL; ?>/icona.png">

    <link rel="stylesheet" href="style.css">
    <?php if (basename($_SERVER['PHP_SELF']) === 'gestione_abitudini.php'): ?>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <?php endif; ?>
</head>

<body>
    <div class="container" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
        <div class="card" style="width:100%;">
            <h2 style="text-align:center;margin-bottom:4px;">🌱 HabitTracker ma + bello</h2>
            <p style="text-align:center;color:#666;margin-bottom:24px;font-size:.9em;">Costruisci abitudini. Guadagna
                XP.</p>
            <?php if ($error)
                echo "<p style='color:var(--danger);text-align:center;margin-bottom:12px;'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <button type="submit" name="action" value="login" class="btn-primary">Accedi</button>
                <button type="submit" name="action" value="register" class="btn-secondary">Registrati</button>
            </form>
        </div>
    </div>

    <!-- BANNER CONSENSO INFORMATIVA -->
    <div id="gdpr-banner"
        style="position:fixed;bottom:0;left:0;right:0;background:rgba(28,28,30,0.95);backdrop-filter:blur(10px);padding:15px 20px;display:flex;justify-content:space-between;align-items:center;z-index:999;border-top:1px solid #333;">
        <span style="font-size:.8em;color:#888;">Utilizziamo i cookie necessari al funzionamento. <a href="privacy.php"
                style="color:var(--primary);text-decoration:underline;">Leggi Privacy</a></span>
        <button
            onclick="document.getElementById('gdpr-banner').style.display='none'; localStorage.setItem('gdpr_consented', 'true');"
            style="background:none;border:1px solid #555;color:var(--text);padding:5px 12px;border-radius:6px;font-size:.8em;cursor:pointer;">Accetto</button>
    </div>
    <script>if (localStorage.getItem('gdpr_consented') === 'true') document.getElementById('gdpr-banner').style.display = 'none';</script>
</body>

</html>