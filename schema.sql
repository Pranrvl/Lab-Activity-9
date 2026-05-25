-- ═══════════════════════════════════════════════════════════════
-- DATABASE SETUP — Pemrograman Web II / Week 7
-- File: schema.sql
-- Jalankan di phpMyAdmin atau MySQL CLI:
--   mysql -u root -p akademik_db < schema.sql
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `akademik_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `akademik_db`;

-- ── Tabel 1: mahasiswa ─────────────────────────────────────────
-- Menyimpan data pokok: NIK dan Nama
-- NIK bersifat UNIQUE (satu mahasiswa satu entri)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mahasiswa_foto`;
DROP TABLE IF EXISTS `mahasiswa`;

CREATE TABLE `mahasiswa` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `nik`        VARCHAR(20)   NOT NULL COMMENT 'Nomor Induk Mahasiswa',
  `nama`       VARCHAR(150)  NOT NULL COMMENT 'Nama lengkap mahasiswa',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data pokok mahasiswa terdaftar';

-- ── Tabel 2: mahasiswa_foto ────────────────────────────────────
-- Relasi 1 mahasiswa → N foto
-- url_foto menyimpan PATH/URL di server, BUKAN binary/blob
-- Keuntungan: DB ringan, file tersimpan di filesystem
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `mahasiswa_foto` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` INT(11)       NOT NULL COMMENT 'FK ke mahasiswa.id',
  `url_foto`     VARCHAR(500)  NOT NULL COMMENT 'URL lokasi foto di server',
  `nama_file`    VARCHAR(255)  NOT NULL COMMENT 'Nama file asli saat upload',
  `uploaded_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mahasiswa_id` (`mahasiswa_id`),
  CONSTRAINT `fk_foto_mahasiswa`
    FOREIGN KEY (`mahasiswa_id`)
    REFERENCES `mahasiswa` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='URL foto mahasiswa — file fisik di /server/uploads/';

-- ── Contoh query untuk verifikasi ─────────────────────────────
-- SELECT m.nik, m.nama, f.url_foto, f.nama_file, f.uploaded_at
-- FROM mahasiswa m
-- JOIN mahasiswa_foto f ON f.mahasiswa_id = m.id
-- ORDER BY m.created_at DESC;