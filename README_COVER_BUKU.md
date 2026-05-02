# 🎨 Fitur Upload Cover Buku - Perpustakaan Digital

Fitur baru telah ditambahkan untuk memungkinkan upload cover buku secara langsung ke proyek. Ini memungkinkan orang lain untuk membuat cover buku sekreatif mungkin!

## 📋 Perubahan yang Dilakukan

### 1. **Database**
- Tambahkan kolom `cover_buku` ke tabel `databuku` (VARCHAR 255)
- Kolom ini menyimpan nama file cover yang di-upload

### 2. **Backend (PHP)**
- ✅ Handler untuk upload file saat tambah buku
- ✅ Handler untuk update/replace file saat edit buku  
- ✅ Handler untuk hapus file saat buku dihapus
- ✅ Validasi tipe file (hanya image: jpg, jpeg, png, gif)
- ✅ Naming file otomatis untuk mencegah duplikasi

### 3. **Frontend (Form & UI)**
- ✅ Input file untuk upload cover di modal tambah/edit buku
- ✅ Preview gambar sebelum submit
- ✅ Tampilkan cover buku di sebelah judul buku di tabel
- ✅ Support untuk dark mode di input file

### 4. **Folder & Keamanan**
- ✅ Folder `uploads/` untuk menyimpan file cover
- ✅ File `.htaccess` untuk block PHP execution dan directory listing
- ✅ File `index.php` di folder uploads untuk security

## 🚀 Cara Menggunakan

### Setup Awal (Hanya Sekali)

1. **Buka phpMyAdmin** dan navigasi ke database `if0_41808303_perpustakaan_digital`

2. **Jalankan SQL Query:**
   ```sql
   ALTER TABLE databuku ADD COLUMN cover_buku VARCHAR(255) NULL;
   ```
   
   Atau buka file `setup_database.sql` dan jalankan query di dalamnya

3. **Verifikasi** bahwa kolom sudah ditambahkan dengan benar

### Menggunakan Fitur Upload Cover

#### Tambah Buku Baru dengan Cover:
1. Klik tombol "+ Tambah Koleksi" di halaman utama
2. Isi form: Kode Buku, Judul, Pengarang, Penerbit, Tahun Terbit
3. **Di bagian "Cover Buku (Foto)"**, klik untuk upload file gambar
4. Format yang didukung: **JPG, PNG, GIF**
5. Setelah memilih file, preview akan muncul
6. Klik "Simpan"

#### Edit Buku & Ganti Cover:
1. Klik tombol ✏️ (Edit) di baris buku
2. Form akan terbuka dengan data yang ada
3. Untuk mengganti cover, upload file baru di bagian "Cover Buku (Foto)"
4. Klik "Update" untuk menyimpan

#### Hapus Buku:
1. Klik tombol 🗑️ (Hapus) di baris buku
2. Konfirmasi penghapusan
3. File cover akan otomatis dihapus dari folder `uploads/`

## 📁 Struktur Folder

```
perpustakaan-digital/
├── index.php                   (File utama - sudah diupdate)
├── koneksi.php                (Konfigurasi database)
├── uploads/                    (Folder untuk menyimpan cover buku)
│   ├── index.php              (Security - block direct access)
│   ├── .htaccess              (Security rules)
│   └── cover_[timestamp]_[random].jpg|png|gif  (File cover)
├── SETUP_COVER_BUKU.md        (Panduan setup)
├── setup_database.sql         (SQL query untuk database)
└── README.md                  (File ini)
```

## 🎯 Fitur Detail

### Format File yang Didukung
- ✅ JPEG (.jpg, .jpeg)
- ✅ PNG (.png)
- ✅ GIF (.gif)
- ❌ WebP, SVG, BMP (tidak didukung)

### Naming Convention
File cover disimpan dengan format:
```
cover_[timestamp]_[random].jpg
```
Contoh: `cover_1704067200_5432.jpg`

Ini memastikan:
- Tidak ada duplikasi nama file
- Mudah diidentifikasi file cover
- Timestamp membantu untuk tracking

### Validasi & Keamanan
- ✅ Hanya file image yang diizinkan
- ✅ File lama otomatis dihapus saat di-edit/hapus
- ✅ File PHP tidak bisa dijalankan di folder uploads
- ✅ Directory listing dinonaktifkan
- ✅ SQL Injection protection (mysqli_real_escape_string)

## 💡 Tips & Trik

1. **Ukuran Gambar Optimal:**
   - Lebar: 200-300px
   - Tinggi: 300-450px (rasio buku)
   - Format: JPG atau PNG (lebih kecil dari GIF)

2. **Editing Cover:**
   - Buka modal edit
   - Upload gambar baru
   - Gambar lama akan otomatis dihapus
   - Klik update

3. **Troubleshooting:**
   - Jika upload gagal, cek ukuran file
   - Pastikan format file benar (jpg, png, gif)
   - Pastikan folder uploads punya permission write

## 🔒 Keamanan

Fitur ini sudah dilengkapi dengan:
- ✅ Validasi tipe file di backend
- ✅ Naming file random untuk prevent collision
- ✅ Block PHP execution di folder uploads
- ✅ Disable directory listing
- ✅ SQL injection protection

## 📝 Catatan Penting

- Setiap user bisa membuat cover sekreatif mungkin dengan upload file mereka sendiri
- Tidak ada batasan ukuran file di kode (bisa ditambahkan jika perlu)
- File lama akan OTOMATIS dihapus saat buku di-edit atau di-hapus
- Cover dari unsplash default jika tidak ada file yang di-upload

## 🐛 Troubleshooting

### "Gagal upload file"
- Pastikan folder `uploads/` ada dan punya permission 755
- Cek ukuran file (kurangi size gambar jika perlu)

### "Database Error - Kolom tidak ada"
- Jalankan query ALTER TABLE di phpMyAdmin
- Lihat file `setup_database.sql` untuk query yang benar

### "Cover tidak tampil"
- Check browser console untuk error
- Pastikan file ada di folder `uploads/`
- Refresh halaman

## 📞 Support

Jika ada pertanyaan atau issue, silakan:
1. Cek file `SETUP_COVER_BUKU.md` untuk panduan detail
2. Cek `setup_database.sql` untuk query database
3. Baca kode comment di `index.php`

---

**Dibuat dengan ❤️ untuk Perpustakaan Digital UIN Antasari**
