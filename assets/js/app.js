/**
 * Babe Mobile App Core JavaScript
 * Antarmuka Jual Beli Barang Bekas Localhost & Android
 */

const API_BASE = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '') + '/api';

// State Management
const AppState = {
  currentUser: JSON.parse(localStorage.getItem('babe_user')) || null,
  activeTab: 'home',
  activeCategory: 0,
  searchQuery: '',
  categories: [],
  products: [],
  myProducts: [],
  favorites: [],
  currentProductDetail: null
};

// Inisialisasi Aplikasi Saat DOM Ready
document.addEventListener('DOMContentLoaded', () => {
  initApp();
});

async function initApp() {
  setupNavigation();
  setupSearch();
  setupCurrencyMask();
  await loadCategories();
  await loadProducts();
  await loadFavorites();
  renderCurrentUserBadge();
}

// Navigasi Bottom Bar
function setupNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', () => {
      const targetTab = item.dataset.tab;
      switchTab(targetTab);
    });
  });
}

function switchTab(tabName) {
  AppState.activeTab = tabName;
  
  // Update nav highlight
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.toggle('active', item.dataset.tab === tabName);
  });

  // Toggle Tab Views
  document.querySelectorAll('.tab-view').forEach(view => {
    view.style.display = 'none';
  });

  const activeView = document.getElementById(`view-${tabName}`);
  if (activeView) {
    activeView.style.display = 'block';
  }

  // Load tab-specific data
  if (tabName === 'home') {
    loadProducts();
  } else if (tabName === 'sell') {
    renderSellFormCategories();
  } else if (tabName === 'favorites') {
    renderFavoritesView();
  } else if (tabName === 'profile') {
    renderProfileView();
  }
}

// Dialog Konfirmasi Universal (Standar Industri E-Commerce)
let confirmDialogResolver = null;

function showConfirmDialog({
  title = 'Konfirmasi Tindakan',
  message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  confirmText = 'Ya, Lanjutkan',
  cancelText = 'Batal',
  type = 'danger', // 'danger' | 'warning' | 'primary'
  icon = null
} = {}) {
  return new Promise((resolve) => {
    confirmDialogResolver = resolve;

    const overlay = document.getElementById('confirm-sheet-overlay');
    const iconBox = document.getElementById('confirm-sheet-icon-box');
    const iconEl = document.getElementById('confirm-sheet-icon');
    const titleEl = document.getElementById('confirm-sheet-title');
    const descEl = document.getElementById('confirm-sheet-desc');
    const btnAction = document.getElementById('confirm-sheet-btn-action');
    const btnCancel = document.getElementById('confirm-sheet-btn-cancel');

    if (!overlay) return resolve(false);

    titleEl.textContent = title;
    descEl.textContent = message;
    btnAction.textContent = confirmText;
    btnCancel.textContent = cancelText;

    iconBox.className = `confirm-sheet-icon-box ${type}`;
    btnAction.className = `confirm-sheet-btn-primary ${type}`;

    if (icon) {
      iconEl.className = icon;
    } else {
      if (type === 'danger') {
        iconEl.className = 'ri-delete-bin-line';
      } else if (type === 'warning') {
        iconEl.className = 'ri-alert-line';
      } else {
        iconEl.className = 'ri-question-line';
      }
    }

    btnAction.onclick = () => closeConfirmDialog(true);
    btnCancel.onclick = () => closeConfirmDialog(false);

    overlay.classList.add('active');
  });
}

function closeConfirmDialog(result = false) {
  const overlay = document.getElementById('confirm-sheet-overlay');
  if (overlay) overlay.classList.remove('active');
  if (confirmDialogResolver) {
    confirmDialogResolver(result);
    confirmDialogResolver = null;
  }
}

// Toast Notification Standar Industri Modern (Multi-Style)
let toastTimer = null;
function showToast(message, type = null, duration = 3000) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    document.body.appendChild(toast);
  }

  // Auto-detect tipe jika tidak dispesifikasi
  if (!type) {
    const lower = message.toLowerCase();
    if (lower.includes('berhasil') || lower.includes('sukses') || lower.includes('tersimpan') || lower.includes('dihapus') || lower.includes('🎉')) {
      type = 'success';
    } else if (lower.includes('gagal') || lower.includes('salah') || lower.includes('kesalahan') || lower.includes('error')) {
      type = 'error';
    } else if (lower.includes('harap') || lower.includes('silakan') || lower.includes('peringatan') || lower.includes('wajib') || lower.includes('minimal')) {
      type = 'warning';
    } else {
      type = 'info';
    }
  }

  const iconMap = {
    success: 'ri-checkbox-circle-fill',
    error: 'ri-error-warning-fill',
    danger: 'ri-error-warning-fill',
    warning: 'ri-alert-fill',
    info: 'ri-information-fill'
  };

  const currentIcon = iconMap[type] || 'ri-information-fill';

  // Set class dan HTML
  toast.className = `toast-${type}`;
  toast.innerHTML = `<i class="${currentIcon}"></i><span>${escapeHtml(message)}</span>`;

  // Tampilkan toast
  toast.classList.add('show');

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

// Currency Masking Real-time untuk Form Jual
function setupCurrencyMask() {
  const displayInput = document.getElementById('sell-price-display');
  const rawInput = document.getElementById('sell-price-raw');
  if (!displayInput || !rawInput) return;

  displayInput.addEventListener('input', function () {
    let cleanVal = this.value.replace(/\D/g, '');
    if (!cleanVal) {
      this.value = '';
      rawInput.value = '';
      return;
    }
    const num = parseInt(cleanVal, 10);
    rawInput.value = num;
    this.value = new Intl.NumberFormat('id-ID').format(num);
  });
}

// 1. Kategori API
async function loadCategories() {
  try {
    const res = await fetch(`${API_BASE}/categories/list.php`);
    const json = await res.json();
    if (json.status) {
      AppState.categories = json.data;
      renderCategoriesSlider();
    }
  } catch (err) {
    console.error('Error fetching categories:', err);
  }
}

