<?php
// DEBUG MODE - aktifkan saat development, matikan saat production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Koneksi database
include 'koneksi.php';

// ============================================================
// PASTIKAN FOLDER UPLOADS ADA
// ============================================================
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Cek apakah kolom cover_buku sudah ada di database
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM databuku LIKE 'cover_buku'");
$hasCoverColumn = mysqli_num_rows($checkColumn) > 0;

// Query dasar
if ($hasCoverColumn) {
    $query = "SELECT * FROM databuku";
} else {
    $query = "SELECT kode_buku, judul_buku, pengarang, penerbit, tahun_terbit, NULL as cover_buku FROM databuku";
}

$result = mysqli_query($conn, $query);

// Jika query gagal (fallback)
if (!$result) {
    $query = "SELECT kode_buku, judul_buku, pengarang, penerbit, tahun_terbit, NULL as cover_buku FROM databuku";
    $result = mysqli_query($conn, $query);
}

$totalBuku = mysqli_num_rows($result);

// Simpan data buku ke array PHP
$books = [];
while ($row = mysqli_fetch_assoc($result)) {
    $books[] = $row;
}

// ============================================================
// HANDLE TAMBAH BUKU
// ============================================================
if (isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $kode_buku    = mysqli_real_escape_string($conn, $_POST['kode_buku']);
    $judul_buku   = mysqli_real_escape_string($conn, $_POST['judul_buku']);
    $pengarang    = mysqli_real_escape_string($conn, $_POST['pengarang']);
    $penerbit     = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $tahun_terbit = mysqli_real_escape_string($conn, $_POST['tahun_terbit']);
    
    // Handle file upload
    $cover_buku = null;
    if (isset($_FILES['cover_buku']) && $_FILES['cover_buku']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['cover_buku']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            // Generate nama unik
            $newname = 'cover_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
            $uploadpath = $uploadDir . '/' . $newname;
            
            if (move_uploaded_file($_FILES['cover_buku']['tmp_name'], $uploadpath)) {
                $cover_buku = $newname;
            }
        }
    }
    
    if ($hasCoverColumn) {
        $sql = "INSERT INTO databuku (kode_buku, judul_buku, pengarang, penerbit, tahun_terbit, cover_buku)
                VALUES ('$kode_buku', '$judul_buku', '$pengarang', '$penerbit', '$tahun_terbit', " . ($cover_buku ? "'$cover_buku'" : "NULL") . ")";
    } else {
        $sql = "INSERT INTO databuku (kode_buku, judul_buku, pengarang, penerbit, tahun_terbit)
                VALUES ('$kode_buku', '$judul_buku', '$pengarang', '$penerbit', '$tahun_terbit')";
    }
    
    mysqli_query($conn, $sql);
    header("Location: index.php?toast=tambah");
    exit();
}

// ============================================================
// HANDLE EDIT BUKU
// ============================================================
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $kode_buku    = mysqli_real_escape_string($conn, $_POST['kode_buku']);
    $judul_buku   = mysqli_real_escape_string($conn, $_POST['judul_buku']);
    $pengarang    = mysqli_real_escape_string($conn, $_POST['pengarang']);
    $penerbit     = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $tahun_terbit = mysqli_real_escape_string($conn, $_POST['tahun_terbit']);
    $old_kode     = mysqli_real_escape_string($conn, $_POST['old_kode']);
    
    // Handle file upload untuk edit
    $cover_update = "";
    if (isset($_FILES['cover_buku']) && $_FILES['cover_buku']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['cover_buku']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            // Hapus cover lama jika ada
            if ($hasCoverColumn) {
                $oldQuery = "SELECT cover_buku FROM databuku WHERE kode_buku='$old_kode'";
                $oldResult = mysqli_query($conn, $oldQuery);
                if ($oldResult && $oldRow = mysqli_fetch_assoc($oldResult)) {
                    if (!empty($oldRow['cover_buku']) && file_exists($uploadDir . '/' . $oldRow['cover_buku'])) {
                        unlink($uploadDir . '/' . $oldRow['cover_buku']);
                    }
                }
            }
            
            $newname = 'cover_' . time() . '_' . rand(1000, 9999) . '.' . $filetype;
            $uploadpath = $uploadDir . '/' . $newname;
            
            if (move_uploaded_file($_FILES['cover_buku']['tmp_name'], $uploadpath)) {
                $cover_update = ", cover_buku='$newname'";
            }
        }
    }
    
    if ($hasCoverColumn && !empty($cover_update)) {
        $sql = "UPDATE databuku SET kode_buku='$kode_buku', judul_buku='$judul_buku',
                pengarang='$pengarang', penerbit='$penerbit', tahun_terbit='$tahun_terbit'$cover_update
                WHERE kode_buku='$old_kode'";
    } else {
        $sql = "UPDATE databuku SET kode_buku='$kode_buku', judul_buku='$judul_buku',
                pengarang='$pengarang', penerbit='$penerbit', tahun_terbit='$tahun_terbit'
                WHERE kode_buku='$old_kode'";
    }
    
    mysqli_query($conn, $sql);
    header("Location: index.php?toast=edit");
    exit();
}

