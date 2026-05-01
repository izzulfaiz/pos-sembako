<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Kasir — POS Sembako</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f5f5f0; color: #1a1a18; font-size: 14px; }

    .navbar {
      background: #fff; border-bottom: 0.5px solid #ddddd5;
      padding: 0 1rem; height: 52px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .navbar-brand { font-weight: 500; font-size: 14px; }
    .navbar-right { display: flex; align-items: center; gap: 6px; }
    .navbar-role {
      font-size: 11px; padding: 2px 8px; border-radius: 20px;
      background: #E1F5EE; color: #0F6E56; font-weight: 500;
    }
    .navbar-role.admin { background: #EEEDFE; color: #534AB7; }
    .btn-nav {
      font-size: 12px; color: #5f5e5a; text-decoration: none;
      border: 0.5px solid #ddddd5; padding: 5px 10px; border-radius: 8px;
      background: none; cursor: pointer;
    }

    /* DESKTOP */
    @media (min-width: 700px) {
      .layout {
        display: grid; grid-template-columns: 1fr 320px;
        gap: 12px; padding: 12px; height: calc(100vh - 52px);
      }
      .panel {
        background: #fff; border: 0.5px solid #ddddd5;
        border-radius: 12px; padding: 14px;
        display: flex; flex-direction: column; overflow: hidden;
      }
      .tab-bar { display: none; }
      .panel-barang, .panel-keranjang { display: flex !important; }
    }

    /* MOBILE */
    @media (max-width: 699px) {
      .layout { display: block; height: calc(100vh - 52px - 56px); }
      .panel {
        background: #fff; display: flex; flex-direction: column;
        height: 100%; overflow: hidden; padding: 12px;
      }
      .panel-barang { display: flex; }
      .panel-keranjang { display: none; }
      .panel-barang.hidden { display: none; }
      .panel-keranjang.show { display: flex; }
      .tab-bar {
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 56px; background: #fff; border-top: 0.5px solid #ddddd5;
        display: flex; z-index: 50;
      }
      .tab-btn {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 3px; font-size: 11px; color: #888780;
        border: none; background: none; cursor: pointer; position: relative;
        -webkit-tap-highlight-color: transparent;
      }
      .tab-btn.active { color: #1D9E75; }
      .tab-btn svg { width: 22px; height: 22px; }
      .tab-badge {
        position: absolute; top: 6px; right: calc(50% - 20px);
        background: #1D9E75; color: #fff; font-size: 10px; font-weight: 600;
        min-width: 16px; height: 16px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center; padding: 0 3px;
      }
      .tab-badge.hidden { display: none; }
    }

    .panel-title { font-weight: 500; font-size: 14px; margin-bottom: 10px; }

    .search-wrap { position: relative; margin-bottom: 10px; }
    .search-input {
      width: 100%; padding: 10px 12px; font-size: 15px;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fafaf8; color: #1a1a18; outline: none;
    }
    .search-input:focus { border-color: #888; background: #fff; }
    .dropdown {
      position: absolute; top: 100%; left: 0; right: 0; z-index: 20;
      background: #fff; border: 0.5px solid #ccc; border-radius: 8px;
      margin-top: 2px; max-height: 240px; overflow-y: auto; display: none;
    }
    .dd-item {
      padding: 10px 12px; cursor: pointer;
      display: flex; justify-content: space-between; align-items: center;
      border-bottom: 0.5px solid #f0f0ea;
    }
    .dd-item:last-child { border-bottom: none; }
    .dd-item-name { font-size: 13px; }
    .dd-item-right { text-align: right; }
    .dd-item-satuan { font-size: 11px; color: #888780; }
    .dd-item-harga { font-size: 13px; font-weight: 500; color: #1D9E75; }

    .prod-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 8px; overflow-y: auto; flex: 1;
      -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 699px) {
      .prod-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .prod-card {
      border: 0.5px solid #ddddd5; border-radius: 10px;
      padding: 12px 10px; cursor: pointer; background: #fafaf8;
      -webkit-tap-highlight-color: transparent;
    }
    .prod-card:active { background: #e8f5f0; border-color: #1D9E75; }
    .prod-card-nama { font-size: 13px; font-weight: 500; margin-bottom: 4px; line-height: 1.3; }
    .prod-card-satuan { font-size: 11px; color: #888780; margin-bottom: 4px; }
    .prod-card-harga { font-size: 13px; color: #1D9E75; font-weight: 500; }
    .prod-card-stok { font-size: 11px; color: #b4b2a9; margin-top: 2px; }

    .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .cart-list { flex: 1; overflow-y: auto; margin-bottom: 10px; -webkit-overflow-scrolling: touch; }
    .cart-item {
      display: flex; align-items: flex-start; gap: 8px;
      padding: 10px 0; border-bottom: 0.5px solid #f0f0ea;
    }
    .cart-item:last-child { border-bottom: none; }
    .ci-info { flex: 1; min-width: 0; }
    .ci-nama { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ci-satuan { font-size: 11px; color: #888780; }
    .ci-qty-wrap { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
    .qty-btn {
      width: 30px; height: 30px; border: 0.5px solid #ccc;
      border-radius: 6px; background: #f5f5f0; cursor: pointer;
      font-size: 16px; display: flex; align-items: center; justify-content: center;
      -webkit-tap-highlight-color: transparent;
    }
    .qty-btn:active { background: #e0e0d8; }
    .ci-qty { font-size: 14px; min-width: 24px; text-align: center; font-weight: 500; }
    .ci-subtotal { font-size: 13px; font-weight: 500; white-space: nowrap; }
    .ci-del {
      background: none; border: none; cursor: pointer;
      color: #b4b2a9; font-size: 22px; line-height: 1; padding: 4px;
      -webkit-tap-highlight-color: transparent;
    }
    .empty-cart { text-align: center; color: #b4b2a9; padding: 3rem 0; font-size: 13px; }

    .divider { border: none; border-top: 0.5px solid #ddddd5; margin: 10px 0; }
    .row-total { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .lbl-total { font-size: 13px; color: #5f5e5a; }
    .grand { font-size: 20px; font-weight: 600; }

    .bayar-label { font-size: 12px; color: #5f5e5a; margin-bottom: 4px; margin-top: 10px; }
    .bayar-wrap { display: flex; align-items: center; gap: 6px; }
    .bayar-rp { font-size: 14px; color: #5f5e5a; }
    .bayar-input {
      flex: 1; padding: 10px; font-size: 18px; font-weight: 600;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fafaf8; color: #1a1a18; outline: none; text-align: right;
    }
    .bayar-input:focus { border-color: #1D9E75; background: #fff; }

    .cepat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px; }
    .cepat-btn {
      padding: 8px 2px; font-size: 12px; text-align: center;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fafaf8; cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }
    .cepat-btn:active { background: #e8f5f0; border-color: #1D9E75; }

    .kembalian-box {
      margin-top: 8px; padding: 10px 14px; border-radius: 10px;
      background: #E1F5EE; border: 0.5px solid #9FE1CB;
    }
    .kembalian-lbl { font-size: 11px; color: #0F6E56; margin-bottom: 2px; }
    .kembalian-val { font-size: 22px; font-weight: 600; color: #085041; }
    .kurang-box {
      margin-top: 8px; padding: 10px 14px; border-radius: 10px;
      background: #FCEBEB; border: 0.5px solid #F7C1C1;
    }
    .kurang-lbl { font-size: 11px; color: #A32D2D; margin-bottom: 2px; }
    .kurang-val { font-size: 22px; font-weight: 600; color: #791F1F; }

    .btn-bayar {
      margin-top: 10px; width: 100%; padding: 14px;
      font-size: 15px; font-weight: 600; border: none; border-radius: 10px;
      background: #1D9E75; color: #fff; cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-bayar:disabled { opacity: 0.35; cursor: not-allowed; }
    .btn-batal {
      margin-top: 6px; width: 100%; padding: 10px; font-size: 13px;
      border: 0.5px solid #ddddd5; border-radius: 10px;
      background: none; cursor: pointer; color: #5f5e5a;
    }

    /* MODAL SATUAN — slide up di mobile */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 100;
      background: rgba(0,0,0,0.4);
      align-items: flex-end; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    @media (min-width: 700px) {
      .modal-overlay { align-items: center; }
      .modal { border-radius: 12px !important; max-width: 360px; }
    }
    .modal {
      background: #fff; border-radius: 16px 16px 0 0;
      padding: 1.25rem 1.25rem 2rem; width: 100%;
    }
    .modal-title { font-weight: 600; font-size: 16px; margin-bottom: 14px; }
    .satuan-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .satuan-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 12px 14px; border: 0.5px solid #ddddd5; border-radius: 10px;
      cursor: pointer; background: #fafaf8;
      -webkit-tap-highlight-color: transparent;
    }
    .satuan-item:active { background: #f0f0ea; }
    .satuan-item.active { border-color: #1D9E75; background: #E1F5EE; }
    .si-nama { font-size: 14px; font-weight: 500; }
    .si-konv { font-size: 12px; color: #888780; margin-top: 2px; }
    .si-harga { font-size: 14px; font-weight: 600; color: #1D9E75; }
    .modal-actions { display: flex; gap: 8px; }
    .btn-batal-modal {
      flex: 1; padding: 12px; font-size: 14px;
      border: 0.5px solid #ddddd5; border-radius: 10px;
      background: none; cursor: pointer; color: #5f5e5a;
    }
    .btn-tambah-modal {
      flex: 2; padding: 12px; font-size: 14px; font-weight: 600;
      border: none; border-radius: 10px;
      background: #1D9E75; color: #fff; cursor: pointer;
    }

    /* MODAL SUKSES */
    .modal-sukses-overlay {
      display: none; position: fixed; inset: 0; z-index: 300;
      background: rgba(0,0,0,0.45);
      align-items: flex-end; justify-content: center;
    }
    .modal-sukses-overlay.open { display: flex; }
    @media (min-width: 700px) {
      .modal-sukses-overlay { align-items: center; }
      .modal-sukses { border-radius: 14px !important; max-width: 320px; }
    }
    .modal-sukses {
      background: #fff; border-radius: 16px 16px 0 0;
      padding: 2rem 1.75rem 2.5rem; width: 100%; text-align: center;
    }
    .sukses-icon {
      width: 56px; height: 56px; border-radius: 50%;
      background: #E1F5EE; color: #1D9E75; font-size: 28px;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;
    }
    .sukses-title { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
    .sukses-kode { font-size: 12px; color: #888780; margin-bottom: 14px; font-family: monospace; }
    .sukses-kembalian {
      background: #E1F5EE; border-radius: 10px; padding: 12px 16px; margin-bottom: 18px;
    }
    .sukses-kembalian-lbl { font-size: 11px; color: #0F6E56; margin-bottom: 3px; }
    .sukses-kembalian-val { font-size: 28px; font-weight: 700; color: #085041; }
    .sukses-actions { display: flex; gap: 8px; }
    .btn-tutup-sukses {
      flex: 1; padding: 12px; font-size: 14px;
      border: 0.5px solid #ddddd5; border-radius: 10px;
      background: none; cursor: pointer; color: #5f5e5a;
    }
    .btn-cetak-nota {
      flex: 2; padding: 12px; font-size: 14px; font-weight: 600;
      border: none; border-radius: 10px; background: #1D9E75; color: #fff;
      cursor: pointer; text-decoration: none;
      display: flex; align-items: center; justify-content: center; gap: 5px;
    }

    .notif {
      position: fixed; top: 64px; left: 50%; transform: translateX(-50%);
      background: #085041; color: #9FE1CB; font-size: 13px; font-weight: 500;
      padding: 10px 20px; border-radius: 8px; z-index: 200;
      opacity: 0; transition: opacity 0.2s; pointer-events: none; white-space: nowrap;
    }
    .notif.show { opacity: 1; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">Toko Sembako Mujiati</div>
  <div class="navbar-right">
    <span class="navbar-role <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
      <?= $user['role'] === 'admin' ? 'Admin' : 'Kasir' ?>
    </span>
    <?php if (isAdmin()): ?>
      <a href="produk.php" class="btn-nav">Produk</a>
      <a href="laporan.php" class="btn-nav">Laporan</a>
    <?php endif; ?>
    <a href="logout.php" class="btn-nav">Keluar</a>
  </div>
</nav>

<div class="layout">
  <div class="panel panel-barang" id="panelBarang">
    <div class="panel-title">Pilih barang</div>
    <div class="search-wrap">
      <input class="search-input" id="search" type="text"
             placeholder="Ketik nama barang..." autocomplete="off">
      <div class="dropdown" id="dropdown"></div>
    </div>
    <div class="prod-grid" id="prodGrid"></div>
  </div>

  <div class="panel panel-keranjang" id="panelKeranjang">
    <div class="cart-header">
      <div class="panel-title" style="margin:0;">Keranjang</div>
      <span id="itemCount" style="font-size:12px;color:#888780;">0 item</span>
    </div>
    <div class="cart-list" id="cartList">
      <div class="empty-cart">Belum ada barang dipilih</div>
    </div>
    <hr class="divider">
    <div class="row-total">
      <span class="lbl-total">Total</span>
      <span class="grand" id="grandTotal">Rp 0</span>
    </div>
    <div class="bayar-label">Uang diterima</div>
    <div class="bayar-wrap">
      <span class="bayar-rp">Rp</span>
      <input class="bayar-input" id="bayarInput" type="number" placeholder="0" min="0" inputmode="numeric">
    </div>
    <div class="cepat-grid" id="cepatGrid"></div>
    <div id="kembalianBox"></div>
    <button class="btn-bayar" id="btnBayar" disabled>Proses pembayaran</button>
    <button class="btn-batal" id="btnBatal">Kosongkan keranjang</button>
  </div>
</div>

<!-- TAB BAR (mobile) -->
<div class="tab-bar">
  <button class="tab-btn active" id="tabBarang" onclick="switchTab('barang')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
      <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 5M17 13l1.4 5M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
    </svg>
    Barang
  </button>
  <button class="tab-btn" id="tabKeranjang" onclick="switchTab('keranjang')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
      <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
      <line x1="3" y1="6" x2="21" y2="6"/>
      <path d="M16 10a4 4 0 01-8 0"/>
    </svg>
    <span class="tab-badge hidden" id="tabBadge">0</span>
    Keranjang
  </button>
</div>

<!-- MODAL SATUAN -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-title" id="modalTitle">Pilih satuan</div>
    <div class="satuan-list" id="satuanList"></div>
    <div class="modal-actions">
      <button class="btn-batal-modal" onclick="closeModal()">Batal</button>
      <button class="btn-tambah-modal" onclick="tambahDariModal()">Tambahkan</button>
    </div>
  </div>
</div>

<!-- MODAL SUKSES -->
<div class="modal-sukses-overlay" id="modalSuksesOverlay">
  <div class="modal-sukses">
    <div class="sukses-icon">✓</div>
    <div class="sukses-title">Transaksi Berhasil!</div>
    <div class="sukses-kode" id="suksesKode"></div>
    <div class="sukses-kembalian">
      <div class="sukses-kembalian-lbl">Kembalian</div>
      <div class="sukses-kembalian-val" id="suksesKembalian"></div>
    </div>
    <div class="sukses-actions">
      <button class="btn-tutup-sukses" id="btnTutusSukses">Tutup</button>
      <a class="btn-cetak-nota" id="btnCetakNota" href="#">🖨️ Cetak Nota</a>
    </div>
  </div>
</div>

<div class="notif" id="notif"></div>

<script>
let produkData = [], cart = [], selectedProduk = null, selectedSatuan = null;
const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

function switchTab(tab) {
  const pb = document.getElementById('panelBarang');
  const pk = document.getElementById('panelKeranjang');
  const tb = document.getElementById('tabBarang');
  const tk = document.getElementById('tabKeranjang');
  if (tab === 'barang') {
    pb.classList.remove('hidden'); pk.classList.remove('show');
    tb.classList.add('active'); tk.classList.remove('active');
  } else {
    pb.classList.add('hidden'); pk.classList.add('show');
    tb.classList.remove('active'); tk.classList.add('active');
  }
}

async function loadProduk() {
  const res = await fetch('api/produk.php');
  produkData = await res.json();
  renderGrid(produkData);
}

function renderGrid(data) {
  const g = document.getElementById('prodGrid');
  if (!data.length) { g.innerHTML = '<div class="empty-cart">Tidak ada barang</div>'; return; }
  g.innerHTML = data.map(p => {
    const def = p.satuan.find(s => s.is_default) || p.satuan[0];
    return `<div class="prod-card" onclick="openModal(${p.id})">
      <div class="prod-card-nama">${p.nama}</div>
      <div class="prod-card-satuan">${def.nama_satuan}</div>
      <div class="prod-card-harga">${fmt(def.harga_jual)}</div>
      <div class="prod-card-stok">Stok: ${Math.round(p.stok)}</div>
    </div>`;
  }).join('');
}

function openModal(produkId) {
  selectedProduk = produkData.find(p => p.id === produkId);
  if (!selectedProduk) return;
  selectedSatuan = selectedProduk.satuan.find(s => s.is_default) || selectedProduk.satuan[0];
  document.getElementById('modalTitle').textContent = selectedProduk.nama;
  document.getElementById('satuanList').innerHTML = selectedProduk.satuan.map(s => `
    <div class="satuan-item ${s.id === selectedSatuan.id ? 'active' : ''}"
         onclick="pilihSatuan(${s.id})"">
      <div>
        <div class="si-nama">${s.nama_satuan}</div>
        <div class="si-konv">Setara ${Math.round(s.konversi)} satuan dasar</div>
      </div>
      <div class="si-harga">${fmt(s.harga_jual)}</div>
    </div>`).join('');
  document.getElementById('modalOverlay').classList.add('open');
}

function pilihSatuan(satuanId) {
  selectedSatuan = selectedProduk.satuan.find(s => String(s.id) === String(satuanId));
  document.querySelectorAll('.satuan-item').forEach(e => e.classList.remove('active'));
  event.currentTarget.classList.add('active');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  selectedProduk = null; selectedSatuan = null;
}

function tambahDariModal() {
  if (!selectedProduk || !selectedSatuan) return;
  const namaProduk = selectedProduk.nama;
  const key = `${selectedProduk.id}_${selectedSatuan.id}`;
  const ex = cart.find(c => c.key === key);
  if (ex) { ex.qty++; }
  else {
    cart.push({ key, produk_id: selectedProduk.id, satuan_id: selectedSatuan.id,
      nama: selectedProduk.nama, nama_satuan: selectedSatuan.nama_satuan,
      konversi: parseFloat(selectedSatuan.konversi), harga: parseFloat(selectedSatuan.harga_jual), qty: 1 });
  }
  closeModal(); renderCart();
  showNotif(namaProduk + ' ditambahkan');
}

function renderCart() {
  const el = document.getElementById('cartList');
  const cnt = document.getElementById('itemCount');
  const badge = document.getElementById('tabBadge');
  const totalQty = cart.reduce((a, c) => a + c.qty, 0);
  cnt.textContent = totalQty + ' item';
  if (totalQty > 0) { badge.textContent = totalQty > 99 ? '99+' : totalQty; badge.classList.remove('hidden'); }
  else { badge.classList.add('hidden'); }

  if (!cart.length) {
    el.innerHTML = '<div class="empty-cart">Belum ada barang dipilih</div>';
    updateTotal(); return;
  }
  el.innerHTML = cart.map((c, i) => `
    <div class="cart-item">
      <div class="ci-info">
        <div class="ci-nama">${c.nama}</div>
        <div class="ci-satuan">${c.nama_satuan} &times; ${c.qty}</div>
        <div class="ci-qty-wrap">
          <button class="qty-btn" onclick="ubahQty(${i},-1)">−</button>
          <span class="ci-qty">${c.qty}</span>
          <button class="qty-btn" onclick="ubahQty(${i},1)">+</button>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
        <button class="ci-del" onclick="hapusItem(${i})">&times;</button>
        <span class="ci-subtotal">${fmt(c.harga * c.qty)}</span>
      </div>
    </div>`).join('');
  updateTotal();
}

function ubahQty(i, d) { cart[i].qty += d; if (cart[i].qty <= 0) cart.splice(i, 1); renderCart(); }
function hapusItem(i) { cart.splice(i, 1); renderCart(); }
function getTotal() { return cart.reduce((a, c) => a + (c.harga * c.qty), 0); }

function updateTotal() {
  const t = getTotal();
  document.getElementById('grandTotal').textContent = fmt(t);
  renderCepat(t); hitungKembalian();
}

function renderCepat(t) {
  if (t === 0) { document.getElementById('cepatGrid').innerHTML = ''; return; }
  const opts = [...new Set([t, Math.ceil(t/10000)*10000, Math.ceil(t/50000)*50000, Math.ceil(t/100000)*100000])].slice(0,3);
  document.getElementById('cepatGrid').innerHTML = opts.map(n =>
    `<div class="cepat-btn" onclick="setBayar(${n})">${fmt(n)}</div>`).join('');
}

function setBayar(n) { document.getElementById('bayarInput').value = n; hitungKembalian(); }

function hitungKembalian() {
  const t = getTotal();
  const bayar = parseInt(document.getElementById('bayarInput').value) || 0;
  const box = document.getElementById('kembalianBox');
  const btn = document.getElementById('btnBayar');
  if (t === 0 || bayar === 0) { box.innerHTML = ''; btn.disabled = true; return; }
  const selisih = bayar - t;
  if (selisih >= 0) {
    box.innerHTML = `<div class="kembalian-box"><div class="kembalian-lbl">Kembalian</div><div class="kembalian-val">${fmt(selisih)}</div></div>`;
    btn.disabled = false;
  } else {
    box.innerHTML = `<div class="kurang-box"><div class="kurang-lbl">Kurang bayar</div><div class="kurang-val">${fmt(Math.abs(selisih))}</div></div>`;
    btn.disabled = true;
  }
}

document.getElementById('bayarInput').addEventListener('input', hitungKembalian);

document.getElementById('btnBayar').addEventListener('click', async () => {
  const t = getTotal();
  const bayar = parseInt(document.getElementById('bayarInput').value) || 0;
  if (!cart.length || bayar < t) return;
  const payload = {
    items: cart.map(c => ({ produk_id: c.produk_id, satuan_id: c.satuan_id,
      nama_produk: c.nama, nama_satuan: c.nama_satuan, konversi: c.konversi,
      harga: c.harga, qty: c.qty, subtotal: c.harga * c.qty })),
    total: t, bayar, kembalian: bayar - t,
  };
  const res = await fetch('api/transaksi.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (data.success) {
    document.getElementById('suksesKode').textContent = 'No. ' + data.kode;
    document.getElementById('suksesKembalian').textContent = fmt(bayar - t);
    document.getElementById('btnCetakNota').href = 'nota.php?kode=' + encodeURIComponent(data.kode);
    document.getElementById('modalSuksesOverlay').classList.add('open');
    cart = [];
    document.getElementById('bayarInput').value = '';
    document.getElementById('kembalianBox').innerHTML = '';
    document.getElementById('btnBayar').disabled = true;
    renderCart(); loadProduk();
  } else { showNotif('Gagal: ' + (data.error || 'Coba lagi')); }
});

document.getElementById('btnBatal').addEventListener('click', () => {
  cart = [];
  document.getElementById('bayarInput').value = '';
  document.getElementById('kembalianBox').innerHTML = '';
  document.getElementById('btnBayar').disabled = true;
  renderCart();
});

document.getElementById('btnTutusSukses').addEventListener('click', () => {
  document.getElementById('modalSuksesOverlay').classList.remove('open');
  switchTab('barang');
});

const searchEl = document.getElementById('search');
const ddEl = document.getElementById('dropdown');
searchEl.addEventListener('input', () => {
  const q = searchEl.value.toLowerCase().trim();
  if (!q) { ddEl.style.display = 'none'; return; }
  const hasil = produkData.filter(p => p.nama.toLowerCase().includes(q));
  if (!hasil.length) { ddEl.style.display = 'none'; return; }
  ddEl.innerHTML = hasil.map(p => {
    const def = p.satuan.find(s => s.is_default) || p.satuan[0];
    return `<div class="dd-item" onmousedown="openModal(${p.id}); searchEl.value=''; ddEl.style.display='none';">
      <span class="dd-item-name">${p.nama}</span>
      <div class="dd-item-right">
        <div class="dd-item-satuan">${def.nama_satuan}</div>
        <div class="dd-item-harga">${fmt(def.harga_jual)}</div>
      </div>
    </div>`;
  }).join('');
  ddEl.style.display = 'block';
});
searchEl.addEventListener('blur', () => setTimeout(() => { ddEl.style.display = 'none'; }, 150));

function showNotif(msg) {
  const el = document.getElementById('notif');
  el.textContent = msg; el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2500);
}

loadProduk();
</script>
</body>
</html>
