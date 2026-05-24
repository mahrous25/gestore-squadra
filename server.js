const express = require('express');
const mysql = require('mysql2/promise');
const path = require('path');

const app = express();
app.use(express.json());
app.use(express.static('.'));

const db = mysql.createPool({
  host: process.env.MYSQLHOST || 'kodama.proxy.rlwy.net',
  port: parseInt(process.env.MYSQLPORT || '48095'),
  user: process.env.MYSQLUSER || 'root',
  password: process.env.MYSQLPASSWORD || 'RQnEDOLRVYIVeeFfSoNGIzQStFgeMSCt',
  database: process.env.MYSQLDATABASE || 'railway',
  waitForConnections: true,
  connectionLimit: 10,
  connectTimeout: 30000,
  acquireTimeout: 30000
});

// INSTALL
app.get('/install', async (req, res) => {
  try {
    await db.query(`CREATE TABLE IF NOT EXISTS categorie (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(50) NOT NULL, creata_il DATETIME DEFAULT CURRENT_TIMESTAMP)`);
    await db.query(`CREATE TABLE IF NOT EXISTS giocatori (id INT AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL, nome_cognome VARCHAR(100) NOT NULL, anno_nascita YEAR, ruolo ENUM('Portiere','Difensore','Centrocampista','Attaccante','Da assegnare') DEFAULT 'Da assegnare', telefono_genitore VARCHAR(30), note TEXT, attivo TINYINT(1) DEFAULT 1, creato_il DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE CASCADE)`);
    await db.query(`CREATE TABLE IF NOT EXISTS allenamenti (id INT AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL, titolo VARCHAR(150) NOT NULL, data DATE, durata_minuti SMALLINT, obiettivi TEXT, esercizi TEXT, note TEXT, creato_il DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE CASCADE)`);
    await db.query(`CREATE TABLE IF NOT EXISTS sessioni_presenze (id INT AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL, data DATE NOT NULL, tipo ENUM('Allenamento','Partita','Ritiro') DEFAULT 'Allenamento', note TEXT, creata_il DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE CASCADE)`);
    await db.query(`CREATE TABLE IF NOT EXISTS presenze (id INT AUTO_INCREMENT PRIMARY KEY, sessione_id INT NOT NULL, giocatore_id INT NOT NULL, presente TINYINT(1) DEFAULT 0, note VARCHAR(255), UNIQUE KEY uq (sessione_id, giocatore_id), FOREIGN KEY (sessione_id) REFERENCES sessioni_presenze(id) ON DELETE CASCADE, FOREIGN KEY (giocatore_id) REFERENCES giocatori(id) ON DELETE CASCADE)`);
    await db.query(`CREATE TABLE IF NOT EXISTS partite (id INT AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL, avversario VARCHAR(100) NOT NULL, data DATE, casa_trasferta ENUM('Casa','Trasferta') DEFAULT 'Casa', gol_fatti TINYINT, gol_subiti TINYINT, risultato ENUM('Vittoria','Pareggio','Sconfitta'), note TEXT, creata_il DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE CASCADE)`);
    const [rows] = await db.query('SELECT COUNT(*) as n FROM categorie');
    if (rows[0].n == 0) {
      await db.query(`INSERT INTO categorie (nome) VALUES ('2019/2020'), ('2018/2019')`);
    }
    res.json({ success: true, message: 'Database installato!' });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
});

// CATEGORIE
app.get('/api/categorie', async (req, res) => {
  try {
    const [rows] = await db.query('SELECT * FROM categorie ORDER BY nome');
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.post('/api/categorie', async (req, res) => {
  try {
    const { nome } = req.body;
    const [r] = await db.query('INSERT INTO categorie (nome) VALUES (?)', [nome]);
    res.json({ id: r.insertId, nome });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.delete('/api/categorie/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM categorie WHERE id=?', [req.params.id]);
    res.json({ deleted: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// GIOCATORI
app.get('/api/giocatori', async (req, res) => {
  try {
    const { categoria_id } = req.query;
    const [rows] = categoria_id
      ? await db.query('SELECT * FROM giocatori WHERE categoria_id=? ORDER BY nome_cognome', [categoria_id])
      : await db.query('SELECT * FROM giocatori ORDER BY nome_cognome');
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.post('/api/giocatori', async (req, res) => {
  try {
    const { categoria_id, nome_cognome, anno_nascita, ruolo, telefono_genitore, note } = req.body;
    const [r] = await db.query('INSERT INTO giocatori (categoria_id,nome_cognome,anno_nascita,ruolo,telefono_genitore,note) VALUES (?,?,?,?,?,?)', [categoria_id, nome_cognome, anno_nascita||null, ruolo||'Da assegnare', telefono_genitore||null, note||null]);
    res.json({ id: r.insertId });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.put('/api/giocatori/:id', async (req, res) => {
  try {
    const { nome_cognome, anno_nascita, ruolo, telefono_genitore, note, attivo } = req.body;
    await db.query('UPDATE giocatori SET nome_cognome=?,anno_nascita=?,ruolo=?,telefono_genitore=?,note=?,attivo=? WHERE id=?', [nome_cognome, anno_nascita||null, ruolo||'Da assegnare', telefono_genitore||null, note||null, attivo??1, req.params.id]);
    res.json({ updated: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.delete('/api/giocatori/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM giocatori WHERE id=?', [req.params.id]);
    res.json({ deleted: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// ALLENAMENTI
app.get('/api/allenamenti', async (req, res) => {
  try {
    const { categoria_id } = req.query;
    const [rows] = categoria_id
      ? await db.query('SELECT * FROM allenamenti WHERE categoria_id=? ORDER BY data DESC', [categoria_id])
      : await db.query('SELECT * FROM allenamenti ORDER BY data DESC');
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.post('/api/allenamenti', async (req, res) => {
  try {
    const { categoria_id, titolo, data, durata_minuti, obiettivi, esercizi, note } = req.body;
    const [r] = await db.query('INSERT INTO allenamenti (categoria_id,titolo,data,durata_minuti,obiettivi,esercizi,note) VALUES (?,?,?,?,?,?,?)', [categoria_id, titolo, data||null, durata_minuti||null, obiettivi||null, esercizi||null, note||null]);
    res.json({ id: r.insertId });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.put('/api/allenamenti/:id', async (req, res) => {
  try {
    const { titolo, data, durata_minuti, obiettivi, esercizi, note } = req.body;
    await db.query('UPDATE allenamenti SET titolo=?,data=?,durata_minuti=?,obiettivi=?,esercizi=?,note=? WHERE id=?', [titolo, data||null, durata_minuti||null, obiettivi||null, esercizi||null, note||null, req.params.id]);
    res.json({ updated: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.delete('/api/allenamenti/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM allenamenti WHERE id=?', [req.params.id]);
    res.json({ deleted: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// SESSIONI
app.get('/api/sessioni', async (req, res) => {
  try {
    const { categoria_id } = req.query;
    const [rows] = await db.query('SELECT s.*, COUNT(p.id) as totale, SUM(p.presente) as presenti FROM sessioni_presenze s LEFT JOIN presenze p ON p.sessione_id=s.id WHERE s.categoria_id=? GROUP BY s.id ORDER BY s.data DESC', [categoria_id]);
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.post('/api/sessioni', async (req, res) => {
  try {
    const { categoria_id, data, tipo, note } = req.body;
    const [r] = await db.query('INSERT INTO sessioni_presenze (categoria_id,data,tipo,note) VALUES (?,?,?,?)', [categoria_id, data, tipo||'Allenamento', note||null]);
    const sessId = r.insertId;
    const [giocatori] = await db.query('SELECT id FROM giocatori WHERE categoria_id=? AND attivo=1', [categoria_id]);
    for (const g of giocatori) {
      await db.query('INSERT INTO presenze (sessione_id,giocatore_id,presente) VALUES (?,?,0)', [sessId, g.id]);
    }
    res.json({ id: sessId });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.delete('/api/sessioni/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM sessioni_presenze WHERE id=?', [req.params.id]);
    res.json({ deleted: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// PRESENZE
app.get('/api/presenze', async (req, res) => {
  try {
    const { sessione_id } = req.query;
    const [rows] = await db.query('SELECT p.*, g.nome_cognome, g.ruolo FROM presenze p JOIN giocatori g ON g.id=p.giocatore_id WHERE p.sessione_id=? ORDER BY g.nome_cognome', [sessione_id]);
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.put('/api/presenze', async (req, res) => {
  try {
    const { sessione_id, giocatore_id, presente } = req.body;
    await db.query('UPDATE presenze SET presente=? WHERE sessione_id=? AND giocatore_id=?', [presente?1:0, sessione_id, giocatore_id]);
    res.json({ updated: true });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// PARTITE
app.get('/api/partite', async (req, res) => {
  try {
    const { categoria_id } = req.query;
    const [rows] = categoria_id
      ? await db.query('SELECT * FROM partite WHERE categoria_id=? ORDER BY data DESC', [categoria_id])
      : await db.query('SELECT * FROM partite ORDER BY data DESC');
    res.json(rows);
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.post('/api/partite', async (req, res) => {
  try {
    const { categoria_id, avversario, data, casa_trasferta, gol_fatti, gol_subiti, note } = req.body;
    const gf = gol_fatti !== undefined && gol_fatti !== '' ? parseInt(gol_fatti) : null;
    const gs = gol_subiti !== undefined && gol_subiti !== '' ? parseInt(gol_subiti) : null;
    const risultato = gf !== null && gs !== null ? (gf > gs ? 'Vittoria' : gf < gs ? 'Sconfitta' : 'Pareggio') : null;
    const [r] = await db.query('INSERT INTO partite (categoria_id,avversario,data,casa_trasferta,gol_fatti,gol_subiti,risultato,note) VALUES (?,?,?,?,?,?,?,?)', [categoria_id, avversario, data||null, casa_trasferta||'Casa', gf, gs, risultato, note||null]);
    res.json({ id: r.insertId, risultato });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.put('/api/partite/:id', async (req, res) => {
  try {
    const { avversario, data, casa_trasferta, gol_fatti, gol_subiti, note } = req.body;
    const gf = gol_fatti !== undefined && gol_fatti !== '' ? parseInt(gol_fatti) : null;
    const gs = gol_subiti !== undefined && gol_subiti !== '' ? parseInt(gol_subiti) : null;
    const risultato = gf !== null && gs !== null ? (gf > gs ? 'Vittoria' : gf < gs ? 'Sconfitta' : 'Pareggio') : null;
    await db.query('UPDATE partite SET avversario=?,data=?,casa_trasferta=?,gol_fatti=?,gol_subiti=?,risultato=?,note=? WHERE id=?', [avversario, data||null, casa_trasferta||'Casa', gf, gs, risultato, note||null, req.params.id]);
    res.json({ updated: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});
app.delete('/api/partite/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM partite WHERE id=?', [req.params.id]);
    res.json({ deleted: req.params.id });
  } catch(e) { res.status(500).json({ error: e.message }); }
});

// HOME
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

const port = process.env.PORT || 8080;
app.listen(port, '0.0.0.0', () => console.log(`Server avviato su porta ${port}`));