function renderCategoriesSlider() {
  const container = document.getElementById('categories-slider');
  if (!container) return;

  let html = `
    <div class="cat-chip ${AppState.activeCategory === 0 ? 'active' : ''}" onclick="selectCategory(0)">
      <div class="cat-icon-circle">
        <i class="ri-apps-2-line"></i>
      </div>
      <span class="cat-name">Semua</span>
    </div>
  `;

  AppState.categories.forEach(cat => {
    const isActive = AppState.activeCategory === cat.id ? 'active' : '';
    html += `
      <div class="cat-chip ${isActive}" onclick="selectCategory(${cat.id})">
        <div class="cat-icon-circle">
          <i class="${cat.icon || 'ri-price-tag-3-line'}"></i>
        </div>
        <span class="cat-name">${cat.name}</span>
      </div>
    `;
  });

  container.innerHTML = html;
}

function selectCategory(catId) {
  AppState.activeCategory = catId;
  renderCategoriesSlider();
  loadProducts();
}

// 2. Produk & Katalog API
async function loadProducts(extraParams = {}) {
  const container = document.getElementById('product-grid');
  if (!container) return;

  container.innerHTML = `
    <div class="skeleton-card">
      <div class="skeleton-box skeleton-card-img"></div>
      <div class="skeleton-card-body">
        <div class="skeleton-box" style="width: 45%; height: 14px;"></div>
        <div class="skeleton-box" style="width: 85%; height: 16px;"></div>
        <div class="skeleton-box" style="width: 60%; height: 18px; margin-top: 4px;"></div>
      </div>
    </div>
    <div class="skeleton-card">
      <div class="skeleton-box skeleton-card-img"></div>
      <div class="skeleton-card-body">
        <div class="skeleton-box" style="width: 45%; height: 14px;"></div>
        <div class="skeleton-box" style="width: 85%; height: 16px;"></div>
        <div class="skeleton-box" style="width: 60%; height: 18px; margin-top: 4px;"></div>
      </div>
    </div>
    <div class="skeleton-card">
      <div class="skeleton-box skeleton-card-img"></div>
      <div class="skeleton-card-body">
        <div class="skeleton-box" style="width: 45%; height: 14px;"></div>
        <div class="skeleton-box" style="width: 85%; height: 16px;"></div>
        <div class="skeleton-box" style="width: 60%; height: 18px; margin-top: 4px;"></div>
      </div>
    </div>
    <div class="skeleton-card">
      <div class="skeleton-box skeleton-card-img"></div>
      <div class="skeleton-card-body">
        <div class="skeleton-box" style="width: 45%; height: 14px;"></div>
        <div class="skeleton-box" style="width: 85%; height: 16px;"></div>
        <div class="skeleton-box" style="width: 60%; height: 18px; margin-top: 4px;"></div>
      </div>
    </div>
  `;

  try {
    const params = new URLSearchParams();
    if (AppState.activeCategory > 0) {
      params.append('category_id', AppState.activeCategory);
    }
    if (AppState.searchQuery) {
      params.append('search', AppState.searchQuery);
    }
    for (const key in extraParams) {
      params.append(key, extraParams[key]);
    }

    const res = await fetch(`${API_BASE}/products/list.php?${params.toString()}`);
    const json = await res.json();

    if (json.status && json.data.length > 0) {
      AppState.products = json.data;
      renderProductCards(json.data);
    } else {
      container.innerHTML = `
        <div style="grid-column: span 2; text-align: center; padding: 50px 20px; color: var(--gray-400);">
          <i class="ri-inbox-line" style="font-size: 3rem;"></i>
          <p style="margin-top: 10px; font-weight: 600; color: var(--gray-600);">Belum ada barang bekas di sini</p>
          <p style="font-size: 0.8rem;">Coba cari kata kunci lain atau jadilah yang pertama menjual!</p>
        </div>
      `;
    }
  } catch (err) {
    container.innerHTML = `
      <div style="grid-column: span 2; text-align: center; padding: 36px 16px; background: #fff; border: 1px dashed var(--gray-200); border-radius: 16px;">
        <i class="ri-wifi-off-line" style="font-size: 2.5rem; color: var(--danger); margin-bottom: 8px; display: block;"></i>
        <p style="font-size: 0.95rem; font-weight: 700; color: var(--gray-800);">Gagal Menghubungkan ke Server</p>
        <p style="font-size: 0.78rem; color: var(--gray-500); max-width: 260px; margin: 4px auto 14px;">
          Pastikan modul Apache & MySQL di XAMPP Anda aktif.
        </p>
        <button class="btn-primary" style="width: auto; padding: 8px 18px; font-size: 0.82rem; margin: 0 auto; display: inline-flex; align-items: center; gap: 6px;" onclick="loadProducts()">
          <i class="ri-refresh-line"></i> Coba Muat Ulang
        </button>
      </div>
    `;
  }
}

