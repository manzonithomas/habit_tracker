<?php
// Non serve la logica di sessione per leggere una pagina statica, 
// ma la teniamo per sicurezza per evitare warning se richiamato accidentalmente
if (session_status() === PHP_SESSION_NONE)
    session_start();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="description" content="Informazioni sulla privacy dell'app GamifyLife.">
    <title>Privacy - GamifyLife</title>
    <link rel="icon" href="icona.png?v=2" type="image/png" sizes="32x32">
    <link rel="stylesheet" href="style.css?v=2">
</head>

<body>

    <div class="container" style="padding-top:40px;">
        <div class="page-header">
            <a href="javascript:history.back()" class="back-btn" title="Indietro">←</a>
            <h2 style="font-size:1.1em;margin:0;">Privacy</h2>
            <div style="width:40px;"></div>
        </div>

        <div class="card">
            <div style="text-align:center; margin-bottom:24px;">
                <div style="font-size:3em; margin-bottom:10px;">🔒</div>
                <h1 style="I tuoi dati, le tue regole</h1>
            <p style=" color:var(--muted);">Questa app non è un'azienda. Non vendiamo dati, non facciamo pubblicità,
                    non tracciamo cosa fai fuori da qui.</p>
            </div>

            <h3 style="margin-bottom:12px; font-size:.95em; color:var(--text);">Cosa salviamo esattamente?</h3>
            <ul style="padding-left:20px; margin-bottom:24px; color:var(--muted); line-height:1.6;">
                <li style="margin-bottom:10px;"><strong>Credenziali:</strong> Username e password (criptografata, non
                    leggibile).</li>
                <li style="margin-bottom:10px;"><strong>Dati di gioco:</strong> Punti XP, livelli e moltiplicatori.</li>
                <li style="margin-bottom:10px;"><strong>Le tue abitudini:</strong> I nomi e gli obiettivi che decidi di
                    tracciare.</li>
                <li style="margin-bottom:10px;"><strong>I tuoi progressi:</strong> I check giornalieri che segni.</li>
                <li style="margin-bottom:10px;"><strong>I tuoi pensieri:</strong> I "Memorable Moments" che scrivi
                    (criptografati, illeggibili per chiunque tranne te).</li>
            </ul>

            <div class="info-box" style="margin-bottom:24px;">
                <p style="margin:0 0 6px;"><strong>Dove finiscono i dati?</strong></p>
                <p style="margin:0;">Restano chiusi nel database del mio Raspberry Pi. Non vengono inviati a nessun
                    server esterno, né a terze parti. Se cancelli il tuo account, i dati vengono eliminati fisicamente e
                    non si possono recuperare.</p>
            </div>

            <h3 style="margin-bottom:12px; font-size:.95em; color:var(--text);">Cosa puoi fare?</h3>
            <ul style="padding-left:20px; margin-bottom:24px; color:var(--muted); line-height:1.6;">
                <li style="margin-bottom:10px;"><strong>Modificare tutto:</strong> Cambiare abitudini o impostazioni
                    quando vuoi.</li>
                <li style="margin-bottom:10px;"><strong>Esportare tutto:</strong> Dalle impostazioni puoi scaricare un
                    file con tutti i tuoi check in un formato leggibile.</li>
                <li style="margin-bottom:10px;"><strong>Cancellare tutto:</strong> Dalle impostazioni c'è il pulsante
                    "Cancella Account Definitivamente" che cancella fisicamente ogni tuo dato.</li>
            </ul>

            <h3 style="margin-bottom:12px; font-size:.95em; color:var(--text);">Hai un problema o una richiesta?</h3>
            <p style="color:var(--muted); font-size:.9em; margin-bottom:24px;">Essendo un progetto privato gestito da
                una singola persona, se hai bisogno di modificare, cancellare o trasferire i tuoi dati, basta scrivermi
                una mail.</p>

            <div
                style="background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:15px; margin-bottom:24px;">
                <p style="margin:0 0 8px; font-size:.9em; color:var(--muted);">Contatto sviluppatore:</p>
                <a href="mailto:thomasmanzoni9807@gmail.com" class="btn-primary"
                    style="display:block; text-align:center; text-decoration:none; font-size:.95em; background-color:#333;">Scrivi
                    un'email</a>
            </div>
        </div>
    </div>

</body>

</html>