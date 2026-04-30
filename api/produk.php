<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET
if ($method === 'GET') {
    $produk = $pdo->query("
        SELECT p.id, p.nama, p.stok, p.stok_minimum, p.kategori_id,
               k.nama AS kategori
        FROM produk p
        JOIN kategori k ON k.id = p.kategori_id
        WHERE p.aktif = 1
        ORDER BY k.nama, p.nama
    ")->fetchAll();

    $satuan = $pdo->query("
        SELECT * FROM produk_satuan ORDER BY produk_id, is_default DESC
    ")->fetchAll();

    $map = [];
    foreach ($satuan as $s) $map[$s['produk_id']][] = $s;
    foreach ($produk as &$p) $p['satuan'] = $map[$p['id']] ?? [];

    echo json_encode($produk);
    exit;
}

requireAdmin();
$body = json_decode(file_get_contents('php://input'), true);

// DELETE
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID tidak valid']); exit; }
    $pdo->prepare("UPDATE produk SET aktif = 0 WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// POST — tambah atau edit
if ($method === 'POST') {
    $id      = (int)($body['id'] ?? 0);
    $nama    = trim($body['nama'] ?? '');
    $katId   = (int)($body['kategori_id'] ?? 0);
    $stok    = (float)($body['stok'] ?? 0);
    $stokMin = (float)($body['stok_minimum'] ?? 5);
    $satuan  = $body['satuan'] ?? [];

    if (!$nama || !$katId || !$satuan) {
        echo json_encode(['error' => 'Data tidak lengkap']); exit;
    }

    try {
        $pdo->beginTransaction();

        if ($id) {
            // Update data produk
            $pdo->prepare("UPDATE produk SET nama=?, kategori_id=?, stok=?, stok_minimum=?, updated_at=NOW() WHERE id=?")
                ->execute([$nama, $katId, $stok, $stokMin, $id]);

            // Ambil satuan yang sudah ada
            $existingIds = $pdo->prepare("SELECT id FROM produk_satuan WHERE produk_id = ?");
            $existingIds->execute([$id]);
            $oldIds = array_column($existingIds->fetchAll(), 'id');

            $newIds = array_filter(array_column($satuan, 'id'));

            // Hapus satuan yang dihilangkan user
            $toDelete = array_diff($oldIds, $newIds);
            if ($toDelete) {
                $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
                $pdo->prepare("DELETE FROM produk_satuan WHERE id IN ($placeholders)")
                    ->execute(array_values($toDelete));
            }

            $stmtUpdate = $pdo->prepare("UPDATE produk_satuan SET nama_satuan=?, konversi=?, harga_beli=?, harga_jual=?, is_default=? WHERE id=?");
            $stmtInsert = $pdo->prepare("INSERT INTO produk_satuan (produk_id, nama_satuan, konversi, harga_beli, harga_jual, is_default) VALUES (?,?,?,?,?,?)");

            foreach ($satuan as $i => $s) {
                $isDefault = $i === 0 ? 1 : 0;
                if (!empty($s['id'])) {
                    $stmtUpdate->execute([
                        trim($s['nama_satuan']),
                        (float)$s['konversi'],
                        (float)($s['harga_beli'] ?? 0),
                        (float)$s['harga_jual'],
                        $isDefault,
                        (int)$s['id'],
                    ]);
                } else {
                    $stmtInsert->execute([
                        $id,
                        trim($s['nama_satuan']),
                        (float)$s['konversi'],
                        (float)($s['harga_beli'] ?? 0),
                        (float)$s['harga_jual'],
                        $isDefault,
                    ]);
                }
            }

        } else {
            $cek = $pdo->prepare("SELECT id FROM produk WHERE LOWER(nama) = LOWER(?) AND id != ?");
$cek->execute([$nama, $id ?? 0]);
if ($cek->fetch()) {
    echo json_encode(['error' => 'Produk dengan nama ini sudah ada']);
    exit;
}
            // Insert produk baru
            $pdo->prepare("INSERT INTO produk (kategori_id, nama, stok, stok_minimum) VALUES (?,?,?,?)")
                ->execute([$katId, $nama, $stok, $stokMin]);
            $id = $pdo->lastInsertId();

            $stmtInsert = $pdo->prepare("INSERT INTO produk_satuan (produk_id, nama_satuan, konversi, harga_beli, harga_jual, is_default) VALUES (?,?,?,?,?,?)");
            foreach ($satuan as $i => $s) {
                $stmtInsert->execute([
                    $id,
                    trim($s['nama_satuan']),
                    (float)$s['konversi'],
                    (float)($s['harga_beli'] ?? 0),
                    (float)$s['harga_jual'],
                    $i === 0 ? 1 : 0,
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method tidak diizinkan']);