function renderProductCards(products) {
  const container = document.getElementById('product-grid');
  if (!container) return;

  const favIds = AppState.favorites.map(f => f.product_id || f.id);

  container.innerHTML = products.map(prod => {
    const isFav = favIds.includes(prod.id);
    const condLabel = formatCondition(prod.condition_type);
    const formattedPrice = formatRupiah(prod.price);
    const isSold = prod.status === 'terjual';

    return `
      <div class="product-card" onclick="openProductDetail(${prod.id})">
        <div class="card-img-container">
          <img src="${prod.primary_image}" alt="${prod.title}" class="card-img" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80'">
          <span class="card-badge-cond">${condLabel}</span>
          <button class="card-favorite-btn ${isFav ? 'favorited' : ''}" onclick="handleFavClick(event, ${prod.id})">
            <i class="${isFav ? 'ri-heart-fill' : 'ri-heart-line'}"></i>
          </button>
          ${isSold ? '<div class="card-sold-overlay">TERJUAL</div>' : ''}
        </div>
        <div class="card-info">
          <div class="card-price">${formattedPrice}</div>
          <div class="card-title">${escapeHtml(prod.title)}</div>
          <div class="card-meta">
            <span class="card-location"><i class="ri-map-pin-line"></i> ${escapeHtml(prod.location)}</span>
            <span><i class="ri-eye-line"></i> ${prod.views_count}</span>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// 3. Detail Barang & Kontak WhatsApp
async function openProductDetail(productId) {
  const modal = document.getElementById('detail-modal');
  const modalBody = document.getElementById('detail-modal-body');
  if (!modal || !modalBody) return;

  modalBody.innerHTML = `
    <div style="text-align: center; padding: 40px; color: var(--gray-400);">
      <i class="ri-loader-4-line ri-spin" style="font-size: 2rem;"></i>
    </div>
  `;
  modal.classList.add('active');

  try {
    const res = await fetch(`${API_BASE}/products/detail.php?id=${productId}`);
    const json = await res.json();
    if (!json.status) {
      modalBody.innerHTML = `<p style="color:var(--danger); padding:20px;">${json.message}</p>`;
      return;
    }

    const p = json.data;
    AppState.currentProductDetail = p;

    // Persiapkan WhatsApp Link
    const cleanPhone = p.seller_phone.replace(/\D/g, '').replace(/^0/, '62');
    const waText = encodeURIComponent(
      `Halo Kak ${p.seller_name}, saya melihat barang bekas *${p.title}* seharga *${formatRupiah(p.price)}* di aplikasi Babe. Apakah barang ini masih tersedia?`
    );
    const waUrl = `https://wa.me/${cleanPhone}?text=${waText}`;

    // Render Carousel Foto
    const primaryImg = (p.images && p.images[0]) ? p.images[0].image_path : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80';

    modalBody.innerHTML = `
      <div class="detail-gallery">
        <img src="${primaryImg}" class="detail-img" alt="${escapeHtml(p.title)}" onerror="this.src='https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80'">
      </div>

      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 8px;">
        <span class="brand-badge" style="background:#e0e7ff; color:var(--primary); font-size:0.75rem;">
          <i class="${p.category_icon || 'ri-price-tag-3-line'}"></i> ${escapeHtml(p.category_name)}
        </span>
        <span style="font-size:0.75rem; color:var(--gray-400);">
          <i class="ri-eye-line"></i> Dilihat ${p.views_count} kali
        </span>
      </div>

      <h2 style="font-size:1.25rem; font-weight:800; color:var(--gray-800); margin-bottom: 6px;">
        ${escapeHtml(p.title)}
      </h2>

      <div style="font-size:1.4rem; font-weight:800; color:var(--primary); margin-bottom: 12px;">
        ${formatRupiah(p.price)}
      </div>

      <div style="display:flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
        <span style="background:var(--gray-100); padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;">
          <i class="ri-shield-check-line" style="color:var(--secondary)"></i> Kondisi: ${formatCondition(p.condition_type)}
        </span>
        <span style="background:var(--gray-100); padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;">
          <i class="ri-map-pin-line" style="color:var(--primary)"></i> Lokasi: ${escapeHtml(p.location)}
        </span>
      </div>

      <div style="margin-bottom: 18px;">
        <h4 style="font-size:0.9rem; font-weight:700; margin-bottom: 6px;">Deskripsi Barang</h4>
        <p style="font-size:0.88rem; color:var(--gray-600); line-height:1.6; white-space:pre-line;">
          ${escapeHtml(p.description)}
        </p>
      </div>

      <!-- Kartu Penjual -->
      <div class="seller-card">
        <div style="display:flex; align-items:center; gap: 10px;">
          <div style="width:44px; height:44px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
            ${p.seller_name.charAt(0).toUpperCase()}
          </div>
          <div>
            <div style="font-weight:700; font-size:0.92rem;">${escapeHtml(p.seller_name)}</div>
            <div style="font-size:0.75rem; color:var(--gray-400);">Penjual di ${escapeHtml(p.seller_city || p.location)}</div>
          </div>
        </div>
        <div style="font-size:0.75rem; color:var(--secondary); font-weight:600;">
          <i class="ri-checkbox-circle-fill"></i> Terverifikasi
        </div>
      </div>

      <!-- Tombol Aksi WhatsApp -->
      <div style="margin-top: 14px;">
        ${p.status === 'terjual' ? `
          <div style="background:#fee2e2; color:#ef4444; padding:12px; border-radius:12px; text-align:center; font-weight:700;">
            Barang ini sudah terjual
          </div>
        ` : `
          <a href="${waUrl}" target="_blank" class="btn-whatsapp">
            <i class="ri-whatsapp-line" style="font-size: 1.3rem;"></i>
            Chat Penjual via WhatsApp
          </a>
        `}
      </div>
    `;

  } catch (err) {
    modalBody.innerHTML = `<p style="color:var(--danger); padding:20px;">Gagal memuat detail barang.</p>`;
  }
}

function closeDetailModal() {
  const modal = document.getElementById('detail-modal');
  if (modal) modal.classList.remove('active');
}

// 4. Pasang Iklan / Jual Barang
function renderSellFormCategories() {
  const select = document.getElementById('sell-category');
  if (!select) return;

  select.innerHTML = '<option value="">-- Pilih Kategori --</option>' +
    AppState.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

  // Default kota sesuai profil
  const locInput = document.getElementById('sell-location');
  if (locInput) {
    locInput.value = (AppState.currentUser && AppState.currentUser.city) ? AppState.currentUser.city : '';
  }
}

async function handleSellSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = document.getElementById('sell-submit-btn');

  if (!AppState.currentUser || !AppState.currentUser.id) {
    showToast('Silakan masuk atau daftar akun terlebih dahulu untuk memasang iklan.', 'warning');
    openAuthModal('login');
    return;
  }

  // Validasi Harga Raw
  const rawPriceInput = document.getElementById('sell-price-raw');
  const priceVal = rawPriceInput ? parseFloat(rawPriceInput.value) : 0;
  if (!priceVal || priceVal < 1000) {
    showToast('Harga jual wajib diisi minimal Rp 1.000.', 'warning');
    const disp = document.getElementById('sell-price-display');
    if (disp) disp.focus();
    return;
  }

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyimpan...';

  try {
    const formData = new FormData(form);
    formData.append('user_id', AppState.currentUser.id);
    formData.set('price', priceVal);

    const res = await fetch(`${API_BASE}/products/create.php`, {
      method: 'POST',
      body: formData
    });

    const json = await res.json();
    if (json.status) {
      showToast('🎉 Iklan barang bekas berhasil dipasang!', 'success');
      form.reset();
      const displayInput = document.getElementById('sell-price-display');
      if (displayInput) displayInput.value = '';
      if (rawPriceInput) rawPriceInput.value = '';
      document.getElementById('sell-image-preview').style.display = 'none';
      switchTab('home');
      await loadProducts();
    } else {
      showToast('Gagal: ' + json.message, 'error');
    }
  } catch (err) {
    showToast('Terjadi kesalahan koneksi server.', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="ri-upload-cloud-line"></i> Pasang Iklan Sekarang';
  }
}

// Preview Gambar saat dipilih di Form Jual
function handleImageSelect(event) {
  const file = event.target.files[0];
  const preview = document.getElementById('sell-image-preview');

  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('Ukuran foto terlalu besar. Maksimal 5MB.', 'warning');
      event.target.value = '';
      if (preview) preview.style.display = 'none';
      return;
    }

    if (preview) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  }
}

