<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';
if (!$kode) {
    http_response_code(400);
    echo 'Kode transaksi tidak valid.';
    exit;
}

$pdo = getDB();

// Ambil header transaksi
$stmt = $pdo->prepare("
    SELECT t.*, u.nama AS nama_kasir
    FROM transaksi t
    JOIN users u ON u.id = t.user_id
    WHERE t.kode = ?
");
$stmt->execute([$kode]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    http_response_code(404);
    echo 'Transaksi tidak ditemukan.';
    exit;
}

// Ambil detail item
$stmtDetail = $pdo->prepare("
    SELECT * FROM detail_transaksi WHERE transaksi_id = ?
");
$stmtDetail->execute([$trx['id']]);
$items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$fmt = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nota <?= htmlspecialchars($kode) ?></title>
  <style>
    /* ── SCREEN WRAPPER ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: system-ui, sans-serif;
      background: #f5f5f0;
      color: #1a1a18;
      font-size: 14px;
      min-height: 100vh;
    }

    /* NAVBAR — hanya tampil di layar, disembunyikan saat print */
    .navbar {
      background: #fff;
      border-bottom: 0.5px solid #ddddd5;
      padding: 0 1.5rem;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .navbar-brand { font-weight: 500; font-size: 15px; }
    .navbar-actions { display: flex; gap: 8px; }

    .btn {
      padding: 7px 16px; font-size: 13px; border-radius: 8px;
      cursor: pointer; text-decoration: none; display: inline-flex;
      align-items: center; gap: 6px;
    }
    .btn-back {
      border: 0.5px solid #ddddd5; background: #fff; color: #5f5e5a;
    }
    .btn-back:hover { background: #f5f5f0; }
    .btn-print {
      border: none; background: #1D9E75; color: #E1F5EE; font-weight: 500;
    }
    .btn-print:hover { opacity: 0.88; }

    /* HALAMAN NOTA */
    .page-wrap {
      display: flex;
      justify-content: center;
      padding: 24px 16px;
    }

    /* NOTA CARD */
    .nota {
      background: #fff;
      border: 0.5px solid #ddddd5;
      border-radius: 12px;
      width: 100%;
      max-width: 380px;
      padding: 24px 20px;
    }

    /* HEADER TOKO */
    .nota-header { text-align: center; margin-bottom: 16px; }
    .nota-toko { font-size: 17px; font-weight: 600; letter-spacing: 0.3px; }
    .nota-sub { font-size: 12px; color: #888780; margin-top: 2px; }

    .divider-dash {
      border: none;
      border-top: 1px dashed #ddddd5;
      margin: 12px 0;
    }
    .divider-solid {
      border: none;
      border-top: 0.5px solid #ddddd5;
      margin: 12px 0;
    }

    /* INFO TRANSAKSI */
    .nota-info { display: flex; flex-direction: column; gap: 4px; margin-bottom: 4px; }
    .info-row { display: flex; justify-content: space-between; font-size: 12px; }
    .info-label { color: #888780; }
    .info-val { font-weight: 500; text-align: right; }

    /* TABEL ITEM */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .items-table th {
      text-align: left; font-size: 11px; font-weight: 500;
      color: #888780; padding: 4px 0;
    }
    .items-table th:last-child,
    .items-table td:last-child { text-align: right; }
    .items-table td {
      font-size: 13px; padding: 5px 0;
      border-bottom: 0.5px solid #f0f0ea;
      vertical-align: top;
    }
    .items-table tr:last-child td { border-bottom: none; }
    .item-nama { font-weight: 500; }
    .item-detail { font-size: 11px; color: #888780; margin-top: 1px; }

    /* SUBTOTAL ROWS */
    .total-section { display: flex; flex-direction: column; gap: 5px; }
    .total-row { display: flex; justify-content: space-between; font-size: 13px; }
    .total-row.grand {
      font-size: 15px; font-weight: 600;
    }
    .total-row.kembalian { color: #0F6E56; }

    /* FOOTER */
    .nota-footer {
      text-align: center;
      font-size: 12px;
      color: #b4b2a9;
      margin-top: 4px;
      line-height: 1.6;
    }
    .nota-footer strong {
      display: block;
      font-size: 13px;
      color: #5f5e5a;
      margin-bottom: 2px;
    }

    /* ── PRINT STYLES ── */
    @media print {
      body { background: #fff; }
      .navbar { display: none; }
      .page-wrap { padding: 0; }
      .nota {
        border: none;
        border-radius: 0;
        max-width: 100%;
        width: 100%;
        padding: 8px 4px;
        box-shadow: none;
      }
      .nota-toko { font-size: 16px; }
      @page {
        size: 80mm auto;
        margin: 6mm 4mm;
      }
    }
  </style>
</head>
<body>

<!-- NAVBAR (hanya layar) -->
<nav class="navbar">
  <div class="navbar-brand">Nota Transaksi</div>
  <div class="navbar-actions">
    <a href="laporan.php" class="btn btn-back">← Kembali</a>
    <button class="btn btn-print" onclick="window.print()">🖨️ Cetak Nota</button>
  </div>
</nav>

<!-- NOTA -->
<div class="page-wrap">
  <div class="nota">

    <!-- HEADER TOKO -->
    <div class="nota-header">
      <div class="nota-toko">Toko Sembako Mujiati</div>
      <div class="nota-sub">Pasar Baru Tuban</div>
    </div>

    <hr class="divider-dash">

    <!-- INFO TRANSAKSI -->
    <div class="nota-info">
      <div class="info-row">
        <span class="info-label">No. Transaksi</span>
        <span class="info-val"><?= htmlspecialchars($trx['kode']) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Tanggal</span>
        <span class="info-val">
          <?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?>
        </span>
      </div>
      <div class="info-row">
        <span class="info-label">Kasir</span>
        <span class="info-val"><?= htmlspecialchars($trx['nama_kasir']) ?></span>
      </div>
    </div>

    <hr class="divider-dash">

    <!-- DAFTAR ITEM -->
    <table class="items-table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Qty</th>
          <th>Harga</th>
          <th>Subtotal</th>
        </tr>
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

    <!-- TOTAL, BAYAR, KEMBALIAN -->
    <div class="total-section">
      <div class="total-row">
        <span>Total</span>
        <span><?= $fmt($trx['total']) ?></span>
      </div>
      <div class="total-row">
        <span>Tunai</span>
        <span><?= $fmt($trx['bayar']) ?></span>
      </div>
      <div class="total-row grand">
        <span>Kembalian</span>
        <span><?= $fmt($trx['kembalian']) ?></span>
      </div>
    </div>

    <hr class="divider-dash">

    <!-- FOOTER -->
    <div class="nota-footer">
      <strong>Terima kasih atas pembelian Anda!</strong>
      Barang yang sudah dibeli tidak dapat dikembalikan.<br>
      Simpan nota ini sebagai bukti pembelian.
    </div>

  </div>
</div>

</body>
</html>
