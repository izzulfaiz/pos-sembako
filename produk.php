<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAdmin();
$user = currentUser();
$pdo  = getDB();

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

$satuan_list = ['pcs','kg','gram','liter','ml','dus','karung','lusin','roll','pack'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Produk — POS Sembako</title>
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
    .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .page-title { font-size: 16px; font-weight: 500; }

    .btn-primary {
      padding: 8px 16px; font-size: 13px; font-weight: 500;
      border: none; border-radius: 8px;
      background: #1D9E75; color: #E1F5EE; cursor: pointer;
    }
    .btn-primary:hover { opacity: 0.88; }

    .filter-bar { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
    .filter-input {
      padding: 7px 12px; font-size: 13px;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fff; color: #1a1a18; outline: none;
    }
    .filter-input:focus { border-color: #888; }

    .table-wrap { background: #fff; border: 0.5px solid #ddddd5; border-radius: 12px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
      padding: 10px 14px; text-align: left;
      font-size: 12px; font-weight: 500; color: #5f5e5a;
      background: #fafaf8; border-bottom: 0.5px solid #ddddd5;
    }
    tbody td { padding: 10px 14px; border-bottom: 0.5px solid #f0f0ea; font-size: 13px; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #fafaf8; }

    .badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
    .badge-ok   { background: #E1F5EE; color: #0F6E56; }
    .badge-warn { background: #FAEEDA; color: #633806; }
    .badge-low  { background: #FCEBEB; color: #791F1F; }

    .btn-edit {
      font-size: 12px; padding: 4px 10px;
      border: 0.5px solid #ddddd5; border-radius: 6px;
      background: none; cursor: pointer; color: #1a1a18; margin-right: 4px;
    }
    .btn-edit:hover { background: #f0f0ea; }
    .btn-del {
      font-size: 12px; padding: 4px 10px;
      border: 0.5px solid #F7C1C1; border-radius: 6px;
      background: none; cursor: pointer; color: #A32D2D;
    }
    .btn-del:hover { background: #FCEBEB; }

    /* MODAL */
    .overlay {
      display: none; position: fixed; inset: 0; z-index: 100;
      background: rgba(0,0,0,0.35);
      align-items: flex-start; justify-content: center; padding-top: 60px;
    }
    .overlay.open { display: flex; }
    .modal {
      background: #fff; border-radius: 12px; padding: 1.5rem;
      width: 100%; max-width: 520px; max-height: 85vh; overflow-y: auto;
    }
    .modal-title { font-size: 15px; font-weight: 500; margin-bottom: 16px; }

    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 12px; color: #5f5e5a; margin-bottom: 4px; }
    .form-control {
      width: 100%; padding: 8px 12px; font-size: 14px;
      border: 0.5px solid #ccc; border-radius: 8px;
      background: #fafaf8; color: #1a1a18; outline: none;
    }
    .form-control:focus { border-color: #888; background: #fff; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    .satuan-section { margin-top: 16px; }
    .satuan-section-title {
      font-size: 13px; font-weight: 500; margin-bottom: 8px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .btn-add-satuan {
      font-size: 12px; padding: 4px 12px;
      border: 0.5px solid #1D9E75; border-radius: 6px;
      background: none; cursor: pointer; color: #0F6E56;
    }
    .btn-add-satuan:hover { background: #E1F5EE; }

    .satuan-header {
      display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
      gap: 6px; margin-bottom: 4px; padding: 0 2px;
    }
    .satuan-header span { font-size: 11px; color: #888780; }

    .satuan-row {
      display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
      gap: 6px; margin-bottom: 6px; align-items: center;
    }
    .satuan-row select,
    .satuan-row input { padding: 7px 8px; font-size: 13px; }
    .satuan-badge {
      font-size: 10px; padding: 2px 6px; border-radius: 10px;
      background: #E1F5EE; color: #0F6E56; font-weight: 500;
      text-align: center; white-space: nowrap;
    }

    .btn-rm-satuan {
      width: 28px; height: 28px; border: 0.5px solid #F7C1C1;
      border-radius: 6px; background: none; cursor: pointer;
      color: #A32D2D; font-size: 16px;
      display: flex; align-items: center; justify-content: center;
    }
    .btn-rm-satuan:disabled { opacity: 0.3; cursor: not-allowed; border-color: #ddd; color: #bbb; }

    .hint-box {
      font-size: 12px; color: #5f5e5a; background: #fafaf8;
      border: 0.5px solid #ddddd5; border-radius: 8px;
      padding: 8px 12px; margin-bottom: 14px; line-height: 1.6;
    }

    .modal-actions { display: flex; gap: 8px; margin-top: 18px; }
    .btn-cancel {
      flex: 1; padding: 9px; font-size: 13px;
      border: 0.5px solid #ddddd5; border-radius: 8px;
      background: none; cursor: pointer; color: #5f5e5a;
    }
    .btn-save {
      flex: 2; padding: 9px; font-size: 13px; font-weight: 500;
      border: none; border-radius: 8px;
      background: #1D9E75; color: #E1F5EE; cursor: pointer;
    }
    .btn-save:hover { opacity: 0.88; }

    .notif {
      position: fixed; top: 64px; left: 50%; transform: translateX(-50%);
      background: #085041; color: #9FE1CB; font-size: 13px; font-weight: 500;
      padding: 10px 20px; border-radius: 8px; z-index: 200;
      opacity: 0; transition: opacity 0.2s; pointer-events: none; white-space: nowrap;
    }
    .notif.show { opacity: 1; }
    .empty-row td { text-align: center; color: #b4b2a9; padding: 2rem; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">Toko Sembako Mujiati</div>
  <div class="navbar-right">
    <a href="index.php"   class="nav-link">Kasir</a>
    <a href="produk.php"  class="nav-link active">Produk</a>
    <a href="laporan.php" class="nav-link">Laporan</a>
    <a href="logout.php"  class="nav-link">Keluar</a>
  </div>
</nav>

<div class="container">
  <div class="topbar">
    <div class="page-title">Manajemen Produk</div>
    <button class="btn-primary" onclick="openModal()">+ Tambah produk</button>
  </div>

  <div class="filter-bar">
    <input class="filter-input" id="filterNama" type="text" placeholder="Cari nama produk..." oninput="filterTable()" style="width:220px;">
    <select class="filter-input" id="filterKat" onchange="filterTable()">
      <option value="">Semua kategori</option>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="filter-input" id="filterStok" onchange="filterTable()">
      <option value="">Semua stok</option>
      <option value="aman">Stok aman</option>
      <option value="menipis">Stok menipis</option>
      <option value="habis">Stok habis</option>
    </select>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama produk</th>
          <th>Kategori</th>
          <th>Stok</th>
          <th>Satuan & harga jual</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tabelProduk">
        <tr class="empty-row"><td colspan="6">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="overlay" id="overlay">
  <div class="modal">
    <div class="modal-title" id="modalTitle">Tambah produk</div>
    <input type="hidden" id="editId">

    <div class="hint-box">
      💡 <strong>Satuan pertama</strong> adalah satuan terkecil (konversi = 1). Satuan berikutnya diisi sesuai isinya, misal karung 25kg → konversi 25.
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nama produk</label>
        <input class="form-control" id="fNama" type="text" placeholder="cth: Beras">
      </div>
      <div class="form-group">
        <label class="form-label">Kategori</label>
        <select class="form-control" id="fKategori">
          <?php foreach ($kategori as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Stok saat ini</label>
        <input class="form-control" id="fStok" type="number" min="0" placeholder="0">
      </div>
      <div class="form-group">
        <label class="form-label">Stok minimum (notif)</label>
        <input class="form-control" id="fStokMin" type="number" min="0" placeholder="5">
      </div>
    </div>

    <div class="satuan-section">
      <div class="satuan-section-title">
        <span>Satuan & harga</span>
        <button class="btn-add-satuan" onclick="tambahBarisSatuan()">+ Tambah satuan</button>
      </div>
      <div class="satuan-header">
        <span>Satuan</span>
        <span>Konversi</span>
        <span>Harga beli (Rp)</span>
        <span>Harga jual (Rp)</span>
        <span></span>
      </div>
      <div id="satuanRows"></div>
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeModal()">Batal</button>
      <button class="btn-save" onclick="simpanProduk()">Simpan produk</button>
    </div>
  </div>
</div>

<div class="notif" id="notif"></div>

<!-- Satuan list dari PHP -->
<script>
const SATUAN_LIST = <?= json_encode($satuan_list) ?>;
const fmt = n => 'Rp ' + Math.round(parseFloat(n)).toLocaleString('id-ID');
let allProduk = [];

async function loadProduk() {
  const res = await fetch('api/produk.php');
  allProduk = await res.json();
  renderTable(allProduk);
}

function statusStok(stok, min) {
  if (stok <= 0)   return ['Habis',   'badge-low'];
  if (stok <= min) return ['Menipis', 'badge-warn'];
  return ['Aman', 'badge-ok'];
}

function renderTable(data) {
  const tbody = document.getElementById('tabelProduk');
  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Tidak ada produk</td></tr>'; return;
  }
  tbody.innerHTML = data.map(p => {
    const [stokLabel, stokClass] = statusStok(p.stok, p.stok_minimum || 5);
    const satuanInfo = p.satuan.map((s, i) =>
      `<span style="font-size:11px;color:#5f5e5a;">${s.nama_satuan}${i===0?' <span style="color:#0F6E56;">(dasar)</span>':''} — ${fmt(s.harga_jual)}</span>`
    ).join('<br>');
    return `<tr>
      <td style="font-weight:500;">${p.nama}</td>
      <td>${p.kategori}</td>
      <td>${Math.round(p.stok)}</td>
      <td>${satuanInfo}</td>
      <td><span class="badge ${stokClass}">${stokLabel}</span></td>
      <td>
        <button class="btn-edit" onclick="editProduk(${p.id})">Edit</button>
        <button class="btn-del"  onclick="hapusProduk(${p.id})">Hapus</button>
      </td>
    </tr>`;
  }).join('');
}

function filterTable() {
  const nama = document.getElementById('filterNama').value.toLowerCase();
  const kat  = document.getElementById('filterKat').value;
  const stok = document.getElementById('filterStok').value;
  const hasil = allProduk.filter(p => {
    if (nama && !p.nama.toLowerCase().includes(nama)) return false;
    if (kat  && String(p.kategori_id) !== kat) return false;
    if (stok === 'aman'    && !(p.stok > (p.stok_minimum||5))) return false;
    if (stok === 'menipis' && !(p.stok > 0 && p.stok <= (p.stok_minimum||5))) return false;
    if (stok === 'habis'   && p.stok > 0) return false;
    return true;
  });
  renderTable(hasil);
}

function buatOptionSatuan(selected = '') {
  return SATUAN_LIST.map(s =>
    `<option value="${s}" ${s === selected ? 'selected' : ''}>${s}</option>`
  ).join('');
}

function tambahBarisSatuan(s = null, index = null) {
  const rows = document.getElementById('satuanRows');
  const isFirst = rows.children.length === 0;
  const div = document.createElement('div');
  div.className = 'satuan-row';
  if (s && s.id) div.dataset.satuanId = s.id;

  div.innerHTML = `
    <select class="form-control s-nama">
      ${buatOptionSatuan(s ? s.nama_satuan : '')}
    </select>
    <input class="form-control s-konv" type="number" min="0.01" step="0.01"
           placeholder="1" value="${s ? Math.round(s.konversi) : (isFirst ? 1 : '')}"
           ${isFirst ? 'readonly' : ''}>
    <input class="form-control s-hbeli" type="number" min="0" placeholder="0"
           value="${s ? Math.round(s.harga_beli).toLocaleString('id-ID') : ''}">
    <input class="form-control s-hjual" type="number" min="0" placeholder="0"
           value="${s ? Math.round(s.harga_jual).toLocaleString('id-ID') : ''}">
    <button class="btn-rm-satuan" onclick="hapusBarisSatuan(this)"
            ${isFirst ? 'disabled' : ''}>&times;</button>
  `;
  rows.appendChild(div);
  div.querySelectorAll('.s-hbeli, .s-hjual').forEach(input => {
  input.addEventListener('input', function() {
    let angka = this.value.replace(/\./g, '');
    if (!isNaN(angka) && angka !== '') {
      this.value = parseInt(angka).toLocaleString('id-ID');
    }
  });
});
}

function hapusBarisSatuan(btn) {
  btn.parentElement.remove();
  // Pastikan baris pertama selalu konversi = 1 dan readonly
  const rows = document.querySelectorAll('#satuanRows .satuan-row');
  rows.forEach((r, i) => {
    const konvInput = r.querySelector('.s-konv');
    const delBtn    = r.querySelector('.btn-rm-satuan');
    if (i === 0) {
      konvInput.value    = 1;
      konvInput.readOnly = true;
      delBtn.disabled    = true;
    } else {
      konvInput.readOnly = false;
      delBtn.disabled    = false;
    }
  });
}

function openModal(data = null) {
  document.getElementById('editId').value    = data ? data.id : '';
  document.getElementById('fNama').value     = data ? data.nama : '';
  document.getElementById('fKategori').value = data ? data.kategori_id : '';
  document.getElementById('fStok').value     = data ? Math.round(data.stok) : '';
  document.getElementById('fStokMin').value  = data ? Math.round(data.stok_minimum || 5) : '5';
  document.getElementById('modalTitle').textContent = data ? 'Edit produk' : 'Tambah produk';
  document.getElementById('satuanRows').innerHTML = '';

  if (data && data.satuan.length) {
    data.satuan.forEach((s, i) => tambahBarisSatuan(s, i));
  } else {
    tambahBarisSatuan(); // baris pertama kosong, konversi 1
  }
  document.getElementById('overlay').classList.add('open');
}

function closeModal() {
  document.getElementById('overlay').classList.remove('open');
}

async function simpanProduk() {
  const id      = document.getElementById('editId').value;
  const nama    = document.getElementById('fNama').value.trim();
  const katId   = document.getElementById('fKategori').value;
  const stok    = Math.round(document.getElementById('fStok').value);
  const stokMin = Math.round(document.getElementById('fStokMin').value);

  if (!nama) { showNotif('Nama produk wajib diisi'); return; }

  const rows   = document.querySelectorAll('#satuanRows .satuan-row');
  const satuan = [];
  for (const r of rows) {
    const nm = r.querySelector('.s-nama').value;
    const kv = r.querySelector('.s-konv').value;
    const hb = r.querySelector('.s-hbeli').value.replace(/\./g, '');
const hj = r.querySelector('.s-hjual').value.replace(/\./g, '');
    if (!nm || !kv || !hj) { showNotif('Isi semua kolom satuan'); return; }
    satuan.push({
      id: r.dataset.satuanId || null,
      nama_satuan: nm,
      konversi: kv,
      harga_beli: hb || 0,
      harga_jual: hj,
    });
  }
  if (!satuan.length) { showNotif('Minimal 1 satuan diperlukan'); return; }
const duplikat = allProduk.find(p => p.nama.toLowerCase() === nama.toLowerCase() && String(p.id) !== String(id));
if (duplikat) { showNotif('Produk "' + nama + '" sudah ada'); return; }
  const res = await fetch('api/produk.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id||null, nama, kategori_id: katId, stok, stok_minimum: stokMin, satuan }),
  });
  const data = await res.json();
  if (data.success) {
    showNotif(id ? 'Produk diperbarui' : 'Produk ditambahkan');
    closeModal();
    loadProduk();
  } else {
    showNotif('Gagal: ' + (data.error || 'Coba lagi'));
  }
}

function editProduk(id) {
  const p = allProduk.find(x => x.id === id);
  if (p) openModal(p);
}

async function hapusProduk(id) {
  if (!confirm('Hapus produk ini? Data transaksi lama tetap tersimpan.')) return;
  const res  = await fetch('api/produk.php', {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  const data = await res.json();
  if (data.success) { showNotif('Produk dihapus'); loadProduk(); }
  else showNotif('Gagal menghapus');
}

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