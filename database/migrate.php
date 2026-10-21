<?php
// migrate.php - Skrip sinkronisasi struktur database dan data bawaan
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS db_etalase_stella CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE db_etalase_stella");

    // Tabel users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $avatarColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetchAll();
    if (empty($avatarColumn)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER password");
    }

    // Tabel projects
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nama_project VARCHAR(150) NOT NULL,
        deskripsi TEXT NOT NULL,
        gambar VARCHAR(255) NOT NULL,
        link_project VARCHAR(255) NOT NULL,
        demo VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Cek kolom demo jika belum ada
    $cols = $pdo->query("SHOW COLUMNS FROM projects LIKE 'demo'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN demo VARCHAR(255) DEFAULT NULL AFTER link_project");
    }

    // Default password hash untuk 'password123'
    $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);

    $defaultUsers = [
        [
            'nama' => 'Super Admin Stella',
            'email' => 'superadmin@stella.sch.id',
            'password' => $hashedPassword,
            'role' => 'super_admin'
        ],
        [
            'nama' => 'Admin Stella',
            'email' => 'admin@stella.sch.id',
            'password' => $hashedPassword,
            'role' => 'admin'
        ],
        [
            'nama' => 'Siswa Contoh',
            'email' => 'siswa@stella.sch.id',
            'password' => $hashedPassword,
            'role' => 'siswa'
        ],
        [
            'nama' => 'Ahmad Fadhil Anwar',
            'email' => 'fadhil@stella.sch.id',
            'password' => $hashedPassword,
            'role' => 'siswa'
        ]
    ];

    $stmtUser = $pdo->prepare("INSERT INTO users (nama, email, password, role) 
        VALUES (:nama, :email, :password, :role)
        ON DUPLICATE KEY UPDATE nama = :nama, password = :password, role = :role");

    foreach ($defaultUsers as $u) {
        $stmtUser->execute($u);
    }

    // Dapatkan ID user Ahmad Fadhil Anwar dan Siswa Contoh untuk sample project jika tabel projects kosong
    $fadhilId = $pdo->query("SELECT id FROM users WHERE email='fadhil@stella.sch.id'")->fetchColumn();
    $siswaId = $pdo->query("SELECT id FROM users WHERE email='siswa@stella.sch.id'")->fetchColumn();

    $projectCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    if ($projectCount == 0) {
        $sampleProjects = [
            [
                'user_id' => $fadhilId ?: 4,
                'nama_project' => 'KantinKu - Sistem Pemesanan Kantin Digital',
                'deskripsi' => 'Aplikasi web pemesanan makanan dan minuman kantin sekolah berbasis online untuk mengurangi antrean siswa di jam istirahat. Dilengkapi menu dinamis, keranjang belanja, invoice digital, dan status pesanan realtime.',
                'gambar' => 'kantinku-preview.svg',
                'link_project' => 'https://github.com/fadhil-anwar/kantinku-app',
                'demo' => 'https://kantinku.demo.stella.sch.id'
            ],
            [
                'user_id' => $fadhilId ?: 4,
                'nama_project' => 'SIM-Alumni SMK Telkom Lampung',
                'deskripsi' => 'Sistem Informasi Manajemen Alumni Stella untuk pendataan penelusuran tamatan (Tracer Study), lowongan kerja, serta forum silaturahmi alumni dengan antarmuka modern.',
                'gambar' => 'sim-alumni.svg',
                'link_project' => 'https://github.com/fadhil-anwar/sim-alumni-stella',
                'demo' => 'https://alumni.demo.stella.sch.id'
            ],
            [
                'user_id' => $siswaId ?: 3,
                'nama_project' => 'Stella Library - E-Perpustakaan Pintar',
                'deskripsi' => 'Katalog perpustakaan digital interaktif dengan fitur peminjaman buku via QR Code, pencarian judul buku cerdas, dan rekapitulasi data anggota perpustakaan.',
                'gambar' => 'stella-library.svg',
                'link_project' => 'https://github.com/smktelkom/stella-library',
                'demo' => ''
            ]
        ];

        $stmtProj = $pdo->prepare("INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project, demo) 
            VALUES (:user_id, :nama_project, :deskripsi, :gambar, :link_project, :demo)");

        foreach ($sampleProjects as $p) {
            $stmtProj->execute($p);
        }
    }

    echo "MIGRATION_SUCCESS\n";
} catch (Exception $e) {
    echo "MIGRATION_ERROR: " . $e->getMessage() . "\n";
}