// ============================================================
// HANDLE HAPUS BUKU
// ============================================================
if (isset($_GET['hapus'])) {
    $kode = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Hapus file cover jika ada
    if ($hasCoverColumn) {
        $coverQuery = "SELECT cover_buku FROM databuku WHERE kode_buku='$kode'";
        $coverResult = mysqli_query($conn, $coverQuery);
        if ($coverResult && $coverRow = mysqli_fetch_assoc($coverResult)) {
            if (!empty($coverRow['cover_buku']) && file_exists($uploadDir . '/' . $coverRow['cover_buku'])) {
                unlink($uploadDir . '/' . $coverRow['cover_buku']);
            }
        }
    }
    
    $sql = "DELETE FROM databuku WHERE kode_buku='$kode'";
    mysqli_query($conn, $sql);
    header("Location: index.php?toast=hapus");
    exit();
}

// ============================================================
// FUNGSI HELPER: Dapatkan URL cover
// ============================================================
function getCoverUrl($cover_buku) {
    $uploadDir = __DIR__ . '/uploads';
    $defaultCover = 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=200';
    
    if (!empty($cover_buku) && file_exists($uploadDir . '/' . $cover_buku)) {
        return 'uploads/' . $cover_buku;
    }
    return $defaultCover;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Premium - Perpustakaan Digital UIN Antasari</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-blue: #07058a;
            --secondary-blue: #1e40af;
            --accent-blue: #3b82f6;
            --primary-gold: #fbbf24;
            --secondary-gold: #f59e0b;
            --dark-gold: #d97706;
            --bg-light: #fefce8;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #07058a 0%, #1e3a8a 40%, #f59e0b 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #1e293b;
            transition: all 0.3s ease;
        }
        body.dark-mode {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        /* Header */
        .header {
            text-align: center; margin-bottom: 40px; padding: 40px;
            background: rgba(255,255,255,0.98); border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            border: 3px solid var(--primary-gold); transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        body.dark-mode .header { background: rgba(30,41,59,0.98); }
        .header::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, var(--primary-gold), var(--secondary-gold), var(--dark-gold));
        }
        .university-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue));
            color: white; padding: 8px 20px; border-radius: 50px;
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 2px; margin-bottom: 20px; border: 2px solid var(--primary-gold);
        }
        h1 { font-family: 'Outfit', sans-serif; font-size: 48px; color: #0f172a; margin-bottom: 10px; font-weight: 800; }
        body.dark-mode h1 { color: #f1f5f9; }
        .highlight { color: var(--secondary-gold); }
        .subtitle { font-size: 16px; color: #64748b; max-width: 600px; margin: 0 auto 30px; line-height: 1.6; }
        body.dark-mode .subtitle { color: #94a3b8; }
        /* Settings Panel */
        .settings-panel {
            position: fixed; top: 20px; right: 20px;
            display: flex; gap: 10px; z-index: 100;
            flex-wrap: wrap; justify-content: flex-end; max-width: 300px;
        }
        .toggle-btn, .action-btn {
            background: rgba(255,255,255,0.95); border: 2px solid var(--primary-gold);
            padding: 12px 20px; border-radius: 50px; cursor: pointer;
            display: flex; align-items: center; gap: 8px;
            font-weight: 600; font-size: 13px; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a;
        }
        body.dark-mode .toggle-btn, body.dark-mode .action-btn {
            background: rgba(30,41,59,0.95); color: #e2e8f0;
        }
        .toggle-btn:hover, .action-btn:hover { transform: translateY(-2px); }
        /* Stats Bar */
        .stats-bar { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
        .stat-item {
            text-align: center; padding: 20px 35px;
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue));
            border-radius: 20px; color: white; min-width: 140px;
            border: 3px solid var(--primary-gold); transition: all 0.3s ease;
        }
        .stat-item:hover { transform: translateY(-5px); }
        .stat-number { font-family: 'Outfit', sans-serif; font-size: 36px; font-weight: 800; color: var(--primary-gold); }
        .stat-label { font-size: 13px; margin-top: 5px; font-weight: 600; }
        /* Control Panel */
        .control-panel {
            background: rgba(255,255,255,0.98); border-radius: 20px;
            padding: 25px 30px; margin-bottom: 30px;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.2);
            border: 2px solid var(--primary-gold);
            display: flex; gap: 20px; align-items: center; flex-wrap: wrap;
        }
        body.dark-mode .control-panel { background: rgba(30,41,59,0.98); }
        .search-box { flex: 1; min-width: 300px; position: relative; }
        .search-box i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--secondary-gold); font-size: 18px; }
        .search-input {
            width: 100%; padding: 15px 20px 15px 50px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 15px; font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease; background: #f8fafc;
        }
        body.dark-mode .search-input { background: #1e293b; border-color: #475569; color: #e2e8f0; }
        .search-input:focus { outline: none; border-color: var(--secondary-gold); }
        .filter-select, .sort-select {
            padding: 15px 20px; border: 2px solid #e2e8f0;
            border-radius: 12px; font-size: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc; cursor: pointer; min-width: 150px;
        }
        body.dark-mode .filter-select, body.dark-mode .sort-select { background: #1e293b; border-color: #475569; color: #e2e8f0; }
        .btn-add {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: #0f172a; border: none; padding: 15px 30px;
            border-radius: 12px; font-size: 15px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 10px;
            transition: all 0.3s ease; font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn-add:hover { transform: translateY(-2px); }
        /* Table Section */
        .table-section {
            background: rgba(255,255,255,0.98); border-radius: 30px;
            padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            border: 2px solid var(--primary-gold); transition: all 0.3s ease;
        }
        body.dark-mode .table-section { background: rgba(30,41,59,0.98); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .section-title { font-family: 'Outfit', sans-serif; font-size: 26px; color: #0f172a; display: flex; align-items: center; gap: 12px; font-weight: 700; }
        body.dark-mode .section-title { color: #f1f5f9; }
        .section-title i { color: var(--secondary-gold); font-size: 30px; }
        .result-count { background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue)); color: white; padding: 8px 20px; border-radius: 50px; font-size: 14px; font-weight: 600; border: 2px solid var(--primary-gold); }
        /* Book Table */
        .book-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .book-table thead th {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue));
            color: white; padding: 18px 20px; text-align: left;
            font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
        }
        .book-table thead th:first-child { border-radius: 15px 0 0 15px; width: 80px; text-align: center; }
        .book-table thead th:last-child { border-radius: 0 15px 15px 0; }
        .book-row { background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s ease; border-radius: 15px; }
        body.dark-mode .book-row { background: #1e293b; }
        .book-row:hover { transform: translateY(-3px) scale(1.01); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.15); border: 2px solid var(--primary-gold); }
        .book-row td { padding: 20px; border: none; vertical-align: middle; }
        .book-row td:first-child { border-radius: 15px 0 0 15px; }
        .book-row td:last-child { border-radius: 0 15px 15px 0; }
        .number-badge {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: #0f172a; width: 42px; height: 42px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px; margin: 0 auto;
        }
        .book-title-cell { display: flex; align-items: center; gap: 20px; }
        
        /* ============================================
           STYLING COVER BUKU - DI PERBAIKI
           ============================================ */
        .book-cover {
            width: 75px; 
            height: 100px; 
            object-fit: cover; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .book-cover:hover {
            transform: scale(1.08);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .book-cover.has-image {
            border: 3px solid var(--primary-gold);
        }
        
        .book-info h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
        body.dark-mode .book-info h3 { color: #f1f5f9; }
        .book-info p { font-size: 13px; color: #64748b; }
        body.dark-mode .book-info p { color: #94a3b8; }
        .year-badge { background: #eff6ff; color: var(--secondary-blue); padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; border: 2px solid #bfdbfe; display: inline-block; }
        body.dark-mode .year-badge { background: #1e3a8a; color: #93c5fd; }
        .category-badge { padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 600; border: 2px solid; display: inline-block; }
        .action-buttons { display: flex; gap: 10px; }
        .btn-action {
            width: 42px; height: 42px; border-radius: 10px;
            border: none; cursor: pointer; display: flex;
            align-items: center; justify-content: center; font-size: 16px;
            transition: all 0.3s ease; text-decoration: none;
        }
        .btn-edit { background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue)); color: white; }
        .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .btn-action:hover { transform: scale(1.1); }
        /* No Result */
        .no-result { text-align: center; padding: 60px; color: #64748b; display: none; }
        .no-result i { font-size: 60px; color: var(--primary-gold); margin-bottom: 20px; display: block; }
        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); z-index: 1000;
            align-items: center; justify-content: center;
            backdrop-filter: blur(8px);
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white; border-radius: 25px; padding: 40px;
            max-width: 550px; width: 90%; max-height: 90vh;
            overflow-y: auto; border: 3px solid var(--primary-gold);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        body.dark-mode .modal-content { background: #1e293b; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .modal-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        body.dark-mode .modal-title { color: #f1f5f9; }
        .modal-title i { color: var(--secondary-gold); }
        .btn-close { background: #f1f5f9; border: none; width: 40px; height: 40px; border-radius: 10px; cursor: pointer; font-size: 18px; color: #64748b; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
        body.dark-mode .form-label { color: #d1d5db; }
        .form-label span { color: #ef4444; }
        .form-input, .form-select {
            width: 100%; padding: 13px 18px; border: 2px solid #e5e7eb;
            border-radius: 12px; font-size: 15px; font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease; background: #f9fafb;
        }
        body.dark-mode .form-input, body.dark-mode .form-select { background: #374151; border-color: #4b5563; color: #f9fafb; }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--secondary-gold); }
        .cover-input {
            padding: 20px;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        .cover-input:hover {
            border-color: var(--secondary-gold);
            background: #f0fdf4;
        }
        body.dark-mode .cover-input {
            background: #374151;
            border-color: #4b5563;
        }
        body.dark-mode .cover-input:hover {
            background: #1f2937;
            border-color: var(--secondary-gold);
        }
        .cover-upload-wrapper small {
            font-size: 13px !important;
        }
        body.dark-mode .cover-upload-wrapper small {
            color: #cbd5e1 !important;
        }
        .form-actions { display: flex; gap: 15px; margin-top: 30px; justify-content: flex-end; }
        .btn-primary { background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue)); color: white; border: none; padding: 13px 30px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 15px; display: flex; align-items: center; gap: 8px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .btn-secondary { background: #f1f5f9; color: #374151; border: 2px solid #e5e7eb; padding: 13px 30px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 15px; display: flex; align-items: center; gap: 8px; font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Toast */
        .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast { background: white; border-radius: 15px; padding: 15px 25px; display: flex; align-items: center; gap: 12px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border-left: 5px solid; animation: slideIn 0.3s ease; min-width: 250px; }
        .toast.success { border-color: #10b981; color: #065f46; }
        .toast.error { border-color: #ef4444; color: #991b1b; }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(100px); } to { opacity: 1; transform: translateX(0); } }
        /* Footer */
        .footer-card {
            margin-top: 40px; text-align: center; padding: 40px;
            background: rgba(255,255,255,0.1); border-radius: 25px;
            backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.2);
        }
        .footer-title { font-family: 'Outfit', sans-serif; font-size: 24px; color: white; font-weight: 700; margin-bottom: 15px; }
        .portal-btn {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: #0f172a; padding: 15px 40px; border-radius: 50px;
            text-decoration: none; font-weight: 700; font-size: 16px;
            transition: all 0.3s ease; margin-top: 10px;
        }
        .portal-btn:hover { transform: translateY(-3px); }
        /* Delete Modal */
        .delete-icon { font-size: 60px; color: #ef4444; text-align: center; margin-bottom: 20px; }
        .delete-text { text-align: center; color: #64748b; margin-bottom: 10px; }
        
        /* ============================================
           MODAL PREVIEW COVER - DI PERBAIKI
           ============================================ */
        .modal-preview-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(10px);
        }
        .modal-preview-overlay.active {
            display: flex;
        }
        .modal-preview-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            position: relative;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            border: 3px solid var(--primary-gold);
            animation: zoomIn 0.3s ease;
            text-align: center;
        }
        body.dark-mode .modal-preview-content { 
            background: #1e293b; 
        }
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .btn-close-preview {
            position: absolute;
            top: -15px;
            right: -15px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: 3px solid white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .btn-close-preview:hover {
            transform: scale(1.1) rotate(90deg);
        }
        .preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .preview-cover-image {
            width: 100%;
            max-width: 350px;
            height: auto;
            max-height: 500px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            object-fit: contain;
        }
        .preview-info {
            text-align: center;
            width: 100%;
        }
        .preview-info h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }
        body.dark-mode .preview-info h2 { 
            color: #f1f5f9; 
        }
        .preview-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-blue));
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Responsive Preview Modal */
        @media (max-width: 768px) {
            .modal-preview-content {
                padding: 25px 20px;
                max-width: 95%;
            }
            .preview-cover-image { 
                max-width: 300px; 
                max-height: 400px;
            }
            .preview-info h2 { 
                font-size: 18px; 
            }
            .btn-close-preview { 
                width: 40px; 
                height: 40px; 
                font-size: 18px;
                top: -10px;
                right: -10px;
            }
        }
        
        @media (max-width: 480px) {
            .modal-preview-content {
                padding: 20px 15px;
                border-radius: 15px;
            }
            .preview-cover-image { 
                max-width: 250px; 
                max-height: 350px;
            }
            .preview-info h2 { 
                font-size: 16px; 
            }
            .btn-close-preview { 
                width: 36px; 
                height: 36px; 
                font-size: 16px; 
                top: -8px; 
                right: -8px; 
            }
        }
        
        /* RESPONSIVE MEDIA QUERIES */
        @media (max-width: 1024px) {
            .container { max-width: 95%; }
            h1 { font-size: 36px; }
            .header { padding: 30px 25px; margin-bottom: 30px; }
            .stat-item { padding: 15px 25px; min-width: 120px; }
            .stat-number { font-size: 28px; }
            .book-table thead th { padding: 14px 12px; font-size: 12px; }
            .book-row td { padding: 15px 12px; }
            .book-cover { width: 60px; height: 80px; }
            .control-panel { gap: 15px; padding: 20px 25px; }
            .settings-panel { gap: 8px; }
        }
        
        @media (max-width: 768px) {
            body { padding: 25px 15px; }
            .container { max-width: 100%; }
            h1 { font-size: 28px; }
            .subtitle { font-size: 14px; }
            .university-badge { padding: 6px 15px; font-size: 11px; }
            .header { padding: 25px 20px; margin-bottom: 25px; border-radius: 20px; }
            .settings-panel { position: relative; top: auto; right: auto; justify-content: center; margin-bottom: 20px; max-width: 100%; flex-wrap: wrap; gap: 8px; }
            .toggle-btn, .action-btn { padding: 10px 15px; font-size: 12px; }
            .stats-bar { gap: 20px; }
            .stat-item { padding: 12px 20px; min-width: 100px; }
            .stat-number { font-size: 24px; }
            .stat-label { font-size: 11px; }
            .control-panel { flex-direction: column; gap: 12px; padding: 18px 20px; }
            .search-box { min-width: 100%; }
            .filter-select, .sort-select { min-width: 100%; }
            .btn-add { width: 100%; justify-content: center; }
            .table-section { padding: 25px 20px; }
            .section-title { font-size: 20px; }
            .book-table thead th { padding: 12px 8px; font-size: 11px; }
            .book-row td { padding: 12px 8px; }
            .book-cover { width: 50px; height: 70px; }
            .book-info h3 { font-size: 14px; }
            .book-info p { font-size: 12px; }
            .action-buttons { gap: 8px; }
            .btn-action { width: 36px; height: 36px; font-size: 14px; }
            .modal-content { padding: 30px 20px; max-width: 95%; }
            .modal-title { font-size: 18px; }
            .form-input, .form-select { padding: 11px 15px; font-size: 14px; }
            .form-label { font-size: 13px; }
            .btn-primary, .btn-secondary { padding: 11px 20px; font-size: 14px; }
            .toast { padding: 12px 20px; min-width: auto; max-width: 90vw; }
            .footer-card { padding: 25px 20px; }
            .footer-title { font-size: 18px; }
            .portal-btn { font-size: 14px; padding: 12px 30px; }
        }
        
        @media (max-width: 600px) {
            body { padding: 20px 12px; }
            h1 { font-size: 24px; margin-bottom: 8px; }
            .subtitle { font-size: 13px; margin-bottom: 20px; }
            .university-badge { padding: 5px 12px; font-size: 10px; letter-spacing: 1px; }
            .header { padding: 20px 18px; margin-bottom: 20px; border-radius: 18px; }
            .settings-panel { gap: 6px; margin-bottom: 15px; }
            .toggle-btn, .action-btn { padding: 9px 12px; font-size: 11px; }
            .stats-bar { gap: 15px; }
            .stat-item { padding: 10px 18px; min-width: 90px; border: 2px solid var(--primary-gold); }
            .stat-number { font-size: 22px; }
            .stat-label { font-size: 10px; margin-top: 3px; }
            .control-panel { gap: 10px; padding: 15px 18px; }
            .search-box { min-width: 100%; }
            .search-box i { left: 15px; font-size: 16px; }
            .search-input { padding: 12px 15px 12px 40px; font-size: 14px; }
            .filter-select, .sort-select { min-width: 100%; padding: 12px 15px; font-size: 13px; }
            .btn-add { width: 100%; padding: 12px 20px; font-size: 14px; }
            .table-section { padding: 20px 15px; border-radius: 20px; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .section-title { font-size: 18px; }
            .result-count { font-size: 13px; padding: 6px 12px; }
            .book-table { border-spacing: 0 10px; }
            .book-table thead th { padding: 10px 6px; font-size: 10px; }
            .book-table thead th:first-child { width: 50px; }
            .book-row td { padding: 10px 6px; font-size: 13px; }
            .book-cover { width: 45px; height: 65px; }
            .book-info h3 { font-size: 13px; margin-bottom: 3px; }
            .book-info p { font-size: 11px; margin: 2px 0; }
            .number-badge { width: 38px; height: 38px; font-size: 14px; }
            .year-badge { padding: 5px 12px; font-size: 12px; }
            .category-badge { padding: 4px 12px; font-size: 11px; }
            .action-buttons { gap: 6px; }
            .btn-action { width: 32px; height: 32px; font-size: 13px; }
            .modal-overlay { padding: 20px 0; }
            .modal-content { padding: 25px 18px; max-width: 100%; }
            .modal-header { margin-bottom: 20px; }
            .modal-title { font-size: 16px; gap: 8px; }
            .btn-close { width: 36px; height: 36px; font-size: 16px; }
            .form-group { margin-bottom: 15px; }
            .form-label { font-size: 12px; margin-bottom: 6px; }
            .form-input, .form-select { padding: 10px 14px; font-size: 14px; border-radius: 10px; }
            .cover-input { padding: 15px; border-radius: 10px; }
            .cover-upload-wrapper small { font-size: 12px !important; margin-top: 6px; }
            .form-actions { flex-direction: column; gap: 10px; margin-top: 20px; }
            .btn-primary, .btn-secondary { width: 100%; padding: 11px 16px; font-size: 13px; justify-content: center; }
            .toast-container { bottom: 20px; right: 15px; gap: 8px; }
            .toast { padding: 11px 15px; min-width: auto; max-width: calc(100vw - 40px); font-size: 13px; }
            .toast i { font-size: 16px; }
            .footer-card { margin-top: 30px; padding: 20px 15px; border-radius: 20px; }
            .footer-title { font-size: 16px; margin-bottom: 10px; }
            .portal-btn { font-size: 13px; padding: 11px 25px; }
        }
        
        @media (max-width: 480px) {
            body { padding: 15px 10px; }
            h1 { font-size: 20px; }
            .subtitle { font-size: 12px; margin-bottom: 15px; }
            .university-badge { padding: 4px 10px; font-size: 9px; }
            .header { padding: 15px 15px; margin-bottom: 15px; border-radius: 15px; }
            .settings-panel { gap: 5px; margin-bottom: 12px; }
            .toggle-btn, .action-btn { padding: 8px 10px; font-size: 10px; flex: 1; }
            .stats-bar { gap: 10px; }
            .stat-item { padding: 8px 12px; min-width: 70px; }
            .stat-number { font-size: 18px; }
            .stat-label { font-size: 9px; }
            .control-panel { gap: 8px; padding: 12px 15px; }
            .search-input { padding: 10px 12px 10px 35px; font-size: 13px; }
            .search-box i { left: 12px; font-size: 14px; }
            .filter-select, .sort-select { padding: 10px 12px; font-size: 12px; }
            .btn-add { padding: 10px 15px; font-size: 13px; }
            .table-section { padding: 15px 12px; }
            .section-title { font-size: 16px; }
            .book-table thead th { padding: 8px 5px; font-size: 9px; }
            .book-table thead th:first-child { width: 45px; }
            .book-row td { padding: 8px 5px; font-size: 12px; }
            .book-cover { width: 40px; height: 60px; }
            .book-info h3 { font-size: 12px; }
            .book-info p { font-size: 10px; }
            .number-badge { width: 35px; height: 35px; font-size: 12px; }
            .year-badge { padding: 4px 10px; font-size: 11px; }
            .category-badge { padding: 3px 10px; font-size: 10px; }
            .action-buttons { gap: 5px; }
            .btn-action { width: 30px; height: 30px; font-size: 12px; }
            .modal-content { padding: 20px 15px; }
            .modal-title { font-size: 15px; }
            .btn-close { width: 32px; height: 32px; font-size: 14px; }
            .form-label { font-size: 11px; }
            .form-input, .form-select { padding: 9px 12px; font-size: 13px; }
            .cover-input { padding: 12px; }
            .btn-primary, .btn-secondary { padding: 10px 14px; font-size: 12px; }
            .toast { padding: 10px 12px; font-size: 12px; min-width: auto; }
            .no-result { padding: 40px 15px; }
            .no-result i { font-size: 48px; }
            .footer-card { margin-top: 25px; padding: 15px 12px; }
            .footer-title { font-size: 14px; }
            .portal-btn { font-size: 12px; padding: 10px 20px; }
        }
        
        @media (min-width: 1920px) {
            .container { max-width: 1600px; }
            h1 { font-size: 56px; }
            .book-cover { width: 90px; height: 120px; }
            .stat-number { font-size: 44px; }
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Settings Panel -->
    <div class="settings-panel">
        <button class="toggle-btn" id="darkModeToggle" onclick="toggleDarkMode()">
            <i class="fas fa-moon"></i>
            <span>Dark Mode</span>
        </button>
    </div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="university-badge">UIN Antasari Banjarbaru</div>
            <h1>Perpustakaan <span class="highlight">Digital</span></h1>
            <p class="subtitle">Selamat datang! Platform ini dirancang untuk memberikan akses ke berbagai literatur dan informasi secara cepat dan efisien.</p>
           
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalBuku; ?></div>
                    <div class="stat-label">Koleksi Buku</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Jurnal Digital</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalBuku; ?></div>
                    <div class="stat-label">Akses Online</div>
                </div>
            </div>
        </div>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="searchInput"
                       placeholder="Cari berdasarkan judul, pengarang, atau penerbit..."
                       onkeyup="filterTable()">
            </div>
            <select class="filter-select" id="filterSelect" onchange="filterTable()">
                <option value="all">Semua Penerbit</option>
                <?php
                $penerbit_query = mysqli_query($conn, "SELECT DISTINCT penerbit FROM databuku ORDER BY penerbit");
                while ($p = mysqli_fetch_assoc($penerbit_query)) {
                    echo "<option value='{$p['penerbit']}'>{$p['penerbit']}</option>";
                }
                ?>
            </select>
            <select class="sort-select" id="sortSelect" onchange="filterTable()">
                <option value="default">Urutkan...</option>
                <option value="judul-asc">Judul (A-Z)</option>
                <option value="judul-desc">Judul (Z-A)</option>
                <option value="tahun-desc">Tahun (Terbaru)</option>
                <option value="tahun-asc">Tahun (Terlama)</option>
            </select>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Tambah Koleksi
            </button>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-book-open"></i>
                    Katalog Buku Kami
                </h2>
                <span class="result-count" id="resultCount">Menampilkan <?php echo $totalBuku; ?> buku</span>
            </div>
           
            <table class="book-table" id="bookTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Detail Buku</th>
                        <th>Tahun</th>
                        <th>Penerbit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="bookTableBody">
                    <?php
                    $no = 1;
                    foreach ($books as $book):
                        $cover = getCoverUrl($book['cover_buku']);
                        $hasCustomCover = !empty($book['cover_buku']) && file_exists($uploadDir . '/' . $book['cover_buku']);
                    ?>
                    <tr class="book-row"
                        data-judul="<?php echo strtolower($book['judul_buku']); ?>"
                        data-pengarang="<?php echo strtolower($book['pengarang']); ?>"
                        data-penerbit="<?php echo strtolower($book['penerbit']); ?>"
                        data-tahun="<?php echo $book['tahun_terbit']; ?>">
                        <td>
                            <div class="number-badge"><?php echo $no++; ?></div>
                        </td>
                        <td>
                            <div class="book-title-cell">
                                <img src="<?php echo $cover; ?>" 
                                     alt="Cover <?php echo htmlspecialchars($book['judul_buku']); ?>" 
                                     class="book-cover <?php echo $hasCustomCover ? 'has-image' : ''; ?>"
                                     onclick="openCoverPreview('<?php echo htmlspecialchars($cover); ?>', '<?php echo htmlspecialchars(addslashes($book['judul_buku'])); ?>')"
                                     title="Klik untuk melihat cover lebih jelas">
                                <div class="book-info">
                                    <h3><?php echo htmlspecialchars($book['judul_buku']); ?></h3>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($book['pengarang']); ?></p>
                                    <p><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['kode_buku']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="year-badge"><?php echo htmlspecialchars($book['tahun_terbit']); ?></span>
                        </td>
                        <td>
                            <span class="category-badge" style="background:#eff6ff; color:#1e40af; border-color:#bfdbfe;">
                                <?php echo htmlspecialchars($book['penerbit']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($book)); ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="confirmHapus('<?php echo $book['kode_buku']; ?>', '<?php echo addslashes($book['judul_buku']); ?>')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
           
            <div class="no-result" id="noResult">
                <i class="fas fa-search"></i>
                <p>Tidak ada buku yang ditemukan sesuai pencarian Anda.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-card">
            <h2 class="footer-title"><i class="fas fa-code"></i> Oleh Akhmad Da'wah</h2>
            <p style="margin-bottom:10px; opacity:0.9; color:white;">Informasi lebih lanjut? Kunjungi profil saya</p>
            <a href="https://github.com/DawahAhn" target="_blank" class="portal-btn">
                <i class="fab fa-github"></i>
                Kunjungi Portal Utama
            </a>
        </div>
    </div>
    
    <!-- Modal Tambah Buku -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">
                    <i class="fas fa-book-medical"></i> Tambah Koleksi Baru
                </h3>
                <button class="btn-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
           
            <form id="addBookForm" method="POST" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="old_kode" id="oldKode" value="">
                <div class="form-group">
                    <label class="form-label">Kode Buku <span>*</span></label>
                    <input type="text" class="form-input" name="kode_buku" id="inputKode" placeholder="Contoh: BK001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Judul Buku <span>*</span></label>
                    <input type="text" class="form-input" name="judul_buku" id="inputJudul" placeholder="Masukkan judul buku" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pengarang <span>*</span></label>
                    <input type="text" class="form-input" name="pengarang" id="inputPengarang" placeholder="Masukkan nama pengarang" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Penerbit <span>*</span></label>
                    <input type="text" class="form-input" name="penerbit" id="inputPenerbit" placeholder="Masukkan nama penerbit" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tahun Terbit <span>*</span></label>
                    <input type="number" class="form-input" name="tahun_terbit" id="inputTahun" placeholder="Contoh: 2024" min="1900" max="2099" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Buku (Foto)</label>
                    <div class="cover-upload-wrapper">
                        <input type="file" class="form-input cover-input" name="cover_buku" id="coverInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="previewCover(event)">
                        <small style="display:block; margin-top:8px; color:#64748b;">
                            <i class="fas fa-info-circle"></i> Format: JPG, PNG, GIF, WEBP | Ukuran maks: 5MB
                        </small>
                        <div id="coverPreview" style="margin-top:15px; display:none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width:100%; max-height:200px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times-circle"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Hapus -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content" style="max-width:400px; text-align:center;">
            <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="modal-title" style="justify-content:center; margin-bottom:15px;">Hapus Buku?</h3>
            <p class="delete-text">Apakah Anda yakin ingin menghapus buku ini?<br>
                <strong id="deleteBookTitle"></strong>
            </p>
            <div class="form-actions" style="justify-content:center; margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <a id="deleteLink" href="#" class="btn-primary" style="background:linear-gradient(135deg,#ef4444,#dc2626); text-decoration:none;">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            </div>
        </div>
    </div>
    
    <!-- ============================================
         MODAL PREVIEW COVER - DI PERBAIKI
         ============================================ -->
    <div class="modal-preview-overlay" id="previewModal">
        <div class="modal-preview-content">
            <button class="btn-close-preview" onclick="closeCoverPreview()">
                <i class="fas fa-times"></i>
            </button>
            <div class="preview-container">
                <img id="previewCoverImage" src="" alt="Cover Preview" class="preview-cover-image">
                <div class="preview-info">
                    <h2 id="previewCoverTitle">Judul Buku</h2>
                    <span class="preview-badge"><i class="fas fa-image"></i> Cover Buku</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ============================================
        // PREVIEW COVER IMAGE - FORM INPUT
        // ============================================
        function previewCover(event) {
            const file = event.target.files[0];
            const previewDiv = document.getElementById('coverPreview');
            const previewImg = document.getElementById('previewImg');
            
            if (file && file.type.startsWith('image/')) {
                // Validasi ukuran (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB.');
                    event.target.value = '';
                    previewDiv.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewDiv.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        }
        
        // ============================================
        // OPEN COVER PREVIEW MODAL
        // ============================================
        function openCoverPreview(coverSrc, bookTitle) {
            const modal = document.getElementById('previewModal');
            const img = document.getElementById('previewCoverImage');
            const title = document.getElementById('previewCoverTitle');
            
            img.src = coverSrc;
            title.textContent = bookTitle || 'Cover Buku';
            modal.classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        // ============================================
        // CLOSE COVER PREVIEW MODAL
        // ============================================
        function closeCoverPreview() {
            const modal = document.getElementById('previewModal');
            modal.classList.remove('active');
            
            // Reset src untuk mencegah flash image lama
            setTimeout(() => {
                document.getElementById('previewCoverImage').src = '';
            }, 300);
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
        
        // Close preview modal when clicking outside
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) closeCoverPreview();
        });
        
        // ============================================
        // DARK MODE
        // ============================================
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const btn = document.getElementById('darkModeToggle');
            if (document.body.classList.contains('dark-mode')) {
                btn.innerHTML = '<i class="fas fa-sun"></i><span>Light Mode</span>';
            } else {
                btn.innerHTML = '<i class="fas fa-moon"></i><span>Dark Mode</span>';
            }
        }
        
        // ============================================
        // FILTER & SEARCH
        // ============================================
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const filter = document.getElementById('filterSelect').value.toLowerCase();
            const sort   = document.getElementById('sortSelect').value;
            const rows   = Array.from(document.querySelectorAll('#bookTableBody .book-row'));
            let visible = 0;
            
            rows.forEach(row => {
                const judul     = row.dataset.judul || '';
                const pengarang = row.dataset.pengarang || '';
                const penerbit  = row.dataset.penerbit || '';
                const matchSearch = judul.includes(search) || pengarang.includes(search) || penerbit.includes(search);
                const matchFilter = filter === 'all' || penerbit === filter;
                
                if (matchSearch && matchFilter) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Sort
            const tbody = document.getElementById('bookTableBody');
            const sortedRows = rows.filter(r => r.style.display !== 'none').sort((a, b) => {
                if (sort === 'judul-asc')  return a.dataset.judul.localeCompare(b.dataset.judul);
                if (sort === 'judul-desc') return b.dataset.judul.localeCompare(a.dataset.judul);
                if (sort === 'tahun-desc') return parseInt(b.dataset.tahun) - parseInt(a.dataset.tahun);
                if (sort === 'tahun-asc')  return parseInt(a.dataset.tahun) - parseInt(b.dataset.tahun);
                return 0;
            });
            sortedRows.forEach(r => tbody.appendChild(r));
            
            // Update nomor urut
            let no = 1;
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('.number-badge').textContent = no++;
                }
            });
            
            document.getElementById('resultCount').textContent = `Menampilkan ${visible} buku`;
            document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
        }
        
        // ============================================
        // OPEN ADD MODAL
        // ============================================
        function openModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-book-medical"></i> Tambah Koleksi Baru';
            document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('oldKode').value = '';
            document.getElementById('addBookForm').reset();
            document.getElementById('coverInput').value = '';
            document.getElementById('coverPreview').style.display = 'none';
            document.getElementById('addModal').classList.add('active');
        }
        
        // ============================================
        // OPEN EDIT MODAL
        // ============================================
        function openEditModal(book) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Koleksi';
            document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Update';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('oldKode').value = book.kode_buku;
            document.getElementById('inputKode').value = book.kode_buku;
            document.getElementById('inputJudul').value = book.judul_buku;
            document.getElementById('inputPengarang').value = book.pengarang;
            document.getElementById('inputPenerbit').value = book.penerbit;
            document.getElementById('inputTahun').value = book.tahun_terbit;
            
            // Reset file input dan preview
            document.getElementById('coverInput').value = '';
            document.getElementById('coverPreview').style.display = 'none';
            
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        // ============================================
        // DELETE CONFIRMATION
        // ============================================
        function confirmHapus(kode, judul) {
            document.getElementById('deleteBookTitle').textContent = judul;
            document.getElementById('deleteLink').href = 'index.php?hapus=' + kode;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        
        // Keyboard event - Close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeDeleteModal();
                closeCoverPreview();
            }
        });
        
        // ============================================
        // TOAST NOTIFICATION
        // ============================================
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            toast.innerHTML = `<i class="fas fa-${icon}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { container.removeChild(toast); }, 3000);
        }
        
        // Auto show toast from PHP redirect
        <?php if (isset($_GET['toast'])): ?>
            <?php if ($_GET['toast'] === 'tambah'): ?>
                window.onload = () => showToast('Buku berhasil ditambahkan!', 'success');
            <?php elseif ($_GET['toast'] === 'edit'): ?>
                window.onload = () => showToast('Buku berhasil diupdate!', 'success');
            <?php elseif ($_GET['toast'] === 'hapus'): ?>
                window.onload = () => showToast('Buku berhasil dihapus!', 'error');
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>