// 5. Wishlist & Favorit
async function loadFavorites() {
  if (!AppState.currentUser || !AppState.currentUser.id) return;
  try {
    const res = await fetch(`${API_BASE}/favorites/list.php?user_id=${AppState.currentUser.id}`);
    const json = await res.json();
    if (json.status) {
      AppState.favorites = json.data;
    }
  } catch (err) {
    console.error('Error load favorites:', err);
  }
}

async function handleFavClick(event, productId) {
  event.stopPropagation(); // Jangan buka detail saat tap icon love
  if (!AppState.currentUser || !AppState.currentUser.id) {
    showToast('Silakan masuk atau daftar akun untuk menyimpan favorit.');
    openAuthModal('login');
    return;
  }

  try {
    const res = await fetch(`${API_BASE}/favorites/toggle.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: AppState.currentUser.id,
        product_id: productId
      })
    });
    const json = await res.json();
    if (json.status) {
      showToast(json.message);
      await loadFavorites();
      if (AppState.activeTab === 'home') {
        renderProductCards(AppState.products);
      } else if (AppState.activeTab === 'favorites') {
        renderFavoritesView();
      }
    }
  } catch (err) {
    showToast('Gagal mengubah favorit.');
  }
}

function renderFavoritesView() {
  const container = document.getElementById('favorites-grid');
  if (!container) return;

  if (AppState.favorites.length === 0) {
    container.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: var(--gray-400);">
        <i class="ri-heart-line" style="font-size: 3rem;"></i>
        <p style="margin-top: 10px; font-weight: 600; color: var(--gray-600);">Belum Ada Barang Favorit</p>
        <p style="font-size: 0.8rem;">Tap ikon hati pada barang yang Anda incar untuk menyimpannya di sini.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = `
    <div class="product-grid">
      ${AppState.favorites.map(prod => `
        <div class="product-card" onclick="openProductDetail(${prod.id})">
          <div class="card-img-container">
            <img src="${prod.primary_image}" alt="${prod.title}" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80'">
            <button class="card-favorite-btn favorited" onclick="handleFavClick(event, ${prod.id})">
              <i class="ri-heart-fill"></i>
            </button>
          </div>
          <div class="card-info">
            <div class="card-price">${formatRupiah(prod.price)}</div>
            <div class="card-title">${escapeHtml(prod.title)}</div>
            <div class="card-meta">
              <span class="card-location"><i class="ri-map-pin-line"></i> ${escapeHtml(prod.location)}</span>
              <span><i class="ri-user-line"></i> ${escapeHtml(prod.seller_name)}</span>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

// 6. Profil & Iklan Saya
async function renderProfileView() {
  const guestCard = document.getElementById('profile-guest-card');
  const userCard = document.getElementById('profile-user-card');
  const myAdsSection = document.getElementById('profile-my-ads-section');
  const user = AppState.currentUser;

  if (!user || !user.id) {
    if (guestCard) guestCard.style.display = 'block';
    if (userCard) userCard.style.display = 'none';
    if (myAdsSection) myAdsSection.style.display = 'none';
    return;
  }

  if (guestCard) guestCard.style.display = 'none';
  if (userCard) userCard.style.display = 'block';
  if (myAdsSection) myAdsSection.style.display = 'block';

  const nameEl = document.getElementById('profile-name');
  const emailEl = document.getElementById('profile-email');
  const phoneEl = document.getElementById('profile-phone');
  const cityEl = document.getElementById('profile-city');
  const avatarTextEl = document.getElementById('profile-avatar-text');

  if (nameEl) nameEl.textContent = user.name;
  if (emailEl) emailEl.textContent = user.email || '-';
  if (phoneEl) phoneEl.innerHTML = `<i class="ri-whatsapp-line" style="color:#22c55e;"></i> ${escapeHtml(user.phone || '-')}`;
  if (cityEl) cityEl.innerHTML = `<i class="ri-map-pin-line" style="color:var(--primary);"></i> ${escapeHtml(user.city || 'Indonesia')}`;
  if (avatarTextEl) avatarTextEl.textContent = (user.name || 'U').charAt(0).toUpperCase();

  // Render Role Badge
  const roleBadgeEl = document.getElementById('profile-role-badge');
  if (roleBadgeEl) {
    const role = user.role || 'pembeli';
    roleBadgeEl.className = `demo-role-badge badge-${role}`;
    const roleLabels = {
      pembeli: '🛒 PEMBELI AKTIF',
      penjual: '🏪 PENJUAL TERVERIFIKASI',
      logistik: '🚚 MITRA LOGISTIK & COD',
      admin: '🛡️ ADMIN MARKETPLACE'
    };
    roleBadgeEl.textContent = roleLabels[role] || role.toUpperCase();
  }

  // Load stats from profile API
  loadProfileStats();
  await loadMyProducts();
}

async function loadProfileStats() {
  if (!AppState.currentUser || !AppState.currentUser.id) return;
  try {
    const res = await fetch(`${API_BASE}/auth/profile.php?user_id=${AppState.currentUser.id}`);
    const json = await res.json();
    if (json.status && json.data && json.data.stats) {
      const stats = json.data.stats;
      const totalEl = document.getElementById('profile-stat-total');
      const activeEl = document.getElementById('profile-stat-active');
      const soldEl = document.getElementById('profile-stat-sold');
      if (totalEl) totalEl.textContent = stats.total_items || 0;
      if (activeEl) activeEl.textContent = stats.active_items || 0;
      if (soldEl) soldEl.textContent = stats.sold_items || 0;
    }
  } catch (e) {
    console.error('Failed to load profile stats', e);
  }
}

async function loadMyProducts() {
  const container = document.getElementById('my-products-list');
  if (!container || !AppState.currentUser) return;

  container.innerHTML = '<p style="text-align:center; padding:20px; color:var(--gray-400);">Memuat barang Anda...</p>';

  try {
    const res = await fetch(`${API_BASE}/products/my_products.php?user_id=${AppState.currentUser.id}`);
    const json = await res.json();
    if (json.status && json.data.length > 0) {
      AppState.myProducts = json.data;
      container.innerHTML = json.data.map(p => `
        <div style="background:#fff; border:1px solid var(--gray-200); border-radius:12px; padding:12px; margin-bottom:10px; display:flex; gap:12px; align-items:center;">
          <img src="${p.primary_image}" style="width:65px; height:65px; object-fit:cover; border-radius:8px;" onerror="this.src='https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80'">
          <div style="flex:1;">
            <div style="font-weight:700; font-size:0.88rem; color:var(--gray-800);">${escapeHtml(p.title)}</div>
            <div style="font-weight:800; font-size:0.92rem; color:var(--primary); margin:2px 0;">${formatRupiah(p.price)}</div>
            <span class="brand-badge" style="background:${p.status === 'tersedia' ? '#dcfce7' : '#fee2e2'}; color:${p.status === 'tersedia' ? '#166534' : '#991b1b'};">
              ${p.status.toUpperCase()}
            </span>
          </div>
          <div style="display:flex; flex-direction:column; gap:4px;">
            <button class="btn-secondary" style="padding:5px 8px; font-size:0.75rem;" onclick="toggleProductStatus(${p.id}, '${p.status === 'tersedia' ? 'terjual' : 'tersedia'}')">
              ${p.status === 'tersedia' ? 'Tandai Terjual' : 'Jual Lagi'}
            </button>
            <button class="btn-secondary" style="padding:5px 8px; font-size:0.75rem; color:var(--danger);" onclick="deleteProduct(${p.id})">
              Hapus
            </button>
          </div>
        </div>
      `).join('');
    } else {
      const role = AppState.currentUser ? (AppState.currentUser.role || 'penjual') : 'penjual';
      if (role === 'pembeli') {
        container.innerHTML = `
          <div style="text-align:center; padding:24px 16px; background:#f0f9ff; border:1px dashed #bae6fd; border-radius:14px;">
            <i class="ri-shopping-bag-3-line" style="font-size:2.2rem; color:#0284c7; margin-bottom:6px; display:block;"></i>
            <p style="font-size:0.9rem; font-weight:700; color:#0369a1;">Akun Pembeli Aktif</p>
            <p style="font-size:0.75rem; color:#075985; margin:4px 0 14px; line-height:1.4;">
              Anda masuk sebagai <strong>Pembeli</strong>. Anda dapat menjelajah katalog barang bekas, menyimpan wishlist favorit, dan menghubungi penjual via WhatsApp.
            </p>
            <div style="display:flex; gap:8px; justify-content:center;">
              <button class="btn-primary" style="font-size:0.78rem; padding:8px 12px; width:auto;" onclick="switchTab('home')">
                <i class="ri-search-line"></i> Jelajah Barang Bekas
              </button>
              <button class="btn-secondary" style="font-size:0.78rem; padding:8px 12px; width:auto;" onclick="switchTab('sell')">
                <i class="ri-add-line"></i> Pasang Iklan Jual
              </button>
            </div>
          </div>
        `;
      } else if (role === 'logistik') {
        container.innerHTML = `
          <div style="text-align:center; padding:24px 16px; background:#fffbeb; border:1px dashed #fde68a; border-radius:14px;">
            <i class="ri-truck-line" style="font-size:2.2rem; color:#d97706; margin-bottom:6px; display:block;"></i>
            <p style="font-size:0.9rem; font-weight:700; color:#92400e;">Penyedia Logistik & Kurir COD</p>
            <p style="font-size:0.75rem; color:#78350f; margin:4px 0 14px; line-height:1.4;">
              Mitra kurir siaga penjemputan barang dan inspeksi fisik COD area <strong>Jabodetabek & Bandung</strong>.
            </p>
            <button class="btn-secondary" style="font-size:0.78rem; padding:8px 14px; width:auto; border-color:#d97706; color:#92400e;" onclick="showToast('Sistem logistik aktif: 12 titik COD siap melayani penjemputan.')">
              <i class="ri-map-pin-range-line"></i> Cek Titik COD Siaga
            </button>
          </div>
        `;
      } else if (role === 'admin') {
        container.innerHTML = `
          <div style="text-align:center; padding:24px 16px; background:#faf5ff; border:1px dashed #e9d5ff; border-radius:14px;">
            <i class="ri-shield-check-line" style="font-size:2.2rem; color:#9333ea; margin-bottom:6px; display:block;"></i>
            <p style="font-size:0.9rem; font-weight:700; color:#6b21a8;">Pusat Kontrol Admin Marketplace</p>
            <p style="font-size:0.75rem; color:#581c87; margin:4px 0 14px; line-height:1.4;">
              Akses moderasi transaksi, verifikasi penjual, audit basis data platform, dan pengawasan iklan barang bekas.
            </p>
            <button class="btn-primary" style="font-size:0.78rem; padding:8px 14px; width:auto; background:#9333ea; border-color:#9333ea;" onclick="showToast('Mode Admin Aktif: Seluruh kueri dan status iklan dalam pengawasan')">
              <i class="ri-settings-4-line"></i> Status Moderasi Normal
            </button>
          </div>
        `;
      } else {
        container.innerHTML = `
          <div style="text-align:center; padding:30px; color:var(--gray-400); background:#fff; border:1px dashed var(--gray-200); border-radius:12px;">
            <i class="ri-inbox-line" style="font-size:2rem; color:var(--gray-400); margin-bottom:6px; display:block;"></i>
            <p style="font-size:0.85rem; font-weight:600;">Belum ada iklan barang</p>
            <p style="font-size:0.75rem; margin-top:4px;">Mulai pasang iklan barang bekas Anda sekarang.</p>
            <button class="btn-primary" style="margin-top:12px; font-size:0.8rem; padding:8px 14px; width:auto;" onclick="switchTab('sell')">
              <i class="ri-add-line"></i> Pasang Iklan Sekarang
            </button>
          </div>
        `;
      }
    }
  } catch (err) {
    container.innerHTML = '<p style="color:var(--danger); text-align:center;">Gagal memuat barang Anda.</p>';
  }
}

async function toggleProductStatus(productId, nextStatus) {
  try {
    const res = await fetch(`${API_BASE}/products/update_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        product_id: productId,
        user_id: AppState.currentUser.id,
        status: nextStatus
      })
    });
    const json = await res.json();
    if (json.status) {
      showToast(json.message);
      await loadMyProducts();
      await loadProducts();
      loadProfileStats();
    } else {
      showToast(json.message);
    }
  } catch (err) {
    showToast('Gagal mengubah status barang.');
  }
}

