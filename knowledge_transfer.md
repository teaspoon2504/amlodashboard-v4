# AMLO Dashboard v2.0 - Knowledge Transfer & System Context

Dokumen ini berisi rangkuman arsitektur, fitur-fitur utama, dan pembaruan sistem terakhir yang telah dipelajari dan diimplementasikan. Anda dapat menggunakan dokumen ini sebagai referensi untuk asisten AI lain di perangkat/device yang berbeda agar dapat melanjutkan pengembangan dengan pemahaman (knowledge) yang sama.

---

## 1. Ikhtisar Proyek (Project Overview)
- **Nama Sistem:** AMLO Dashboard v2.0
- **Tujuan:** Sistem Monitoring Aktivitas & Kinerja Harian AMLO (Anti Money Laundering Officer) untuk memantau status penyelesaian tugas KYC, CDD, EDD, RFI Remittance, dll.
- **Teknologi Utama:** 
  - Backend: **Vanilla PHP 8+** (Tanpa framework besar, menggunakan arsitektur procedural/modular yang rapi)
  - Database: **MySQL** (via PDO di `config/database.php`)
  - Frontend: **Vanilla HTML/CSS/JS** dengan Design System khusus (`amlo-design-system.css`).
- **Rebranding Terakhir:** Seluruh atribut yang awalnya menggunakan "Bank BRI" dan `@bankbri.co.id` telah diubah secara global menjadi **"AMLODashboard"** dan `@amlodashboard.com`.

---

## 2. Struktur Direktori Utama
- `/api/` : Endpoint backend yang merespons dengan JSON (mis. `tasks.php`, `approvals.php`, `feedback.php`). Digunakan untuk AJAX/Fetch dari frontend.
- `/pages/` : Halaman antarmuka (UI) PHP (mis. `dashboard.php`, `tasks.php`, `login.php`).
- `/includes/` : File logika bisnis, auth, dan *helper functions* (mis. `auth.php`, `functions.php`).
- `/assets/` : Menyimpan CSS Design System, file JS, dan font (Inter).
- `/config/` : Pengaturan koneksi *database* (`database.php`) dan inisialisasi lingkungan (`init.php`).

---

## 3. Sistem Peran (User Roles & Capabilities)
Sistem memiliki 3 tingkat akses (*role-based access control*):
1. **AMLO Officer (`officer`)**: Level pelaksana di wilayah. Mengerjakan tugas (To-Do Harian), memperbarui *progress* (1-100%), dan ketika mencapai 100%, dapat melakukan **Request Approval** ke Lead.
2. **Lead Kanwil (`lead`)**: Level manajerial. Bisa membuat dan mendelegasikan tugas ke Officer di wilayahnya, memonitor performa tim, serta melakukan **Approve/Reject** atas tugas Officer yang *Waiting for Approval*.
3. **Head Office (`ho`)**: Level eksekutif nasional. Memantau performa semua Kanwil secara agregat, memberikan *Assessment & Feedback* (teguran), dan melihat indikator KPI "Wilayah Good".

---

## 4. Fitur Spesifik yang Baru Diimplementasikan (Approval Workflow)
Alur kerja yang paling krusial dan baru ditambahkan adalah mekanisme validasi tugas:
1. **Aturan Baru:** Tugas yang sudah 100% *progress*-nya tidak lagi langsung dianggap selesai secara sepihak oleh Officer.
2. **Pengajuan (Submission):** Officer wajib menekan tombol "📤 Request for Approval". Status pengajuan akan masuk ke tabel `submissions` dengan status `pending`.
3. **UI Officer:** Tugas yang sedang diajukan akan mendapatkan *badge* "⌛ WAITING FOR APPROVAL" di halaman To-Do Harian. Di Dashboard, ini dihitung dalam KPI *Waiting Approval* (kartu warna oranye).
4. **Persetujuan Lead:** Lead membuka tugas tersebut dan menekan tombol "✅ APPROVE TUGAS INI".
5. **Finalisasi:** Setelah di-*approve*, API `approvals.php` akan meng-_update_ tabel `submissions` dan `task_progress` menjadi `approved`. Di UI, tugas ini resmi mendapatkan *badge* "✅ SELESAI" dan dihitung ke dalam KPI *Selesai* (kartu hijau).

---

## 5. Perubahan Skema Database Terakhir
Untuk mengeksekusi fitur *Approval Workflow*, terdapat pembaruan skema yang harus selalu dipastikan sudah ada di server produksi:

1. **Tabel `task_progress`**: Kolom `status` ditambahkan opsi `approved` (Enum: `'pending','active','done','approved'`).
2. **Tabel `submissions`**: Menyimpan data tugas yang diajukan Officer ke Lead.
   - Kolom penting: `task_progress_id`, `submitted_by`, `status` (`pending/approved/rejected`).
3. **Tabel `approvals`**: Menyimpan rekam jejak (*log*) siapa Lead/HO yang melakukan *approve* atau *reject* beserta catatannya.

---

## 6. Status Deployment & Shared Hosting
- **Server:** Aplikasi ini di-*deploy* ke Shared Hosting menggunakan cPanel.
- **Metode Update:** Seluruh pembaruan di-*package* dalam file `amlodashboard_deploy_update.zip` (mengabaikan `.git` dan `node_modules`). Zip ini di-*upload* ke File Manager cPanel dan di-ekstrak (overwrite) pada direktori `public_html`.
- **Integrasi UML:** Diagram Use Case yang merepresentasikan alur kerja ini tersedia di `uml_diagram.html` dengan desain responsif (dioptimalkan untuk format *print* kertas A4).

---

*Gunakan file ini sebagai referensi (context) setiap kali Anda memulai sesi *pair programming* baru dengan asisten AI lain.*
