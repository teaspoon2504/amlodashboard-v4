<?php
require_once __DIR__ . '/includes/functions.php';

try {
    echo "Starting DB update...\n";
    
    // 1. Clear related tables
    db_exec("DELETE FROM feedbacks");
    db_exec("DELETE FROM approvals");
    db_exec("DELETE FROM submissions");
    db_exec("DELETE FROM task_progress");
    db_exec("DELETE FROM task_targets");
    
    // 2. Clear task templates
    db_exec("DELETE FROM task_templates");
    db_exec("ALTER TABLE task_templates AUTO_INCREMENT = 1");
    
    // 3. Insert new tasks
    $tasks = [
        [
            'nama' => 'Tindak Lanjut Alert STR',
            'kategori' => 'A',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'STR Proaktif',
            'kategori' => 'A',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Pengkinian Bad Data',
            'kategori' => 'B',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Pengkinian CIF ganda',
            'kategori' => 'B',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Pengkinian data nasabah',
            'kategori' => 'B',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Pengkinian data Beneficial Owner',
            'kategori' => 'B',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Tindak Lanjut PEP Sistem AML CFT CPF',
            'kategori' => 'B',
            'periode' => 'bulanan',
            'tag' => 'bulanan',
            'target' => 'Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait',
            'due_label' => 'setiap Akhir bulan',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'E-Learning Target',
            'kategori' => 'C',
            'periode' => 'semesteran',
            'tag' => 'semesteran',
            'target' => '100%',
            'due_label' => 'Semester',
            'source_link' => 'Input PSA + Progress E-Learning'
        ],
        [
            'nama' => 'Sosialisasi AML CFT CPF',
            'kategori' => 'D',
            'periode' => 'semesteran',
            'tag' => 'semesteran',
            'target' => '1x setiap semester',
            'due_label' => 'Semester',
            'source_link' => 'Inputan AMLO'
        ],
        [
            'nama' => 'Tindak Lanjut RBA Bankwide',
            'kategori' => 'E',
            'periode' => 'triwulan',
            'tag' => 'triwulan',
            'target' => 'Action plan sesuai ketentuan 100%',
            'due_label' => 'Per tenggat aksi',
            'source_link' => 'Enterprise Risk'
        ],
        [
            'nama' => 'Report Progress AML CFT CPF',
            'kategori' => 'F',
            'periode' => 'triwulan',
            'tag' => 'triwulan',
            'target' => 'Laporan + attach 100%',
            'due_label' => 'Tgl 10 Apr, Jul, Okt, Jan',
            'source_link' => 'Inputan'
        ],
        [
            'nama' => 'RFI Remittance',
            'kategori' => 'BX',
            'periode' => 'adhoc',
            'tag' => 'adhoc',
            'target' => 'target yang diberikan oleh Lead atau HO',
            'due_label' => 'due date yang diberikan oleh Lead atau HO',
            'source_link' => 'List request dari Kanpus'
        ],
        [
            'nama' => 'Adhoc Enhanced Due Diligence (EDD)',
            'kategori' => 'BX',
            'periode' => 'adhoc',
            'tag' => 'adhoc',
            'target' => 'target yang diberikan oleh Lead atau HO',
            'due_label' => 'due date yang diberikan oleh Lead atau HO',
            'source_link' => 'List request dari Kanpus'
        ],
        [
            'nama' => 'Adhoc Pendampingan AML',
            'kategori' => 'X',
            'periode' => 'adhoc',
            'tag' => 'adhoc',
            'target' => 'target yang diberikan oleh Lead atau HO',
            'due_label' => 'due date yang diberikan oleh Lead atau HO',
            'source_link' => 'List request Kanpus'
        ]
    ];

    foreach ($tasks as $t) {
        db_exec(
            "INSERT INTO task_templates (nama, kategori, periode, tag, target, due_label, source_link, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
            [$t['nama'], $t['kategori'], $t['periode'], $t['tag'], $t['target'], $t['due_label'], $t['source_link']]
        );
    }
    
    echo "Tasks successfully populated.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
