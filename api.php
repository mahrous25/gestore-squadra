<?php
require_once 'config.php';
$pdo = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$path   = trim($_GET['path'] ?? '', '/');
$parts  = explode('/', $path);
$entity = $parts[0] ?? '';
$id     = isset($parts[1]) ? (int)$parts[1] : null;

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── HELPER ──────────────────────────────────────────────────
function ok($data)   { echo json_encode($data); exit; }
function err($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

// ════════════════════════════════════════════════════════════
// CATEGORIE
// ════════════════════════════════════════════════════════════
if ($entity === 'categorie') {
    if ($method === 'GET' && !$id) {
        ok($pdo->query("SELECT * FROM categorie ORDER BY nome")->fetchAll());
    }
    if ($method === 'POST') {
        $nome = trim($body['nome'] ?? '');
        if (!$nome) err('Nome obbligatorio');
        $st = $pdo->prepare("INSERT INTO categorie (nome, societa) VALUES (?,?)");
        $st->execute([$nome, $body['societa'] ?? null]);
        ok(['id' => $pdo->lastInsertId(), 'nome' => $nome]);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM categorie WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
}

// ════════════════════════════════════════════════════════════
// GIOCATORI
// ════════════════════════════════════════════════════════════
if ($entity === 'giocatori') {
    if ($method === 'GET' && !$id) {
        $cat = $_GET['categoria_id'] ?? null;
        if ($cat) {
            $st = $pdo->prepare("SELECT * FROM giocatori WHERE categoria_id=? ORDER BY nome_cognome");
            $st->execute([$cat]);
        } else {
            $st = $pdo->query("SELECT * FROM giocatori ORDER BY nome_cognome");
        }
        ok($st->fetchAll());
    }
    if ($method === 'POST') {
        $nome = trim($body['nome_cognome'] ?? '');
        if (!$nome) err('Nome obbligatorio');
        $cat  = $body['categoria_id'] ?? null;
        if (!$cat) err('Categoria obbligatoria');
        $st = $pdo->prepare("INSERT INTO giocatori (categoria_id,nome_cognome,anno_nascita,ruolo,telefono_genitore,note) VALUES (?,?,?,?,?,?)");
        $st->execute([$cat, $nome, $body['anno_nascita'] ?? null, $body['ruolo'] ?? 'Da assegnare', $body['telefono_genitore'] ?? null, $body['note'] ?? null]);
        ok(['id' => $pdo->lastInsertId(), 'nome_cognome' => $nome]);
    }
    if ($method === 'PUT' && $id) {
        $st = $pdo->prepare("UPDATE giocatori SET nome_cognome=?,anno_nascita=?,ruolo=?,telefono_genitore=?,note=?,attivo=? WHERE id=?");
        $st->execute([
            $body['nome_cognome'] ?? '', $body['anno_nascita'] ?? null,
            $body['ruolo'] ?? 'Da assegnare', $body['telefono_genitore'] ?? null,
            $body['note'] ?? null, $body['attivo'] ?? 1, $id
        ]);
        ok(['updated' => $id]);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM giocatori WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
}

// ════════════════════════════════════════════════════════════
// ALLENAMENTI
// ════════════════════════════════════════════════════════════
if ($entity === 'allenamenti') {
    if ($method === 'GET' && !$id) {
        $cat = $_GET['categoria_id'] ?? null;
        if ($cat) {
            $st = $pdo->prepare("SELECT * FROM allenamenti WHERE categoria_id=? ORDER BY data DESC");
            $st->execute([$cat]);
        } else {
            $st = $pdo->query("SELECT * FROM allenamenti ORDER BY data DESC");
        }
        ok($st->fetchAll());
    }
    if ($method === 'GET' && $id) {
        $st = $pdo->prepare("SELECT * FROM allenamenti WHERE id=?");
        $st->execute([$id]);
        ok($st->fetch());
    }
    if ($method === 'POST') {
        $titolo = trim($body['titolo'] ?? '');
        if (!$titolo) err('Titolo obbligatorio');
        $st = $pdo->prepare("INSERT INTO allenamenti (categoria_id,titolo,data,durata_minuti,obiettivi,esercizi,note) VALUES (?,?,?,?,?,?,?)");
        $st->execute([$body['categoria_id'], $titolo, $body['data'] ?? null, $body['durata_minuti'] ?? null, $body['obiettivi'] ?? null, $body['esercizi'] ?? null, $body['note'] ?? null]);
        ok(['id' => $pdo->lastInsertId()]);
    }
    if ($method === 'PUT' && $id) {
        $st = $pdo->prepare("UPDATE allenamenti SET titolo=?,data=?,durata_minuti=?,obiettivi=?,esercizi=?,note=? WHERE id=?");
        $st->execute([$body['titolo'], $body['data'] ?? null, $body['durata_minuti'] ?? null, $body['obiettivi'] ?? null, $body['esercizi'] ?? null, $body['note'] ?? null, $id]);
        ok(['updated' => $id]);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM allenamenti WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
}

// ════════════════════════════════════════════════════════════
// SESSIONI PRESENZE
// ════════════════════════════════════════════════════════════
if ($entity === 'sessioni') {
    if ($method === 'GET' && !$id) {
        $cat = $_GET['categoria_id'] ?? null;
        if ($cat) {
            $st = $pdo->prepare("SELECT s.*, COUNT(p.id) as totale, SUM(p.presente) as presenti FROM sessioni_presenze s LEFT JOIN presenze p ON p.sessione_id=s.id WHERE s.categoria_id=? GROUP BY s.id ORDER BY s.data DESC");
            $st->execute([$cat]);
        } else {
            $st = $pdo->query("SELECT * FROM sessioni_presenze ORDER BY data DESC");
        }
        ok($st->fetchAll());
    }
    if ($method === 'POST') {
        $data = $body['data'] ?? null;
        if (!$data) err('Data obbligatoria');
        $cat = $body['categoria_id'] ?? null;
        if (!$cat) err('Categoria obbligatoria');
        // Crea sessione
        $st = $pdo->prepare("INSERT INTO sessioni_presenze (categoria_id,data,tipo,note) VALUES (?,?,?,?)");
        $st->execute([$cat, $data, $body['tipo'] ?? 'Allenamento', $body['note'] ?? null]);
        $sessId = $pdo->lastInsertId();
        // Aggiungi tutti i giocatori attivi alla sessione
        $giocatori = $pdo->prepare("SELECT id FROM giocatori WHERE categoria_id=? AND attivo=1");
        $giocatori->execute([$cat]);
        $ins = $pdo->prepare("INSERT INTO presenze (sessione_id,giocatore_id,presente) VALUES (?,?,0)");
        foreach ($giocatori->fetchAll() as $g) {
            $ins->execute([$sessId, $g['id']]);
        }
        ok(['id' => $sessId]);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM sessioni_presenze WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
}

// ════════════════════════════════════════════════════════════
// PRESENZE (dettaglio singola sessione)
// ════════════════════════════════════════════════════════════
if ($entity === 'presenze') {
    if ($method === 'GET') {
        $sess = $_GET['sessione_id'] ?? null;
        if (!$sess) err('sessione_id obbligatorio');
        $st = $pdo->prepare("SELECT p.*, g.nome_cognome, g.ruolo FROM presenze p JOIN giocatori g ON g.id=p.giocatore_id WHERE p.sessione_id=? ORDER BY g.nome_cognome");
        $st->execute([$sess]);
        ok($st->fetchAll());
    }
    if ($method === 'PUT') {
        // Aggiorna presenza: { sessione_id, giocatore_id, presente }
        $st = $pdo->prepare("UPDATE presenze SET presente=? WHERE sessione_id=? AND giocatore_id=?");
        $st->execute([$body['presente'] ? 1 : 0, $body['sessione_id'], $body['giocatore_id']]);
        ok(['updated' => true]);
    }
}

// ════════════════════════════════════════════════════════════
// PARTITE
// ════════════════════════════════════════════════════════════
if ($entity === 'partite') {
    if ($method === 'GET' && !$id) {
        $cat = $_GET['categoria_id'] ?? null;
        if ($cat) {
            $st = $pdo->prepare("SELECT * FROM partite WHERE categoria_id=? ORDER BY data DESC");
            $st->execute([$cat]);
        } else {
            $st = $pdo->query("SELECT * FROM partite ORDER BY data DESC");
        }
        ok($st->fetchAll());
    }
    if ($method === 'POST') {
        $avv = trim($body['avversario'] ?? '');
        if (!$avv) err('Avversario obbligatorio');
        $gf  = $body['gol_fatti']   ?? null;
        $gs  = $body['gol_subiti']  ?? null;
        $ris = null;
        if ($gf !== null && $gs !== null) {
            $ris = $gf > $gs ? 'Vittoria' : ($gf < $gs ? 'Sconfitta' : 'Pareggio');
        }
        $st = $pdo->prepare("INSERT INTO partite (categoria_id,avversario,data,casa_trasferta,gol_fatti,gol_subiti,risultato,note) VALUES (?,?,?,?,?,?,?,?)");
        $st->execute([$body['categoria_id'], $avv, $body['data'] ?? null, $body['casa_trasferta'] ?? 'Casa', $gf, $gs, $ris, $body['note'] ?? null]);
        ok(['id' => $pdo->lastInsertId(), 'risultato' => $ris]);
    }
    if ($method === 'PUT' && $id) {
        $gf  = $body['gol_fatti']  ?? null;
        $gs  = $body['gol_subiti'] ?? null;
        $ris = null;
        if ($gf !== null && $gs !== null) {
            $ris = $gf > $gs ? 'Vittoria' : ($gf < $gs ? 'Sconfitta' : 'Pareggio');
        }
        $st = $pdo->prepare("UPDATE partite SET avversario=?,data=?,casa_trasferta=?,gol_fatti=?,gol_subiti=?,risultato=?,note=? WHERE id=?");
        $st->execute([$body['avversario'], $body['data'] ?? null, $body['casa_trasferta'] ?? 'Casa', $gf, $gs, $ris, $body['note'] ?? null, $id]);
        ok(['updated' => $id, 'risultato' => $ris]);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM partite WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
}

err('Endpoint non trovato', 404);
