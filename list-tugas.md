# Audit Tugas AMLO

Berikut adalah hasil audit seluruh tugas AMLO yang ada pada sistem (berdasarkan tabel `task_templates`), lengkap dengan detail informasi serta kapabilitas masing-masing pengguna (Role Capability).

## Daftar Tugas (Task List)

Tabel berikut memuat detail lengkap seluruh tugas (Task Templates) yang saat ini aktif di dalam sistem.

| ID | Nama Tugas | Kategori | Periode | Target | Tenggat Waktu (Due Label) | Sumber Data (Source Link) |
|---|---|:---:|:---:|---|---|---|
Tindak Lanjut Alert STR | A | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
STR Proaktif | A | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
Pengkinian Bad Data | B | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
Pengkinian CIF ganda | B | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
Pengkinian data nasabah | B | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
Pengkinian data Beneficial Owner | B | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
Tindak Lanjut PEP Sistem AML CFT CPF | B | Bulanan | Sesuai target yang didistribusikan oleh lead dari target total yang disetting oleh HO pada bulan terkait | setiap Akhir bulan | Inputan AMLO |
E-Learning Target | C | Semesteran | 100% | Semester | Input PSA + Progress E-Learning |
Sosialisasi AML CFT CPF | D | Semesteran | 1x setiap semester | Semester | Inputan AMLO |
Tindak Lanjut RBA Bankwide | E | Triwulan | Action plan sesuai ketentuan 100% | Per tenggat aksi | Enterprise Risk |
Report Progress AML CFT CPF | F | Triwulan | Laporan + attach 100% | Tgl 10 Apr, Jul, Okt, Jan | Inputan |
RFI Remittance | BX | Adhoc | target yang diberikan oleh Lead atau HO | due date yang diberikan oleh Lead atau HO | List request dari Kanpus |
Adhoc Enhanced Due Diligence (EDD) | BX | Adhoc | target yang diberikan oleh Lead atau HO | due date yang diberikan oleh Lead atau HO | List request dari Kanpus |
Pendampingan AML | X | Adhoc | target yang diberikan oleh Lead atau HO | due date yang diberikan oleh Lead atau HO | List request Kanpus |


---

## Kapabilitas Pengguna (Role Capability)

Dalam pengelolaan dan eksekusi tugas-tugas di atas, masing-masing peran memiliki kapabilitas (hak akses dan otoritas) yang berbeda-beda sebagai berikut:

### 1. AMLO (Officer)
Peran AMLO adalah sebagai eksekutor operasional harian yang melaksanakan tugas sesuai target.
- **Menerima Target:** Menerima kuota atau alokasi tugas (untuk task bulanan) yang didistribusikan oleh Lead.
- **Memperbarui Progress:** Mengisi dan memperbarui progress dari jumlah target yang telah diberikan dan memberikan keterangan terkait tugas tersebut
- **Mengirim Persetujuan (Submit):** Mengajukan laporan tugas yang telah selesai (`100%`) kepada Lead untuk di- *review* dan *approve*.
- **Menerima Feedback:** Menerima masukan/teguran (*feedback*) dari Head Office terkait kualitas laporan.

### 2. Lead (Regional Office)
Peran Lead adalah sebagai supervisor di tingkat wilayah yang bertugas memantau kinerja dan mendistribusikan beban tugas.
- **Menerima Plafon Target:** Menerima *plafon* atau batas maksimal kuota target dari setiap kategori tugas yang akan didistribusikan ke AMLO di bawahnya dari HO.
- **Distribusi Target (Manajemen Tugas):** Membagi dan mendistribusikan kuota target dari HO kepada masing-masing AMLO di bawah wilayahnya. (Sistem memastikan total distribusi Lead tidak boleh melampaui Plafon HO).
- **Approval Laporan:** Menyetujui (*approve*) atau menolak (*reject*) laporan akhir tugas yang disubmit oleh AMLO (khusus tugas dengan status selesai `100%`).
- **Monitoring Officer:** Memantau kemajuan (*progress*) setiap AMLO di bawah supervisinya secara detail (Siapa yang *exceed*, *good*, atau *below* target).
- **Memberikan Feedback:** (Jika berlaku) memberikan teguran internal kepada AMLO di bawahnya, serta melihat masukan dari HO.

### 3. HO (Head Office Assurance)
Peran Head Office (HO) adalah sebagai pembuat kebijakan, menetapkan target global, dan memantau keseluruhan wilayah (nasional).
- **Penentuan Target Global (Manajemen Tugas):** Memberikan *plafon target* secara kolektif untuk masing-masing Regional Office per bulan dan tahun tertentu.
- **Monitoring Wilayah (Agregat):** Memantau agregat performa seluruh Regional Office secara makro (Melihat Regional Office mana yang berkinerja unggul dan Regional Office  yang butuh perhatian khusus).
- **Assessment & Feedback:** Memberikan evaluasi, penilaian (*assessment*), dan umpan balik (*feedback*) spesifik terhadap laporan tugas yang dikerjakan oleh AMLO maupun kepada Lead Regional Office .

---
> [!NOTE]
> Kapabilitas ini sudah terintegrasi penuh ke dalam sistem melalui modul "Manajemen Tugas", modul "To-Do Harian", dan modul "Approvals".
