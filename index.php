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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kasir — POS Sembako</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f5f5f0; color: #1a1a18; font-size: 14px; }

    /* NAVBAR */
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
    .navbar-right { display: flex; align-items: center; gap: 1rem; }
    .navbar-user { font-size: 13px; color: #5f5e5a; }
    .navbar-role {
      font-size: 11px; padding: 2px 8px; border-radius: 20px;
      background: #E1F5EE; color: #0F6E56; font-weight: 500;
    }
    .navbar-role.admin { background: #EEEDFE; color: #534AB7; }
    .btn-logout {
      font-size: 13px; color: #5f5e5a; text-decoration: none;
      border: 0.5px solid #ddddd5; padding: 5px 12px; border-radius: 8px;
      background: none; cursor: pointer;
    }
    .btn-logout:hover { background: #f5f5f0; }

    /* LAYOUT */
    .layout {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 12px;
      padding: 12px;
      height: calc(100vh - 52px);
    }

    /* PANEL */
    .panel {
      background: #fff;
      border: 0.5px solid #ddddd5;
      border-radius: 12px;
      padding: 14px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .panel-title { font-weight: 500; font-size: 14px; margin-bottom: 10px; }

    /* SEARCH */
    .search-wrap { position: relative; margin-bottom: 10px; }
    .search-input {
      width: 100%; padding: 9px 12px; font-size: 14px;
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
      padding: 9px 12px; cursor: pointer;
      display: flex; justify-content: space-between; align-items: center;
      border-bottom: 0.5px solid #f0f0ea;
    }
    .dd-item:last-child { border-bottom: none; }
    .dd-item:hover { background: #f5f5f0; }
    .dd-item-name { font-size: 13px; }
    .dd-item-right { text-align: right; }
    .dd-item-satuan { font-size: 11px; color: #888780; }
    .dd-item-harga { font-size: 13px; font-weight: 500; color: #1D9E75; }

    /* PRODUK GRID */
    .prod-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
      gap: 8px;
      overflow-y: auto;
      flex: 1;
    }
    .prod-card {
      border: 0.5px solid #ddddd5; border-radius: 8px;
      padding: 10px 8px; cursor: pointer; background: #fafaf8;
      transition: background 0.12s, border-color 0.12s;
    }
    .prod-card:hover { background: #f0f0ea; border-color: #bbb; }
    .prod-card-nama { font-size: 13px; font-weight: 500; margin-bottom: 4px; }
    .prod-card-satuan { font-size: 11px; color: #888780; margin-bottom: 4px; }
    .prod-card-harga { font-size: 13px; color: #1D9E75; font-weight: 500; }
    .prod-card-stok { font-size: 11px; color: #b4b2a9; margin-top: 2px; }

    /* KERANJANG */
    .cart-list { flex: 1; overflow-y: auto; margin-bottom: 10px; }
    .cart-item {
      display: flex; align-items: flex-start; gap: 8px;
      padding: 8px 0; border-bottom: 0.5px solid #f0f0ea;
    }
    .cart-item:last-child { border-bottom: none; }
    .ci-info { flex: 1; min-width: 0; }
    .ci-nama { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ci-satuan { font-size: 11px; color: #888780; }
    .ci-qty-wrap { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
    .qty-btn {
      width: 22px; height: 22px; border: 0.5px solid #ccc;
      border-radius: 4px; background: #f5f5f0; cursor: pointer;
      font-size: 14px; display: flex; align-items: center; justify-content: center;
      color: #1a1a18; line-height: 1;
    }
    .qty-btn:hover { background: #e8e8e0; }
    .ci-qty { font-size: 13px; min-width: 20px; text-align: center; }
    .ci-subtotal { font-size: 13px; font-weight: 500; white-space: nowrap; }
    .ci-del {
      background: none; border: none; cursor: pointer;
      color: #b4b2a9; font-size: 16px; line-height: 1; padding: 2px;
    }
    .ci-del:hover { color: #a32d2d; }
    .empty-cart { text-align: center; color: #b4b2a9; padding: 2rem 0; font-size: 13px; }

    /* TOTAL & BAYAR */
    .divider { border: none; border-top: 0.5px solid #ddddd5; margin: 10px 0; }
    .row-total { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .lbl-total { font-size: 13px; color: #5f5e5a; }
    .val-total { font-size: 13px; }
    .grand { font-size: 18px; font-weight: 500; }

    .bayar-label { font-size: 12px; color: #5f5e5a; margin-bottom: 4px; margin-top: 10px; }
    .bayar-wrap { display: flex; align-items: center; gap: 6px; }
    .bayar-rp { font-size: 13px; color: #5f5e5a; }
    .bayar-input {
      flex: 1; padding: 8px 10px; font-size: 15px; font-weight: 500;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fafaf8; color: #1a1a18; outline: none; text-align: right;
    }
    .bayar-input:focus { border-color: #888; background: #fff; }

    .cepat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-top: 6px; }
    .cepat-btn {
      padding: 5px 2px; font-size: 11px; text-align: center;
      border: 0.5px solid #ccc; border-radius: 6px;
      background: #fafaf8; cursor: pointer; color: #1a1a18;
    }
    .cepat-btn:hover { background: #f0f0ea; }

    .kembalian-box {
      margin-top: 8px; padding: 9px 12px; border-radius: 8px;
      background: #E1F5EE; border: 0.5px solid #9FE1CB;
    }
    .kembalian-lbl { font-size: 11px; color: #0F6E56; margin-bottom: 2px; }
    .kembalian-val { font-size: 19px; font-weight: 500; color: #085041; }
    .kurang-box {
      margin-top: 8px; padding: 9px 12px; border-radius: 8px;
      background: #FCEBEB; border: 0.5px solid #F7C1C1;
    }
    .kurang-lbl { font-size: 11px; color: #A32D2D; margin-bottom: 2px; }
    .kurang-val { font-size: 19px; font-weight: 500; color: #791F1F; }

    .btn-bayar {
      margin-top: 10px; width: 100%; padding: 11px;
      font-size: 14px; font-weight: 500; border: none; border-radius: 8px;
      background: #1D9E75; color: #E1F5EE; cursor: pointer; transition: opacity 0.15s;
    }
    .btn-bayar:hover { opacity: 0.88; }
    .btn-bayar:disabled { opacity: 0.35; cursor: not-allowed; }
    .btn-batal {
      margin-top: 6px; width: 100%; padding: 8px; font-size: 13px;
      border: 0.5px solid #ddddd5; border-radius: 8px;
      background: none; cursor: pointer; color: #5f5e5a;
    }
    .btn-batal:hover { background: #f5f5f0; }

    /* MODAL SATUAN */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 100;
      background: rgba(0,0,0,0.35);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: #fff; border-radius: 12px;
      padding: 1.5rem; width: 100%; max-width: 340px;
    }
    .modal-title { font-weight: 500; font-size: 15px; margin-bottom: 12px; }
    .satuan-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
    .satuan-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 12px; border: 0.5px solid #ddddd5; border-radius: 8px;
      cursor: pointer; background: #fafaf8; transition: background 0.12s;
    }
    .satuan-item:hover { background: #f0f0ea; border-color: #bbb; }
    .satuan-item.active { border-color: #1D9E75; background: #E1F5EE; }
    .si-nama { font-size: 13px; font-weight: 500; }
    .si-konv { font-size: 11px; color: #888780; }
    .si-harga { font-size: 13px; font-weight: 500; color: #1D9E75; }
    .modal-actions { display: flex; gap: 8px; }
    .btn-batal-modal {
      flex: 1; padding: 9px; font-size: 13px;
      border: 0.5px solid #ddddd5; border-radius: 8px;
      background: none; cursor: pointer; color: #5f5e5a;
    }
    .btn-tambah-modal {
      flex: 2; padding: 9px; font-size: 13px; font-weight: 500;
      border: none; border-radius: 8px;
      background: #1D9E75; color: #E1F5EE; cursor: pointer;
    }
    .btn-tambah-modal:hover { opacity: 0.88; }

    /* NOTIF */
    .notif {
      position: fixed; top: 64px; left: 50%; transform: translateX(-50%);
      background: #085041; color: #9FE1CB; font-size: 13px; font-weight: 500;
      padding: 10px 20px; border-radius: 8px; z-index: 200;
      opacity: 0; transition: opacity 0.2s; pointer-events: none;
    }
    .notif.show { opacity: 1; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">Toko Sembako Mujiati</div>
  <div class="navbar-right">
    <span class="navbar-user"><?= htmlspecialchars($user['nama']) ?></span>
    <span class="navbar-role <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
      <?= $user['role'] === 'admin' ? 'Admin' : 'Kasir' ?>
    </span>
    <?php if (isAdmin()): ?>
      <a href="produk.php" class="btn-logout">Produk</a>
      <a href="laporan.php" class="btn-logout">Laporan</a>
    <?php endif; ?>
    <a href="logout.php" class="btn-logout">Keluar</a>
  </div>
</nav>

<div class="layout">

  <!-- KIRI: PILIH BARANG -->
  <div class="panel">
    <div class="panel-title">Pilih barang</div>
    <div class="search-wrap">
      <input class="search-input" id="search" type="text"
             placeholder="Ketik nama barang..." autocomplete="off">
      <div class="dropdown" id="dropdown"></div>
    </div>
    <div class="prod-grid" id="prodGrid"></div>
  </div>

  <!-- KANAN: KERANJANG & BAYAR -->
  <div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <div class="panel-title" style="margin:0;">Keranjang</div>
      <span id="itemCount" style="font-size:12px;color:#888780;">0 item</span>
    </div>
    <div class="cart-list" id="cartList">
      <div class="empty-cart">Belum ada barang dipilih</div>
    </div>
    <hr class="divider">
    <div class="row-total">
      <span class="lbl-total">Total</span>
      <span class="val-total grand" id="grandTotal">Rp 0</span>
    </div>
    <div class="bayar-label">Uang diterima</div>
    <div class="bayar-wrap">
      <span class="bayar-rp">Rp</span>
      <input class="bayar-input" id="bayarInput" type="number" placeholder="0" min="0">
    </div>
    <div class="cepat-grid" id="cepatGrid"></div>
    <div id="kembalianBox"></div>
    <button class="btn-bayar" id="btnBayar" disabled>Proses pembayaran</button>
    <button class="btn-batal" id="btnBatal">Kosongkan keranjang</button>
  </div>

</div>

<!-- MODAL PILIH SATUAN -->
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

<div class="notif" id="notif"></div>

<script>
let produkData = [];
let cart = [];
let selectedProduk = null;
let selectedSatuan = null;

const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

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
      <div class="prod-card-stok">Stok: ${p.stok}</div>
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
         onclick="pilihSatuan(${s.id})">
      <div>
        <div class="si-nama">${s.nama_satuan}</div>
        <div class="si-konv">Setara ${s.konversi} satuan dasar</div>
      </div>
      <div class="si-harga">${fmt(s.harga_jual)}</div>
    </div>`).join('');
  document.getElementById('modalOverlay').classList.add('open');
}

function pilihSatuan(satuanId) {
  selectedSatuan = selectedProduk.satuan.find(s => s.id === satuanId);
  document.querySelectorAll('.satuan-item').forEach(el => el.classList.remove('active'));
  event.currentTarget.classList.add('active');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  selectedProduk = null;
  selectedSatuan = null;
}

function tambahDariModal() {
  if (!selectedProduk || !selectedSatuan) return;
  const key = `${selectedProduk.id}_${selectedSatuan.id}`;
  const ex = cart.find(c => c.key === key);
  if (ex) {
    ex.qty++;
  } else {
    cart.push({
      key,
      produk_id: selectedProduk.id,
      satuan_id: selectedSatuan.id,
      nama: selectedProduk.nama,
      nama_satuan: selectedSatuan.nama_satuan,
      konversi: parseFloat(selectedSatuan.konversi),
      harga: parseFloat(selectedSatuan.harga_jual),
      qty: 1,
    });
  }
  closeModal();
  renderCart();
  showNotif(selectedProduk.nama + ' ditambahkan');
}

function renderCart() {
  const el = document.getElementById('cartList');
  const cnt = document.getElementById('itemCount');
  const totalQty = cart.reduce((a, c) => a + c.qty, 0);
  cnt.textContent = totalQty + ' item';
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
          <button class="qty-btn" onclick="ubahQty(${i}, -1)">-</button>
          <span class="ci-qty">${c.qty}</span>
          <button class="qty-btn" onclick="ubahQty(${i}, 1)">+</button>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
        <button class="ci-del" onclick="hapusItem(${i})">&times;</button>
        <span class="ci-subtotal">${fmt(c.harga * c.qty)}</span>
      </div>
    </div>`).join('');
  updateTotal();
}

function ubahQty(i, d) {
  cart[i].qty += d;
  if (cart[i].qty <= 0) cart.splice(i, 1);
  renderCart();
}

function hapusItem(i) {
  cart.splice(i, 1);
  renderCart();
}

function getTotal() {
  return cart.reduce((a, c) => a + (c.harga * c.qty), 0);
}

function updateTotal() {
  const t = getTotal();
  document.getElementById('grandTotal').textContent = fmt(t);
  renderCepat(t);
  hitungKembalian();
}

function renderCepat(t) {
  if (t === 0) { document.getElementById('cepatGrid').innerHTML = ''; return; }
  const opts = [...new Set([t, Math.ceil(t/10000)*10000, Math.ceil(t/50000)*50000, Math.ceil(t/100000)*100000])].slice(0, 3);
  document.getElementById('cepatGrid').innerHTML = opts.map(n =>
    `<div class="cepat-btn" onclick="setBayar(${n})">${fmt(n)}</div>`).join('');
}

function setBayar(n) {
  document.getElementById('bayarInput').value = n;
  hitungKembalian();
}

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
    items: cart.map(c => ({
      produk_id: c.produk_id,
      satuan_id: c.satuan_id,
      nama_produk: c.nama,
      nama_satuan: c.nama_satuan,
      konversi: c.konversi,
      harga: c.harga,
      qty: c.qty,
      subtotal: c.harga * c.qty,
    })),
    total: t,
    bayar,
    kembalian: bayar - t,
  };

  const res = await fetch('api/transaksi.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();

  if (data.success) {
    showNotif('Transaksi berhasil! Kembalian ' + fmt(bayar - t));
    cart = [];
    document.getElementById('bayarInput').value = '';
    document.getElementById('kembalianBox').innerHTML = '';
    document.getElementById('btnBayar').disabled = true;
    renderCart();
    loadProduk();
  } else {
    showNotif('Gagal: ' + (data.error || 'Coba lagi'));
  }
});

document.getElementById('btnBatal').addEventListener('click', () => {
  cart = [];
  document.getElementById('bayarInput').value = '';
  document.getElementById('kembalianBox').innerHTML = '';
  document.getElementById('btnBayar').disabled = true;
  renderCart();
});

// SEARCH
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
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2500);
}

loadProduk();
</script>
</body>
</html>
