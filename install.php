<?php
require_once 'config.php';

$pdo = getDB();

$sql = "
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE IF NOT EXISTS `categorie` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(50) NOT NULL,
  `societa`   VARCHAR(100) DEFAULT NULL,
  `creata_il` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `giocatori` (
  `id`                 INT(11) NOT NULL AUTO_INCREMENT,
  `categoria_id`       INT(11) NOT NULL,
  `nome_cognome`       VARCHAR(100) NOT NULL,
  `anno_nascita`       YEAR DEFAULT NULL,
  `ruolo`              ENUM('Portiere','Difensore','Centrocampista','Attaccante','Da assegnare') NOT NULL DEFAULT 'Da assegnare',
  `telefono_genitore`  VARCHAR(30) DEFAULT NULL,
  `note`               TEXT DEFAULT NULL,
  `attivo`             TINYINT(1) NOT NULL DEFAULT 1,
  `creato_il`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_giocatori_categoria` (`categoria_id`),
  CONSTRAINT `fk_giocatori_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorie` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `allenamenti` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `categoria_id`   INT(11) NOT NULL,
  `titolo`         VARCHAR(150) NOT NULL,
  `data`           DATE DEFAULT NULL,
  `durata_minuti`  SMALLINT DEFAULT NULL,
  `obiettivi`      TEXT DEFAULT NULL,
  `esercizi`       TEXT DEFAULT NULL,
  `note`           TEXT DEFAULT NULL,
  `creato_il`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_allenamenti_categoria` (`categoria_id`),
  CONSTRAINT `fk_allenamenti_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorie` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sessioni_presenze` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` INT(11) NOT NULL,
  `data`         DATE NOT NULL,
  `tipo`         ENUM('Allenamento','Partita','Ritiro') NOT NULL DEFAULT 'Allenamento',
  `note`         TEXT DEFAULT NULL,
  `creata_il`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sessioni_categoria` (`categoria_id`),
  CONSTRAINT `fk_sessioni_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorie` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `presenze` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `sessione_id`  INT(11) NOT NULL,
  `giocatore_id` INT(11) NOT NULL,
  `presente`     TINYINT(1) NOT NULL DEFAULT 0,
  `note`         VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessione_giocatore` (`sessione_id`,`giocatore_id`),
  KEY `fk_presenze_sessione` (`sessione_id`),
  KEY `fk_presenze_giocatore` (`giocatore_id`),
  CONSTRAINT `fk_presenze_sessione`
    FOREIGN KEY (`sessione_id`) REFERENCES `sessioni_presenze` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_presenze_giocatore`
    FOREIGN KEY (`giocatore_id`) REFERENCES `giocatori` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `partite` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `categoria_id`   INT(11) NOT NULL,
  `avversario`     VARCHAR(100) NOT NULL,
  `data`           DATE DEFAULT NULL,
  `casa_trasferta` ENUM('Casa','Trasferta') NOT NULL DEFAULT 'Casa',
  `gol_fatti`      TINYINT DEFAULT NULL,
  `gol_subiti`     TINYINT DEFAULT NULL,
  `risultato`      ENUM('Vittoria','Pareggio','Sconfitta') DEFAULT NULL,
  `note`           TEXT DEFAULT NULL,
  `creata_il`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_partite_categoria` (`categoria_id`),
  CONSTRAINT `fk_partite_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorie` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    // Esegui ogni statement separatamente
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt) && $stmt !== 'SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\'') {
            $pdo->exec($stmt);
        }
    }

    // Inserisci categoria di default se non esiste
    $check = $pdo->query("SELECT COUNT(*) as n FROM categorie")->fetch();
    if ($check['n'] == 0) {
        $pdo->exec("INSERT INTO categorie (nome) VALUES ('2019/2020'), ('2018/2019')");
    }

    echo json_encode(['success' => true, 'message' => 'Database installato correttamente!']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
