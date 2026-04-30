<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireAdmin();

$pdo = getDB();

// Detail satu transaksi
if (isset($_GET['detail'])) {
    $id  = (int)$_GET['detail'];
    $trx = $pdo->prepare("SELECT t.*, u.nama AS kasir FROM transaksi t JOIN users u ON u.id = t.user_id WHERE t.id = ?");
    $trx->execute([$id]);
    $data = $trx->fetch();
    if (!$data) { echo json_encode(['error' => 'Tidak ditemukan']); exit; }

    $items = $pdo->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id = ?");
    $items->execute([$id]);
    $data['items'] = $items->fetchAll();

    echo json_encode($data);
    exit;
}

// Laporan rentang tanggal
$dari   = $_GET['dari']   ?? date('Y-m-d');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Ringkasan
$stmtR = $pdo->prepare("
    SELECT
        COUNT(*)            AS total_transaksi,
        COALESCE(SUM(total), 0) AS total_pendapatan
    FROM transaksi
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmtR->execute([$dari, $sampai]);
$ringkasan = $stmtR->fetch();

// Hitung modal dari detail transaksi (harga_beli × qty × konversi)
$stmtM = $pdo->prepare("
    SELECT COALESCE(SUM(ps.harga_beli * dt.qty), 0) AS total_modal
    FROM detail_transaksi dt
    JOIN transaksi t ON t.id = dt.transaksi_id
    JOIN produk_satuan ps ON ps.id = dt.produk_satuan_id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
");
$stmtM->execute([$dari, $sampai]);
$modal = $stmtM->fetch();
$ringkasan['total_modal'] = $modal['total_modal'];

// Produk terlaris
$stmtP = $pdo->prepare("
    SELECT
        dt.nama_produk,
        dt.nama_satuan,
        SUM(dt.qty * dt.konversi) AS total_qty,
        SUM(dt.subtotal)          AS total_pendapatan
    FROM detail_transaksi dt
    JOIN transaksi t ON t.id = dt.transaksi_id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY dt.nama_produk, dt.nama_satuan
    ORDER BY total_pendapatan DESC
    LIMIT 10
");
$stmtP->execute([$dari, $sampai]);
$terlaris = $stmtP->fetchAll();

// Riwayat transaksi
$stmtT = $pdo->prepare("
    SELECT t.*, u.nama AS kasir
    FROM transaksi t
    JOIN users u ON u.id = t.user_id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    ORDER BY t.created_at DESC
");
$stmtT->execute([$dari, $sampai]);
$transaksi = $stmtT->fetchAll();

echo json_encode([
    'ringkasan'  => $ringkasan,
    'terlaris'   => $terlaris,
    'transaksi'  => $transaksi,
]);