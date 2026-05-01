<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';
if (!$kode) { http_response_code(400); echo 'Kode transaksi tidak valid.'; exit; }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT t.*, u.nama AS nama_kasir FROM transaksi t JOIN users u ON u.id = t.user_id WHERE t.kode = ?");
$stmt->execute([$kode]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trx) { http_response_code(404); echo 'Transaksi tidak ditemukan.'; exit; }

$stmtDetail = $pdo->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id = ?");
$stmtDetail->execute([$trx['id']]);
$items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$fmt = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Nota <?= htmlspecialchars($kode) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f5f5f0; color: #1a1a18; font-size: 14px; min-height: 100vh; }

    /* NAVBAR */
    .navbar {
      background: #fff; border-bottom: 0.5px solid #ddddd5;
      padding: 0 1rem; height: 52px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .navbar-brand { font-weight: 500; font-size: 14px; }
    .navbar-actions { display: flex; gap: 8px; }
    .btn {
      padding: 8px 14px; font-size: 13px; border-radius: 8px;
      cursor: pointer; text-decoration: none; display: inline-flex;
      align-items: center; gap: 5px; -webkit-tap-highlight-color: transparent;
    }
    .btn-back { border: 0.5px solid #ddddd5; background: #fff; color: #5f5e5a; }
    .btn-print { border: none; background: #1D9E75; color: #fff; font-weight: 500; }

    /* NOTA WRAP */
    .page-wrap { display: flex; justify-content: center; padding: 16px; }
    .nota {
      background: #fff; border: 0.5px solid #ddddd5;
      border-radius: 12px; width: 100%; max-width: 380px; padding: 20px 18px;
    }

    /* HEADER */
    .nota-header { text-align: center; margin-bottom: 14px; }
    .nota-toko { font-size: 16px; font-weight: 700; letter-spacing: 0.3px; }
    .nota-sub { font-size: 12px; color: #888780; margin-top: 2px; }

    .divider-dash { border: none; border-top: 1px dashed #ddddd5; margin: 10px 0; }
    .divider-solid { border: none; border-top: 0.5px solid #ddddd5; margin: 10px 0; }

    /* INFO */
    .nota-info { display: flex; flex-direction: column; gap: 4px; }
    .info-row { display: flex; justify-content: space-between; font-size: 12px; }
    .info-label { color: #888780; }
    .info-val { font-weight: 500; text-align: right; max-width: 60%; word-break: break-all; }

    /* TABEL ITEM */
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th {
      text-align: left; font-size: 11px; font-weight: 500;
      color: #888780; padding: 4px 0;
    }
    .items-table th:last-child, .items-table td:last-child { text-align: right; }
    .items-table td {
      font-size: 12px; padding: 6px 0;
      border-bottom: 0.5px solid #f0f0ea; vertical-align: top;
    }
    .items-table tr:last-child td { border-bottom: none; }
    .item-nama { font-weight: 500; }
    .item-detail { font-size: 11px; color: #888780; margin-top: 1px; }

    /* TOTAL */
    .total-section { display: flex; flex-direction: column; gap: 6px; }
    .total-row { display: flex; justify-content: space-between; font-size: 13px; }
    .total-row.grand { font-size: 15px; font-weight: 700; }

    /* FOOTER */
    .nota-footer {
      text-align: center; font-size: 12px;
      color: #b4b2a9; margin-top: 4px; line-height: 1.6;
    }
    .nota-footer strong { display: block; font-size: 13px; color: #5f5e5a; margin-bottom: 2px; }

    /* PRINT */
    @media print {
      body { background: #fff; }
      .navbar { display: none; }
      .page-wrap { padding: 0; }
      .nota { border: none; border-radius: 0; max-width: 100%; width: 100%; padding: 6px 2px; box-shadow: none; }
      @page { size: 80mm auto; margin: 5mm 3mm; }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">Nota Transaksi</div>
  <div class="navbar-actions">
    <a href="index.php" class="btn btn-back">← Kembali</a>
    <button class="btn btn-print" onclick="window.print()">🖨️ Cetak</button>
  </div>
</nav>

<div class="page-wrap">
  <div class="nota">

    <div class="nota-header">
      <div class="nota-toko">Toko Sembako Mujiati</div>
      <div class="nota-sub">Pasar Baru Tuban</div>
    </div>

    <hr class="divider-dash">

    <div class="nota-info">
      <div class="info-row">
        <span class="info-label">No. Transaksi</span>
        <span class="info-val"><?= htmlspecialchars($trx['kode']) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Tanggal</span>
        <span class="info-val"><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Kasir</span>
        <span class="info-val"><?= htmlspecialchars($trx['nama_kasir']) ?></span>
      </div>
    </div>

    <hr class="divider-dash">

    <table class="items-table">
      <thead>
        <tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <div class="item-nama"><?= htmlspecialchars($item['nama_produk']) ?></div>
            <div class="item-detail"><?= htmlspecialchars($item['nama_satuan']) ?></div>
          </td>
          <td><?= (int)$item['qty'] ?></td>
          <td><?= $fmt($item['harga']) ?></td>
          <td><?= $fmt($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <hr class="divider-solid">

    <div class="total-section">
      <div class="total-row"><span>Total</span><span><?= $fmt($trx['total']) ?></span></div>
      <div class="total-row"><span>Tunai</span><span><?= $fmt($trx['bayar']) ?></span></div>
      <div class="total-row grand"><span>Kembalian</span><span><?= $fmt($trx['kembalian']) ?></span></div>
    </div>

    <hr class="divider-dash">

    <div class="nota-footer">
      <strong>Terima kasih atas pembelian Anda!</strong>
      Barang yang sudah dibeli tidak dapat dikembalikan.<br>
      Simpan nota ini sebagai bukti pembelian.
    </div>

  </div>
</div>

</body>
</html>
