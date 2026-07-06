<?php
require 'config.php';
checkLogin();
$uid = $_SESSION['user_id'];
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$oggi = date('Y-m-d');

function calcolaLivello($xp)
{
    $lv = 0;
    while (true) {
        if ($xp < 100 * pow($lv + 1, 1.5))
            break;
        $lv++;
    }
    $i = 100 * pow($lv, 1.5);
    $f = 100 * pow($lv + 1, 1.5);
    $d = $f - $i;
    return ['livello' => $lv, 'percentuale' => $d > 0 ? ($xp - $i) / $d * 100 : 0, 'xp_mancanti' => round($f - $xp)];
}
function getTitolo($lv)
{
    if ($lv >= 50)
        return "⭐ Semidio";
    if ($lv >= 35)
        return "🏆 Campione";
    if ($lv >= 20)
        return "🔥 Maestro";
    if ($lv >= 10)
        return "💪 Costruttore";
    if ($lv >= 5)
        return "📚 Apprendista";
    return "🌱 Principiante";
}

function aggiornaStreak($uid, $conn)
{
    global $oggi;
    $s = $conn->prepare("SELECT data_ultimo_check, giorni_streak, scudi_rimanenti, moltiplicatore FROM utenti WHERE id=?");
    $s->bind_param("i", $uid);
    $s->execute();
    $u = $s->get_result()->fetch_assoc();
    if ($u['data_ultimo_check'] == $oggi)
        return $u;

    $diff = (int) (new DateTime($u['data_ultimo_check'] ?? $oggi))->diff(new DateTime($oggi))->days;
    $new_streak = $u['giorni_streak'];
    $new_molt = 0;
    $scudo_usato = false;

    if ($u['data_ultimo_check'] === null) {
        $new_streak = 1;
        $new_molt = 1.10;
    } elseif ($diff == 1) {
        $new_streak++;
        $new_molt = min(1.00 + $new_streak * 0.10, 3.00);
        if ($new_streak % 7 == 0 && $u['scudi_rimanenti'] < 3) {
            $conn->prepare("UPDATE utenti SET scudi_rimanenti = scudi_rimanenti + 1 WHERE id=?")->bind_param("i", $uid)->execute();
        }
    } elseif ($diff > 1) {
        if ($u['scudi_rimanenti'] > 0) {
            $conn->prepare("UPDATE utenti SET scudi_rimanenti = scudi_rimanenti - 1 WHERE id=?")->bind_param("i", $uid)->execute();
            $scudo_usato = true;
        } else {
            $new_streak = 1;
            $new_molt = 1.10;
        }
    }

    if ($new_molt > 0) {
        $up = $conn->prepare("UPDATE utenti SET giorni_streak=?, moltiplicatore=?, data_ultimo_check=? WHERE id=?");
        $up->bind_param("iddsi", $new_streak, $new_molt, $oggi, $uid);
        $up->execute();
    }

    $u['giorni_streak'] = $new_streak;
    $u['scudi_rimanenti'] -= ($scudo_usato ? 1 : 0);
    $u['scudo_usato'] = $scudo_usato;
    if ($new_molt > 0)
        $u['moltiplicatore'] = $new_molt;
    return $u;
}

function verificaBonus($uid, $conn, $molt)
{
    global $oggi;
    $s = $conn->prepare("SELECT id,modalita,obiettivo,inverso FROM abitudini WHERE utente_id=? AND tipo='giornaliera' AND attiva=1");
    $s->bind_param("i", $uid);
    $s->execute();
    $res = $s->get_result();
    if ($res->num_rows == 0)
        return false;
    $all_ok = true;
    while ($ab = $res->fetch_assoc()) {
        $sc = $conn->prepare("SELECT valore_inserito FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check=?");
        $sc->bind_param("iis", $ab['id'], $uid, $oggi);
        $sc->execute();
        $ck = $sc->get_result()->fetch_assoc();
        if (!$ck) {
            $all_ok = false;
            break;
        }
        if ($ab['modalita'] == 'contatore') {
            $val = $ck['valore_inserito'] ?? 0;
            if ($ab['inverso']) {
                if ($val <= 0 || $val > $ab['obiettivo']) {
                    $all_ok = false;
                    break;
                }
            } else {
                if ($val < $ab['obiettivo']) {
                    $all_ok = false;
                    break;
                }
            }
        }
    }
    if ($all_ok) {
        $sb = $conn->prepare("SELECT id FROM bonus_giornalieri WHERE utente_id=? AND data_bonus=?");
        $sb->bind_param("is", $uid, $oggi);
        $sb->execute();
        if ($sb->get_result()->num_rows == 0) {
            $bxp = round(50 * $molt);
            $conn->prepare("INSERT INTO bonus_giornalieri (utente_id,data_bonus,xp_guadagnato) VALUES (?,?,?)")->bind_param("isi", $uid, $oggi, $bxp)->execute();
            return $bxp;
        }
    }
    return false;
}

