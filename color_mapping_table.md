# UI Audit: Color Mapping Table

Tabel pemetaan ini berisi seluruh warna yang diekstrak dari file `assets/css/amlo-design-system.scss`. Kolom "Warna Baru (Design System)" sengaja dikosongkan agar Anda dapat mengisinya dengan palet dari SVG Design System baru Anda.

### 1. Backgrounds & Surfaces (Latar Belakang & Area)

| No | Komponen / Elemen | Warna Existing (Lama) | Warna Baru (Design System) | Keterangan / State |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Background Page | `#0b1929` (`var(--canvas)`) | | Main Background |
| 2 | Sidebar, Cards, Topbar | `#122236` (`var(--surface-soft)`) | | Soft Surface Background |
| 3 | Input, Select, Textarea | `#1a3352` (`var(--surface-elevated)`) | | Elevated Surface |
| 4 | Translucent Overlay | `rgba(26, 51, 82, 0.6)` (`var(--surface-translucent)`) | | Translucent Surface |
| 5 | Modal Overlay | `rgba(0, 0, 0, 0.6)` | | Modal Backdrop Blur |
| 6 | Table Group Header | `rgba(212, 175, 55, 0.05)` | | Latar Belakang Row Grouping Tabel |

### 2. Borders, Dividers & Outlines (Garis & Pembatas)

| No | Komponen / Elemen | Warna Existing (Lama) | Warna Baru (Design System) | Keterangan / State |
| :--- | :--- | :--- | :--- | :--- |
| 7 | Main Border / Divider | `rgba(255, 255, 255, 0.08)` (`var(--hairline)`) | | Garis batas standar / Scrollbar thumb |
| 8 | Soft Divider / Hover BG | `rgba(255, 255, 255, 0.05)` (`var(--hairline-soft)`) | | Item Hover Background / Soft Borders |
| 9 | KPI Card Hover | `rgba(255, 255, 255, 0.15)` | | Border Color saat di-hover |

### 3. Typography & Text (Teks)

| No | Komponen / Elemen | Warna Existing (Lama) | Warna Baru (Design System) | Keterangan / State |
| :--- | :--- | :--- | :--- | :--- |
| 10 | Primary Text / Heading | `#f0f4f8` (`var(--ink-deep)`) | | Teks Utama, Title |
| 11 | Standard Text | `#d4dde6` (`var(--ink)`) | | Teks Body, List Item |
| 12 | Subheading Text | `#b0bcc8` (`var(--charcoal)`) | | Sub-judul |
| 13 | Slate Text | `#8a9cae` (`var(--slate)`) | | Teks keterangan minor |
| 14 | Muted Text / Labels | `#7a93ab` (`var(--steel)`) | | Teks Label Input, Waktu, Deskripsi sekunder |
| 15 | Stone Text | `#5d6c7b` (`var(--stone)`) | | Teks untuk Nav Section Label |
| 16 | Disabled Text | `#455261` (`var(--disabled)`) | | Teks State Disabled |
| 17 | Button Text | `#ffffff` (`var(--ink-button)`) | | Teks dalam tombol (Normal) |
| 18 | Button Text Pressed | `#d4dde6` (`var(--ink-button-pressed)`) | | Teks dalam tombol (Pressed/Active) |

### 4. Brand & Accents (Warna Aksen & Interaktif)

| No | Komponen / Elemen | Warna Existing (Lama) | Warna Baru (Design System) | Keterangan / State |
| :--- | :--- | :--- | :--- | :--- |
| 19 | Primary Brand Accent | `#0064e0` (`var(--primary)`) | | Default Button, Icon, Outline |
| 20 | Primary Brand Deep | `#0457cb` (`var(--primary-deep)`) | | Button Hover / Active State |
| 21 | Primary Soft BG | `rgba(0, 100, 224, 0.15)` (`var(--primary-soft)`) | | Background Badge Cobalt / KPI Card |
| 22 | Focus Ring | `rgba(0, 100, 224, 0.4)` (`var(--primary-ring)`) | | Input Outline saat Focus |
| 23 | Meta Link | `#385898` (`var(--meta-link)`) | | Link Warna Alternatif |
| 24 | FB Blue | `#1876f2` (`var(--fb-blue)`) | | Brand Warna Spesifik |
| 25 | Purple Accent | `#a121ce` (`var(--oculus-purple)`) | | Badge Purple |
| 26 | Purple Soft BG | `rgba(161, 33, 206, 0.15)` (`var(--oculus-purple-bg)`) | | Background Badge / KPI Card Purple |
| 27 | AMLO Gold Accent | `#c8a84b` (`var(--gold)`) | | Teks Total Progress, Icon Officer |
| 28 | AMLO Gold Soft BG | `rgba(200, 168, 75, 0.15)` (`var(--gold-soft)`) | | Background Icon Avatar |
| 29 | Teal Accent | `#1b8f9e` (`var(--teal)`) | | Aksen Tambahan |
| 30 | Teal Light Accent | `#25b5c9` (`var(--teal-light)`) | | Global Link `a`, Badge Nama Officer |

### 5. Semantic & Status (Warna Status: Success, Warning, Error)

| No | Komponen / Elemen | Warna Existing (Lama) | Warna Baru (Design System) | Keterangan / State |
| :--- | :--- | :--- | :--- | :--- |
| 31 | Success (Green) | `#31a24c` (`var(--success)`) | | Badge Exceed, Status Selesai |
| 32 | Success Strong | `#24e400` (`var(--success-strong)`) | | Hover/Active Success |
| 33 | Success BG | `rgba(49, 162, 76, 0.15)` (`var(--success-bg)`) | | Latar Belakang Badge Exceed |
| 34 | Attention (Orange) | `#f2a918` (`var(--attention)`) | | Status In Progress, Waiting |
| 35 | Attention BG | `rgba(242, 169, 24, 0.15)` (`var(--attention-bg)`) | | Latar Belakang Status Progress |
| 36 | Warning (Yellow) | `#f7b928` (`var(--warning)`) | | Promo Banner, Alert |
| 37 | Warning Vivid | `#ffe200` (`var(--warning-vivid)`) | | Warning Strong State |
| 38 | Warning BG | `rgba(255, 226, 0, 0.15)` (`var(--warning-bg)`) | | Latar Belakang Warning |
| 39 | Critical (Red) | `#e41e3f` (`var(--critical)`) | | Status Below, Pending |
| 40 | Critical Strong | `#f0284a` (`var(--critical-strong)`) | | Text Error, Alert Dot |
| 41 | Critical BG | `rgba(224, 82, 82, 0.15)` (`var(--critical-bg)`) | | Latar Belakang Status Below |
| 42 | Custom Perf Good Text | `#3498db` | | Teks Status "Good" (80% - 99%) |
| 43 | Custom Perf Good BG | `rgba(52, 152, 219, 0.15)` | | Latar Belakang Status "Good" |
