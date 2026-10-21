-- ============================================================================
-- DATABASE: db_etalase_stella
-- Judul    : Etalase Digital SMK Telkom Lampung
-- Pembuat  : Ahmad Fadhil Anwar (Kelas XI RPL)
-- Guru     : Pak Ikhwan
-- ============================================================================

CREATE DATABASE IF NOT EXISTS db_etalase_stella 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db_etalase_stella;

-- ----------------------------------------------------------------------------
-- 1. Tabel users (Multi-Role: super_admin, admin, siswa)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 2. Tabel projects (Karya Siswa)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_project VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    link_project VARCHAR(255) NOT NULL,
    demo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 3. Akun Default (Password untuk semua akun: password123)
-- ----------------------------------------------------------------------------
INSERT INTO users (nama, email, password, role) VALUES
('Super Admin Stella', 'superadmin@stella.sch.id', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1xlb1G5X5K8d.V0eQ06b3Ff9p5K9e.', 'super_admin'),
('Admin Stella', 'admin@stella.sch.id', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1xlb1G5X5K8d.V0eQ06b3Ff9p5K9e.', 'admin'),
('Siswa Contoh', 'siswa@stella.sch.id', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1xlb1G5X5K8d.V0eQ06b3Ff9p5K9e.', 'siswa'),
('Ahmad Fadhil Anwar', 'fadhil@stella.sch.id', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1xlb1G5X5K8d.V0eQ06b3Ff9p5K9e.', 'siswa')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

-- ----------------------------------------------------------------------------
-- 4. Sample Data Project
-- ----------------------------------------------------------------------------
INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project, demo) VALUES
(4, 'KantinKu - Sistem Pemesanan Kantin Digital', 'Aplikasi web pemesanan makanan dan minuman kantin sekolah berbasis online untuk mengurangi antrean siswa di jam istirahat. Dilengkapi menu dinamis, keranjang belanja, invoice digital, dan status pesanan realtime.', 'kantinku-preview.svg', 'https://github.com/fadhil-anwar/kantinku-app', 'https://kantinku.demo.stella.sch.id'),
(4, 'SIM-Alumni SMK Telkom Lampung', 'Sistem Informasi Manajemen Alumni Stella untuk pendataan penelusuran tamatan (Tracer Study), lowongan kerja, serta forum silaturahmi alumni dengan antarmuka modern.', 'sim-alumni.svg', 'https://github.com/fadhil-anwar/sim-alumni-stella', 'https://alumni.demo.stella.sch.id'),
(3, 'Stella Library - E-Perpustakaan Pintar', 'Katalog perpustakaan digital interaktif dengan fitur peminjaman buku via QR Code, pencarian judul buku cerdas, dan rekapitulasi data anggota perpustakaan.', 'stella-library.svg', 'https://github.com/smktelkom/stella-library', NULL)
ON DUPLICATE KEY UPDATE nama_project = VALUES(nama_project);
