-- Query untuk menambahkan kolom cover_buku ke tabel databuku
-- Jalankan query ini di phpMyAdmin atau SQL client Anda

-- Tambahkan kolom cover_buku jika belum ada
ALTER TABLE databuku ADD COLUMN cover_buku VARCHAR(255) NULL;

-- Alternatif: Jika kolom sudah ada tapi tipe datanya berbeda, ubah tipe datanya
-- ALTER TABLE databuku MODIFY COLUMN cover_buku VARCHAR(255) NULL;

-- Verifikasi struktur tabel
DESC databuku;
