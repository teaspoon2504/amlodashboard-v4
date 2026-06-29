<?php
require __DIR__ . '/../config/database.php';

$updates = [
    [2, 'Tindak Lanjut Alert STR', 'A', 'bulanan', 'Silakan lakukan update tindak lanjut Alert STR pada Sistem AML, CFT & CPF (https://brisim.bri.co.id/)', 'setiap Akhir bulan', 'Inputan AMLO'],
    [3, 'STR Proaktif', 'A', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO'],
    [5, 'Pengkinian Bad Data', 'B', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO'],
    [6, 'Tindak Lanjut PEP Sistem AML CFT CPF', 'B', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO'],
    [7, 'E-Learning Target', 'C', 'bulanan', '100%', 'Semester', 'Input PSA + Progress E-Learning'],
    [8, 'Sosialisasi AML CFT CPF', 'D', 'bulanan', '1x setiap semester', 'Semester', 'Inputan AMLO'],
    [9, 'Tindak Lanjut RBA Bankwide', 'E', 'triwulan', 'Action plan sesuai ketentuan 100%', 'Per tenggat aksi', 'Enterprise Risk'],
    [10, 'RFI Remittance', 'BX', 'adhoc', 'target yang diberikan oleh Lead atau HO', 'due date yang diberikan oleh Lead atau HO', 'List request dari Kanpus'],
    [11, 'Report Progress AML CFT CPF', 'F', 'bulanan', 'Laporan + attach 100%', 'Tgl 10 Apr, Jul, Okt, Jan', 'Inputan'],
    [12, 'Adhoc Enhanced Due Diligence (EDD)', 'BX', 'adhoc', 'target yang diberikan oleh Lead atau HO', 'due date yang diberikan oleh Lead atau HO', 'List request dari Kanpus'],
    [13, 'Pendampingan Verifikasi Lapangan', 'X', 'adhoc', 'target yang diberikan oleh Lead atau HO', 'due date yang diberikan oleh Lead atau HO', 'List request Kanpus'],
    [17, 'Adhoc Asistensi UKO', 'X', 'adhoc', 'target yang diberikan oleh Lead atau HO', 'due date yang diberikan oleh Lead atau HO', 'List request Kanpus']
];

foreach ($updates as $u) {
    db_exec("UPDATE task_templates SET nama=?, kategori=?, periode=?, tag=?, target=?, due_label=?, source_link=?, is_active=1 WHERE id=?", 
        [$u[1], $u[2], $u[3], $u[3], $u[4], $u[5], $u[6], $u[0]]);
}

// Deactivate unused
db_exec("UPDATE task_templates SET is_active=0 WHERE id IN (1, 4)");

// Insert missing tasks
$missing = [
    ['Pengkinian CIF ganda', 'B', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO'],
    ['Pengkinian data nasabah', 'B', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO'],
    ['Pengkinian data Beneficial Owner', 'B', 'bulanan', 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait', 'setiap Akhir bulan', 'Inputan AMLO']
];

foreach ($missing as $m) {
    // Check if exists
    $exists = db_fetch_one("SELECT id FROM task_templates WHERE nama=?", [$m[0]]);
    if (!$exists) {
        db_insert("INSERT INTO task_templates (nama, kategori, periode, target, due_label, source_link, is_active, tag) VALUES (?, ?, ?, ?, ?, ?, 1, 'bulanan')",
            [$m[0], $m[1], $m[2], $m[3], $m[4], $m[5]]);
    } else {
        db_exec("UPDATE task_templates SET kategori=?, periode=?, target=?, due_label=?, source_link=?, is_active=1 WHERE id=?",
            [$m[1], $m[2], $m[3], $m[4], $m[5], $exists['id']]);
    }
}

echo "Database updated successfully!\n";