async function deleteProduct(productId) {
  const confirmed = await showConfirmDialog({
    title: 'Hapus Iklan Barang?',
    message: 'Iklan barang bekas ini akan dihapus permanen dari katalog Babe dan tidak dapat dipulihkan.',
    confirmText: 'Ya, Hapus Iklan',
    cancelText: 'Batalkan',
    type: 'danger',
    icon: 'ri-delete-bin-6-line'
  });
  if (!confirmed) return;

  try {
    const res = await fetch(`${API_BASE}/products/delete.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        product_id: productId,
        user_id: AppState.currentUser.id
      })
    });
    const json = await res.json();
    if (json.status) {
      showToast('Iklan barang berhasil dihapus.', 'success');
      await loadMyProducts();
      await loadProducts();
      loadProfileStats();
    } else {
      showToast(json.message, 'error');
    }
  } catch (err) {
    showToast('Gagal menghapus produk.', 'error');
  }
}

// Standar Akun Demo Marketplace / Online Shop
const DEMO_ACCOUNTS = {
  pembeli: {
    id: 5,
    name: 'Rian Pratama',
    email: 'rian.pembeli@babe.id',
    phone: '085712345678',
    city: 'Surabaya',
    role: 'pembeli',
    roleLabel: '🛒 Pembeli Aktif'
  },
  penjual: {
    id: 1,
    name: 'Budi Santoso',
    email: 'budi@example.com',
    phone: '081234567890',
    city: 'Jakarta Selatan',
    role: 'penjual',
    roleLabel: '🏪 Penjual Terverifikasi'
  },
  logistik: {
    id: 6,
    name: 'Joko Handoko (Babe Express)',
    email: 'joko.logistik@babe.id',
    phone: '087811223344',
    city: 'Jabodetabek & Bandung',
    role: 'logistik',
    roleLabel: '🚚 Mitra Logistik & COD'
  },
  admin: {
    id: 7,
    name: 'Admin Babe Support',
    email: 'admin@babe.id',
    phone: '081199887766',
    city: 'Jakarta Pusat (HQ)',
    role: 'admin',
    roleLabel: '🛡️ Admin Marketplace'
  }
};

// 1-Klik Login Akun Demo (Standar Online Shop)
function loginAsDemo(roleKey) {
  let user = null;
  if (typeof roleKey === 'string' && DEMO_ACCOUNTS[roleKey]) {
    user = { ...DEMO_ACCOUNTS[roleKey] };
  } else if (roleKey === 1) {
    user = { ...DEMO_ACCOUNTS['penjual'] };
  } else if (roleKey === 2) {
    user = {
      id: 2,
      name: 'Siti Rahma',
      email: 'siti@example.com',
      phone: '089876543210',
      city: 'Bandung',
      role: 'penjual',
      roleLabel: '🏪 Penjual Terverifikasi'
    };
  } else if (DEMO_ACCOUNTS[roleKey]) {
    user = { ...DEMO_ACCOUNTS[roleKey] };
  }

  if (!user) return;

  user.token = btoa(user.id + ':' + Date.now() + ':demo_auth_token');
  AppState.currentUser = user;
  localStorage.setItem('babe_user', JSON.stringify(user));

  closeAuthModal();
  renderCurrentUserBadge();
  renderProfileView();
  loadFavorites();
  showToast(`Berhasil masuk sebagai ${user.roleLabel || user.name}!`);
}

// Alias untuk kompatibilitas
function switchDemoUser(idOrRole) {
  loginAsDemo(idOrRole);
}

function renderCurrentUserBadge() {
  const badgeName = document.getElementById('user-header-name');
  const badgeIcon = document.getElementById('user-header-icon');
  if (!badgeName) return;

  if (AppState.currentUser && AppState.currentUser.name) {
    const role = AppState.currentUser.role || 'pembeli';
    const roleIcons = {
      pembeli: { icon: 'ri-shopping-bag-3-fill', color: '#0284c7' },
      penjual: { icon: 'ri-store-2-fill', color: '#16a34a' },
      logistik: { icon: 'ri-truck-fill', color: '#d97706' },
      admin: { icon: 'ri-shield-check-fill', color: '#9333ea' }
    };
    
    badgeName.textContent = AppState.currentUser.name.split(' ')[0];
    if (badgeIcon) {
      const cfg = roleIcons[role] || { icon: 'ri-user-smile-fill', color: 'var(--primary)' };
      badgeIcon.className = cfg.icon;
      badgeIcon.style.color = cfg.color;
    }
  } else {
    badgeName.textContent = 'Masuk';
    if (badgeIcon) {
      badgeIcon.className = 'ri-user-line';
      badgeIcon.style.color = 'var(--gray-600)';
    }
  }
}

function handleHeaderUserClick() {
  if (AppState.currentUser && AppState.currentUser.id) {
    switchTab('profile');
  } else {
    openAuthModal('login');
  }
}

// Auth Modal Management
function openAuthModal(tab = 'login') {
  const modal = document.getElementById('auth-modal');
  if (modal) {
    modal.classList.add('active');
    switchAuthTab(tab);
  }
}

function closeAuthModal() {
  const modal = document.getElementById('auth-modal');
  if (modal) modal.classList.remove('active');
}

function switchAuthTab(tab) {
  const btnLogin = document.getElementById('auth-tab-btn-login');
  const btnReg = document.getElementById('auth-tab-btn-register');
  const paneLogin = document.getElementById('auth-tab-login');
  const paneReg = document.getElementById('auth-tab-register');

  if (tab === 'login') {
    if (btnLogin) btnLogin.classList.add('active');
    if (btnReg) btnReg.classList.remove('active');
    if (paneLogin) paneLogin.style.display = 'block';
    if (paneReg) paneReg.style.display = 'none';
  } else {
    if (btnLogin) btnLogin.classList.remove('active');
    if (btnReg) btnReg.classList.add('active');
    if (paneLogin) paneLogin.style.display = 'none';
    if (paneReg) paneReg.style.display = 'block';
  }
}

// Handler Submit Login
async function handleLoginSubmit(event) {
  event.preventDefault();
  const emailInput = document.getElementById('login-email');
  const passInput = document.getElementById('login-password');
  const submitBtn = document.getElementById('btn-login-submit');

  const email = emailInput ? emailInput.value.trim() : '';
  const password = passInput ? passInput.value.trim() : '';

  if (!email || !password) {
    showToast('Harap isi email dan kata sandi.');
    return;
  }

  const originalText = submitBtn ? submitBtn.innerHTML : '';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Memproses...';
  }

  try {
    const res = await fetch(`${API_BASE}/auth/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const json = await res.json();

    if (json.status && json.data) {
      AppState.currentUser = json.data;
      localStorage.setItem('babe_user', JSON.stringify(json.data));
      closeAuthModal();
      renderCurrentUserBadge();
      if (emailInput) emailInput.value = '';
      if (passInput) passInput.value = '';
      showToast(`Login berhasil! Selamat datang, ${json.data.name.split(' ')[0]}`);
      renderProfileView();
      loadFavorites();
    } else {
      showToast(json.message || 'Login gagal. Periksa kembali email dan kata sandi.');
    }
  } catch (err) {
    console.error(err);
    showToast('Terjadi kesalahan koneksi server.');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }
}

// Handler Submit Register (Pencatatan Akun Baru)
async function handleRegisterSubmit(event) {
  event.preventDefault();
  const nameInput = document.getElementById('reg-name');
  const phoneInput = document.getElementById('reg-phone');
  const cityInput = document.getElementById('reg-city');
  const emailInput = document.getElementById('reg-email');
  const passInput = document.getElementById('reg-password');
  const submitBtn = document.getElementById('btn-register-submit');

  const name = nameInput ? nameInput.value.trim() : '';
  const phone = phoneInput ? phoneInput.value.trim() : '';
  const city = cityInput ? cityInput.value.trim() : 'Jakarta';
  const email = emailInput ? emailInput.value.trim() : '';
  const password = passInput ? passInput.value.trim() : '';

  if (!name || !phone || !email || !password) {
    showToast('Harap lengkapi semua kolom wajib.');
    return;
  }

  if (password.length < 6) {
    showToast('Kata sandi minimal 6 karakter.');
    return;
  }

  const originalText = submitBtn ? submitBtn.innerHTML : '';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mencatat Akun...';
  }

  try {
    const res = await fetch(`${API_BASE}/auth/register.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, phone, city, email, password })
    });
    const json = await res.json();

    if (json.status && json.data) {
      AppState.currentUser = json.data;
      localStorage.setItem('babe_user', JSON.stringify(json.data));
      closeAuthModal();
      renderCurrentUserBadge();
      
      if (nameInput) nameInput.value = '';
      if (phoneInput) phoneInput.value = '';
      if (cityInput) cityInput.value = '';
      if (emailInput) emailInput.value = '';
      if (passInput) passInput.value = '';

      showToast(`Pencatatan akun berhasil! Selamat datang, ${json.data.name.split(' ')[0]}`);
      renderProfileView();
      loadFavorites();
    } else {
      showToast(json.message || 'Pendaftaran akun gagal.');
    }
  } catch (err) {
    console.error(err);
    showToast('Terjadi kesalahan koneksi saat registrasi.');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }
}

// Handler Logout dengan Confirm Bottom Sheet Dialog
async function handleLogout() {
  const confirmed = await showConfirmDialog({
    title: 'Keluar dari Akun Babe?',
    message: 'Apakah Anda yakin ingin keluar? Iklan dan barang favorit Anda akan tetap aman tersimpan.',
    confirmText: 'Ya, Keluar Akun',
    cancelText: 'Tetap Masuk',
    type: 'danger',
    icon: 'ri-logout-box-r-line'
  });
  if (!confirmed) return;

  AppState.currentUser = null;
  localStorage.removeItem('babe_user');
  AppState.myProducts = [];
  AppState.favorites = [];
  renderCurrentUserBadge();
  renderProfileView();
  showToast('Anda telah berhasil keluar (Logout).', 'info');
}

// Edit Profile Modal
function openEditProfileModal() {
  const user = AppState.currentUser;
  if (!user) {
    openAuthModal('login');
    return;
  }
  const modal = document.getElementById('edit-profile-modal');
  const nameInput = document.getElementById('edit-name');
  const phoneInput = document.getElementById('edit-phone');
  const cityInput = document.getElementById('edit-city');

  if (nameInput) nameInput.value = user.name || '';
  if (phoneInput) phoneInput.value = user.phone || '';
  if (cityInput) cityInput.value = user.city || '';

  if (modal) modal.classList.add('active');
}

function closeEditProfileModal() {
  const modal = document.getElementById('edit-profile-modal');
  if (modal) modal.classList.remove('active');
}

async function handleEditProfileSubmit(event) {
  event.preventDefault();
  if (!AppState.currentUser) return;

  const nameInput = document.getElementById('edit-name');
  const phoneInput = document.getElementById('edit-phone');
  const cityInput = document.getElementById('edit-city');
  const submitBtn = document.getElementById('btn-edit-profile-submit');

  const name = nameInput ? nameInput.value.trim() : '';
  const phone = phoneInput ? phoneInput.value.trim() : '';
  const city = cityInput ? cityInput.value.trim() : '';

  if (!name || !phone) {
    showToast('Nama dan nomor WhatsApp wajib diisi.');
    return;
  }

  const originalText = submitBtn ? submitBtn.innerHTML : '';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyimpan...';
  }

  try {
    const res = await fetch(`${API_BASE}/auth/profile.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: AppState.currentUser.id,
        name,
        phone,
        city
      })
    });
    const json = await res.json();

    if (json.status) {
      AppState.currentUser.name = name;
      AppState.currentUser.phone = phone;
      AppState.currentUser.city = city;
      localStorage.setItem('babe_user', JSON.stringify(AppState.currentUser));
      closeEditProfileModal();
      renderCurrentUserBadge();
      renderProfileView();
      showToast('Profil akun berhasil diperbarui!');
    } else {
      showToast(json.message || 'Gagal memperbarui profil.');
    }
  } catch (err) {
    console.error(err);
    showToast('Terjadi kesalahan koneksi server.');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }
}