switch ($action) {
    case 'get_state':
        $user = aggiornaStreak($uid, $conn);
        $s1 = $conn->prepare("SELECT COALESCE(SUM(xp_guadagnato),0) as t FROM check_abitudini WHERE utente_id=?");
        $s1->bind_param("i", $uid);
        $s1->execute();
        $xp_c = $s1->get_result()->fetch_assoc()['t'];
        $s2 = $conn->prepare("SELECT COALESCE(SUM(xp_guadagnato),0) as t FROM bonus_giornalieri WHERE utente_id=?");
        $s2->bind_param("i", $uid);
        $s2->execute();
        $xp_b = $s2->get_result()->fetch_assoc()['t'];
        $xp_tot = $xp_c + $xp_b;
        $info = calcolaLivello($xp_tot);

        $sb = $conn->prepare("SELECT xp_guadagnato FROM bonus_giornalieri WHERE utente_id=? AND data_bonus=?");
        $sb->bind_param("is", $uid, $oggi);
        $sb->execute();
        $bonus = $sb->get_result()->fetch_assoc();

        $sm = $conn->prepare("SELECT testo FROM momenti WHERE utente_id=? AND data_momento=?");
        $sm->bind_param("is", $uid, $oggi);
        $sm->execute();
        $momento = $sm->get_result()->fetch_assoc();

        echo json_encode([
            'user' => $user,
            'xp_totale' => $xp_tot,
            'level' => $info,
            'titolo' => getTitolo($info['livello']),
            'bonus_oggi' => $bonus,
            'momento_oggi' => decryptText($momento['testo'] ?? '')
        ]);
        break;

    case 'toggle_check':
        $ab_id = (int) $_POST['ab_id'];
        $tipo = $_POST['tipo'];
        $sx = $conn->prepare("SELECT xp_ricompensa FROM abitudini WHERE id=? AND utente_id=?");
        $sx->bind_param("ii", $ab_id, $uid);
        $sx->execute();
        $abd = $sx->get_result()->fetch_assoc();

        // Prevenzione warning linter: gestiamo i due casi in blocchi separati e puliti
        if ($tipo === 'settimanale') {
            $lun = getLunedi();
            $dom = getDomenica();
            $sc = $conn->prepare("SELECT id FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check BETWEEN ? AND ?");
            $sc->bind_param("iiss", $ab_id, $uid, $lun, $dom);
            $sc->execute();
        } else {
            $sc = $conn->prepare("SELECT id FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check=?");
            $sc->bind_param("iis", $ab_id, $uid, $oggi);
            $sc->execute();
        }

        if ($sc->get_result()->num_rows > 0) {
            if ($tipo === 'settimanale') {
                $del = $conn->prepare("DELETE FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check BETWEEN ? AND ?");
                $del->bind_param("iiss", $ab_id, $uid, $lun, $dom);
                $del->execute();
            } else {
                $del = $conn->prepare("DELETE FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check=?");
                $del->bind_param("iis", $ab_id, $uid, $oggi);
                $del->execute();
            }
            echo json_encode(['status' => 'unchecked']);
        } else {
            $user = aggiornaStreak($uid, $conn);
            $xp = round($user['moltiplicatore'] * $abd['xp_ricompensa']);
            $ins = $conn->prepare("INSERT INTO check_abitudini (abitudine_id,utente_id,data_check,xp_guadagnato) VALUES (?,?,?,?)");
            $ins->bind_param("iisi", $ab_id, $uid, $oggi, $xp);
            $ins->execute();
            $bonus_xp = verificaBonus($uid, $conn, $user['moltiplicatore']);
            echo json_encode(['status' => 'checked', 'xp' => $xp, 'bonus' => $bonus_xp, 'scudo_usato' => $user['scudo_usato'] ?? false]);
        }
        break;

    case 'save_counter':
        $ab_id = (int) $_POST['ab_id'];
        $val = max(0, (int) $_POST['val']);
        $tipo = $_POST['tipo'];
        $sh = $conn->prepare("SELECT xp_ricompensa,obiettivo,inverso FROM abitudini WHERE id=? AND utente_id=?");
        $sh->bind_param("ii", $ab_id, $uid);
        $sh->execute();
        $habit = $sh->get_result()->fetch_assoc();

        if ($habit) {
            $user = aggiornaStreak($uid, $conn);
            if ($val == 0) {
                if ($tipo === 'settimanale') {
                    $lun = getLunedi();
                    $dom = getDomenica();
                    $del = $conn->prepare("DELETE FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check BETWEEN ? AND ?");
                    $del->bind_param("iiss", $ab_id, $uid, $lun, $dom);
                    $del->execute();
                } else {
                    $del = $conn->prepare("DELETE FROM check_abitudini WHERE abitudine_id=? AND utente_id=? AND data_check=?");
                    $del->bind_param("iis", $ab_id, $uid, $oggi);
                    $del->execute();
                }
                echo json_encode(['status' => 'deleted']);
            } else {
                if ($habit['inverso'])
                    $rapporto = $val <= 0 ? 1.0 : min($habit['obiettivo'] / $val, 1.0);
                else
                    $rapporto = min($val / $habit['obiettivo'], 1.0);
                $xp = round($user['moltiplicatore'] * $habit['xp_ricompensa'] * $rapporto);
                $up = $conn->prepare("INSERT INTO check_abitudini (abitudine_id,utente_id,data_check,valore_inserito,xp_guadagnato) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE valore_inserito=VALUES(valore_inserito), xp_guadagnato=VALUES(xp_guadagnato)");
                $up->bind_param("iisii", $ab_id, $uid, $oggi, $val, $xp);
                $up->execute();
                $bonus_xp = verificaBonus($uid, $conn, $user['moltiplicatore']);
                echo json_encode(['status' => 'saved', 'xp' => $xp, 'bonus' => $bonus_xp, 'scudo_usato' => $user['scudo_usato'] ?? false]);
            }
        }
        break;

    case 'save_moment':
        $testo = trim($_POST['testo'] ?? '');
        $enc_testo = encryptText($testo); // Crittografia prima di salvare
        $up = $conn->prepare("INSERT INTO momenti (utente_id, data_momento, testo) VALUES (?,?,?) ON DUPLICATE KEY UPDATE testo=VALUES(testo)");
        $up->bind_param("iss", $uid, $oggi, $enc_testo);
        $up->execute();
        echo json_encode(['status' => 'ok']);
        break;
}