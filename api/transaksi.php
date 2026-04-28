<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['items'])) {
    echo json_encode(['error' => 'Data tidak valid']);
    exit;
}

$pdo   = getDB();
$user  = currentUser();
$kode  = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
$total = (float) $body['total'];
$bayar = (float) $body['bayar'];
$kembalian = $bayar - $total;

try {
    $pdo->beginTransaction();

    // Simpan header transaksi
    $stmt = $pdo->prepare("INSERT INTO transaksi (kode, user_id, total, bayar, kembalian) VALUES (?,?,?,?,?)");
    $stmt->execute([$kode, $user['id'], $total, $bayar, $kembalian]);
    $transaksiId = $pdo->lastInsertId();

    // Simpan detail & kurangi stok
    $stmtDetail = $pdo->prepare("INSERT INTO detail_transaksi
        (transaksi_id, produk_id, produk_satuan_id, nama_produk, nama_satuan, konversi, harga, qty, subtotal)
        VALUES (?,?,?,?,?,?,?,?,?)");

    $stmtStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
    $stmtLog  = $pdo->prepare("INSERT INTO stok_log (produk_id, user_id, jenis, jumlah, keterangan) VALUES (?,?,?,?,?)");

    foreach ($body['items'] as $item) {
        $konversi  = (float) $item['konversi'];
        $qty       = (float) $item['qty'];
        $berkurang = $qty * $konversi;

        $stmtDetail->execute([
            $transaksiId,
            $item['produk_id'],
            $item['satuan_id'],
            $item['nama_produk'],
            $item['nama_satuan'],
            $konversi,
            $item['harga'],
            $qty,
            $item['subtotal'],
        ]);

        $stmtStok->execute([$berkurang, $item['produk_id']]);

        $stmtLog->execute([
            $item['produk_id'],
            $user['id'],
            'keluar',
            $berkurang,
            'Transaksi ' . $kode,
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'kode' => $kode]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Transaksi gagal disimpan: ' . $e->getMessage()]);
}