// 7. Search & Filter
function setupSearch() {
  const searchInput = document.getElementById('search-input');
  if (!searchInput) return;

  let debounceTimer;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      AppState.searchQuery = e.target.value.trim();
      loadProducts();
    }, 400);
  });
}

function openFilterModal() {
  const modal = document.getElementById('filter-modal');
  if (modal) modal.classList.add('active');
}

function closeFilterModal() {
  const modal = document.getElementById('filter-modal');
  if (modal) modal.classList.remove('active');
}

function applyFilters() {
  const cond = document.getElementById('filter-condition').value;
  const sort = document.getElementById('filter-sort').value;
  const minPrice = document.getElementById('filter-min-price').value;
  const maxPrice = document.getElementById('filter-max-price').value;

  const filters = {};
  if (cond) filters.condition = cond;
  if (sort) filters.sort = sort;
  if (minPrice) filters.min_price = minPrice;
  if (maxPrice) filters.max_price = maxPrice;

  closeFilterModal();
  loadProducts(filters);
}

// Format Helpers
function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(amount);
}

function formatCondition(cond) {
  const map = {
    'seperti_baru': 'Seperti Baru ⭐',
    'mulus': 'Mulus 👍',
    'bekas_wajar': 'Bekas Wajar 📦',
    'minus_pemakaian': 'Minus Pemakaian 🔧'
  };
  return map[cond] || cond;
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Modal Buka di HP (Wi-Fi Hub)
let cachedHpUrl = '';

async function openHpModal() {
  const modal = document.getElementById('hp-modal');
  if (!modal) return;
  modal.classList.add('active');

  const urlEl = document.getElementById('hp-modal-url');
  const qrEl = document.getElementById('hp-modal-qr');

  if (cachedHpUrl) {
    urlEl.textContent = cachedHpUrl;
    qrEl.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(cachedHpUrl)}`;
    return;
  }

  urlEl.textContent = 'Mendeteksi IP Wi-Fi...';

  try {
    const res = await fetch('api/config/network.php');
    const data = await res.json();
    if (data.status && data.mobile_url) {
      cachedHpUrl = data.mobile_url;
    } else {
      cachedHpUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '') + '/';
    }
  } catch (err) {
    cachedHpUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '') + '/';
  }

  urlEl.textContent = cachedHpUrl;
  qrEl.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(cachedHpUrl)}`;
}

function closeHpModal() {
  const modal = document.getElementById('hp-modal');
  if (modal) modal.classList.remove('active');
}

function copyHpModalUrl() {
  const urlEl = document.getElementById('hp-modal-url');
  if (urlEl && urlEl.textContent) {
    navigator.clipboard.writeText(urlEl.textContent).then(() => {
      showToast('Link disalin! Buka atau kirim ke HP Anda.');
    });
  }
}

