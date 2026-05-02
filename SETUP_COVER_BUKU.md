# Setup Fitur Cover Buku

Untuk menggunakan fitur upload cover buku, Anda perlu menambahkan kolom `cover_buku` ke tabel `databuku` di database.

## Langkah 1: Update Database

Jalankan query SQL berikut di phpMyAdmin atau client SQL Anda:

```sql
ALTER TABLE databuku ADD COLUMN cover_buku VARCHAR(255) NULL;
```

Atau jika kolom sudah ada, pastikan tipe datanya sudah benar:

```sql
ALTER TABLE databuku MODIFY COLUMN cover_buku VARCHAR(255) NULL;
```

## Langkah 2: Verifikasi Struktur Tabel

Setelah menjalankan query, struktur tabel `databuku` seharusnya terlihat seperti ini:

| Kolom | Tipe | Null | Default |
|-------|------|------|---------|
| kode_buku | VARCHAR | NO | |
| judul_buku | VARCHAR | NO | |
| pengarang | VARCHAR | NO | |
| penerbit | VARCHAR | NO | |
| tahun_terbit | INT | NO | |
| cover_buku | VARCHAR | YES | NULL |

## Langkah 3: Folder Uploads

Pastikan folder `uploads/` sudah ada di direktori project:
- `c:\xampp\htdocs\perpustakaan-digital\uploads\`

Folder ini sudah otomatis dibuat oleh sistem.

## Fitur yang Ditambahkan

✅ **Upload Cover Buku**
- Ketika membuat buku baru, Anda bisa upload foto cover
- Format yang didukung: JPG, JPEG, PNG, GIF
- Preview foto sebelum upload

✅ **Edit Cover Buku**
- Saat edit buku, Anda bisa mengganti cover
- Cover lama akan otomatis dihapus

✅ **Tampilan Cover di Tabel**
- Cover buku akan ditampilkan di sebelah judul buku
- Jika tidak ada cover, akan menampilkan default image

✅ **Delete Otomatis**
- Ketika buku dihapus, file cover juga akan dihapus otomatis

## Catatan Keamanan

- Folder `uploads/` berisi file gambar yang di-upload oleh pengguna
- Hanya format image yang diizinkan: JPG, JPEG, PNG, GIF
- Ukuran file maksimal tidak dibatasi di kode (opsional: tambahkan validasi ukuran)

## Troubleshooting

### Error: "Kolom cover_buku tidak ada"
→ Jalankan query ALTER TABLE seperti di Langkah 1

### Error: "Gagal upload file"
→ Pastikan folder `uploads/` punya permission untuk write (chmod 755)

### Cover tidak tampil
→ Pastikan file ada di folder `uploads/` dan path benar

