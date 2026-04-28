<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAdmin();
$user = currentUser();
$pdo  = getDB();

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan — POS Sembako</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f5f5f0; color: #1a1a18; font-size: 14px; }

    .navbar {
      background: #fff; border-bottom: 0.5px solid #ddddd5;
      padding: 0 1.5rem; height: 52px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .navbar-brand { font-weight: 500; font-size: 15px; }
    .navbar-right  { display: flex; align-items: center; gap: 1rem; }
    .nav-link {
      font-size: 13px; color: #5f5e5a; text-decoration: none;
      padding: 5px 12px; border: 0.5px solid #ddddd5; border-radius: 8px;
    }
    .nav-link:hover  { background: #f5f5f0; }
    .nav-link.active { background: #E1F5EE; color: #0F6E56; border-color: #9FE1CB; }

    .container { max-width: 1100px; margin: 0 auto; padding: 16px; }
    .page-title { font-size: 16px; font-weight: 500; margin-bottom: 14px; }

    /* FILTER BAR */
    .filter-bar { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
    .filter-input {
      padding: 7px 12px; font-size: 13px;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fff; color: #1a1a18; outline: none;
    }
    .filter-input:focus { border-color: #888; }
    .btn-filter {
      padding: 7px 16px; font-size: 13px; font-weight: 500;
      border: none; border-radius: 8px;
      background: #1D9E75; color: #E1F5EE; cursor: pointer;
    }
    .btn-filter:hover { opacity: 0.88; }
    .btn-export {
      padding: 7px 14px; font-size: 13px;
      border: 0.5px solid #ddddd5; border-radius: 8px;
      background: #fff; cursor: pointer; color: #1a1a18; margin-left: auto;
    }
    .btn-export:hover { background: #f5f5f0; }

    /* METRIK */
    .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-bottom: 16px; }
    .metric {
      background: #fafaf8; border-radius: 8px; padding: 14px 16px;
    }
    .metric-label { font-size: 12px; color: #888780; margin-bottom: 6px; }
    .metric-val   { font-size: 22px; font-weight: 500; color: #1a1a18; }
    .metric-val.green { color: #0F6E56; }
    .metric-val.amber { color: #633806; }

    /* TABEL TRANSAKSI */
    .section-title { font-size: 13px; font-weight: 500; margin-bottom: 8px; color: #5f5e5a; }
    .table-wrap {
      background: #fff; border: 0.5px solid #ddddd5;
      border-radius: 12px; overflow: hidden; margin-bottom: 16px;
    }
    table { width: 100%; border-collapse: collapse; }
    thead th {
      padding: 9px 14px; text-align: left;
      font-size: 12px; font-weight: 500; color: #5f5e5a;
      background: #fafaf8; border-bottom: 0.5px solid #ddddd5;
    }
    tbody td { padding: 9px 14px; border-bottom: 0.5px solid #f0f0ea; font-size: 13px; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #fafaf8; }
    .empty-row td { text-align: center; color: #b4b2a9; padding: 2rem; }

    .btn-detail {
      font-size: 12px; padding: 3px 10px;
      border: 0.5px solid #ddddd5; border-radius: 6px;
      background: none; cursor: pointer; color: #1a1a18;
    }
    .btn-detail:hover { background: #f0f0ea; }

    /* TABEL PRODUK TERLARIS */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
    @media (max-width: 680px) { .two-col { grid-template-columns: 1fr; } }

    .rank-num {
      display: inline-flex; align-items: center; justify-content: center;
      width: 22px; height: 22px; border-radius: 50%;
      background: #f0f0ea; font-size: 11px; font-weight: 500; color: #5f5e5a;
    }
    .rank-num.top { background: #FAEEDA; color: #633806; }

    /* MODAL DETAIL */
    .overlay {
      display: none; position: fixed; inset: 0; z-index: 100;
      background: rgba(0,0,0,0.35);
      align-items: flex-start; justify-content: center; padding-top: 60px;
    }
    .overlay.open { display: flex; }
    .modal {
      background: #fff; border-radius: 12px; padding: 1.5rem;
      width: 100%; max-width: 460px; max-height: 85vh; overflow-y: auto;
    }
    .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
    .modal-title  { font-size: 15px; font-weight: 500; }
    .modal-sub    { font-size: 12px; color: #888780; margin-top: 2px; }
    .btn-close    { background: none; border: none; cursor: pointer; font-size: 20px; color: #888780; line-height: 1; }
    .detail-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .detail-table th { padding: 7px 10px; background: #fafaf8; font-size: 12px; font-weight: 500; color: #5f5e5a; border-bottom: 0.5px solid #ddddd5; text-align: left; }
    .detail-table td { padding: 7px 10px; border-bottom: 0.5px solid #f0f0ea; }
    .detail-table tr:last-child td { border-bottom: none; }
    .detail-footer { margin-top: 12px; padding-top: 12px; border-top: 0.5px solid #ddddd5; }
    .detail-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
    .detail-row.bold { font-weight: 500; font-size: 15px; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">Toko Sembako</div>
  <div class="navbar-right">
    <a href="index.php"   class="nav-link">Kasir</a>
    <a href="produk.php"  class="nav-link">Produk</a>
    <a href="laporan.php" class="nav-link active">Laporan</a>
    <a href="logout.php"  class="nav-link">Keluar</a>
  </div>
</nav>

<div class="container">
  <div class="page-title">Laporan penjualan</div>

  <div class="filter-bar">
    <input class="filter-input" type="date" id="fDari"  title="Dari tanggal">
    <input class="filter-input" type="date" id="fSampai" title="Sampai tanggal">
    <button class="btn-filter" onclick="loadLaporan()">Tampilkan</button>
    <button class="btn-export" onclick="exportCSV()">Ekspor CSV</button>
  </div>

  <!-- METRIK -->
  <div class="metrics">
    <div class="metric">
      <div class="metric-label">Total transaksi</div>
      <div class="metric-val" id="mTrx">—</div>
    </div>
    <div class="metric">
      <div class="metric-label">Total pendapatan</div>
      <div class="metric-val green" id="mPendapatan">—</div>
    </div>
    <div class="metric">
      <div class="metric-label">Total modal</div>
      <div class="metric-val amber" id="mModal">—</div>
    </div>
    <div class="metric">
      <div class="metric-label">Estimasi laba</div>
      <div class="metric-val green" id="mLaba">—</div>
    </div>
    <div class="metric">
      <div class="metric-label">Rata-rata per transaksi</div>
      <div class="metric-val" id="mRata">—</div>
    </div>
  </div>

  <!-- TERLARIS & STOK MENIPIS -->
  <div class="two-col">
    <div>
      <div class="section-title">Produk terlaris</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Produk</th><th>Terjual</th><th>Pendapatan</th></tr></thead>
          <tbody id="tabelTerlaris"><tr class="empty-row"><td colspan="4">Belum ada data</td></tr></tbody>
        </table>
      </div>
    </div>
    <div>
      <div class="section-title">Stok menipis / habis</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Produk</th><th>Stok</th><th>Min</th><th>Status</th></tr></thead>
          <tbody id="tabelStok"><tr class="empty-row"><td colspan="4">Memuat...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- RIWAYAT TRANSAKSI -->
  <div class="section-title">Riwayat transaksi</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Kode</th>
          <th>Waktu</th>
          <th>Kasir</th>
          <th>Total</th>
          <th>Bayar</th>
          <th>Kembalian</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tabelTrx"><tr class="empty-row"><td colspan="7">Belum ada data</td></tr></tbody>
    </table>
  </div>
</div>

<!-- MODAL DETAIL TRANSAKSI -->
<div class="overlay" id="overlay">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalKode">—</div>
        <div class="modal-sub"   id="modalWaktu">—</div>
      </div>
      <button class="btn-close" onclick="closeModal()">&times;</button>
    </div>
    <table class="detail-table">
      <thead><tr><th>Produk</th><th>Satuan</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
      <tbody id="modalItems"></tbody>
    </table>
    <div class="detail-footer">
      <div class="detail-row"><span>Total</span><span id="dTotal">—</span></div>
      <div class="detail-row"><span>Bayar</span><span id="dBayar">—</span></div>
      <div class="detail-row bold"><span>Kembalian</span><span id="dKembalian">—</span></div>
    </div>
  </div>
</div>

<script>
const fmt   = n  => 'Rp ' + Math.round(n).toLocaleString('id-ID');
const fmtTgl = s => new Date(s).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

let laporanData = null;

// Set default tanggal: hari ini
const today = new Date().toISOString().slice(0, 10);
document.getElementById('fDari').value   = today;
document.getElementById('fSampai').value = today;

async function loadLaporan() {
  const dari   = document.getElementById('fDari').value;
  const sampai = document.getElementById('fSampai').value;
  if (!dari || !sampai) { alert('Pilih rentang tanggal'); return; }

  const res  = await fetch(`/api/laporan.php?dari=${dari}&sampai=${sampai}`);
  laporanData = await res.json();

  renderMetrik(laporanData.ringkasan);
  renderTerlaris(laporanData.terlaris);
  renderTransaksi(laporanData.transaksi);
  loadStok();
}

async function loadStok() {
  const res  = await fetch('/api/produk.php');
  const data = await res.json();
  const menipis = data.filter(p => p.stok <= (p.stok_minimum || 5));
  const tbody = document.getElementById('tabelStok');
  if (!menipis.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="4">Semua stok aman</td></tr>'; return;
  }
  tbody.innerHTML = menipis.map(p => {
    const habis = p.stok <= 0;
    const badge = habis
      ? '<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#FCEBEB;color:#791F1F;font-weight:500;">Habis</span>'
      : '<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#FAEEDA;color:#633806;font-weight:500;">Menipis</span>';
    return `<tr>
      <td style="font-weight:500;">${p.nama}</td>
      <td>${p.stok}</td>
      <td>${p.stok_minimum || 5}</td>
      <td>${badge}</td>
    </tr>`;
  }).join('');
}

function renderMetrik(r) {
  if (!r) return;
  document.getElementById('mTrx').textContent        = r.total_transaksi;
  document.getElementById('mPendapatan').textContent  = fmt(r.total_pendapatan);
  document.getElementById('mModal').textContent       = fmt(r.total_modal);
  document.getElementById('mLaba').textContent        = fmt(r.total_pendapatan - r.total_modal);
  const rata = r.total_transaksi > 0 ? r.total_pendapatan / r.total_transaksi : 0;
  document.getElementById('mRata').textContent        = fmt(rata);
}

function renderTerlaris(data) {
  const tbody = document.getElementById('tabelTerlaris');
  if (!data || !data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="4">Belum ada data</td></tr>'; return;
  }
  tbody.innerHTML = data.map((p, i) => `
    <tr>
      <td><span class="rank-num ${i < 3 ? 'top' : ''}">${i + 1}</span></td>
      <td>${p.nama_produk}</td>
      <td>${p.total_qty}</td>
      <td>${fmt(p.total_pendapatan)}</td>
    </tr>`).join('');
}

function renderTransaksi(data) {
  const tbody = document.getElementById('tabelTrx');
  if (!data || !data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">Tidak ada transaksi</td></tr>'; return;
  }
  tbody.innerHTML = data.map(t => `
    <tr>
      <td style="font-family:monospace;font-size:12px;">${t.kode}</td>
      <td>${fmtTgl(t.created_at)}</td>
      <td>${t.kasir}</td>
      <td style="font-weight:500;">${fmt(t.total)}</td>
      <td>${fmt(t.bayar)}</td>
      <td>${fmt(t.kembalian)}</td>
      <td><button class="btn-detail" onclick="lihatDetail(${t.id})">Detail</button></td>
    </tr>`).join('');
}

async function lihatDetail(id) {
  const res  = await fetch(`/api/laporan.php?detail=${id}`);
  const data = await res.json();
  if (!data || data.error) return;

  document.getElementById('modalKode').textContent   = data.kode;
  document.getElementById('modalWaktu').textContent  = fmtTgl(data.created_at);
  document.getElementById('dTotal').textContent      = fmt(data.total);
  document.getElementById('dBayar').textContent      = fmt(data.bayar);
  document.getElementById('dKembalian').textContent  = fmt(data.kembalian);
  document.getElementById('modalItems').innerHTML    = data.items.map(i => `
    <tr>
      <td>${i.nama_produk}</td>
      <td>${i.nama_satuan}</td>
      <td>${i.qty}</td>
      <td>${fmt(i.harga)}</td>
      <td>${fmt(i.subtotal)}</td>
    </tr>`).join('');
  document.getElementById('overlay').classList.add('open');
}

function closeModal() {
  document.getElementById('overlay').classList.remove('open');
}

function exportCSV() {
  if (!laporanData || !laporanData.transaksi.length) { alert('Tidak ada data untuk diekspor'); return; }
  const dari   = document.getElementById('fDari').value;
  const sampai = document.getElementById('fSampai').value;
  const rows   = [['Kode','Waktu','Kasir','Total','Bayar','Kembalian']];
  laporanData.transaksi.forEach(t => {
    rows.push([t.kode, t.created_at, t.kasir, t.total, t.bayar, t.kembalian]);
  });
  const csv  = rows.map(r => r.join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `laporan_${dari}_${sampai}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

loadLaporan();
loadStok();
</script>
</body>
</html>