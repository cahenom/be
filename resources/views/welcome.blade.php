<!DOCTYPE html>
<html lang="id" class="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PUNYAKIOS - Solusi Pembayaran Digital</title>

<!-- Font & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>

<style>

/* ========================= */
/* VARIABLES */
/* ========================= */
:root {
    --primary: #0d7cf2;
    --dark-bg: #101922;
    --light-bg: #f5f7f8;
    --text-dark: #0d141c;
    --text-light: #f8fafc;

    --radius: 14px;
    --transition: 0.25s ease;
}

/* DARK MODE */
.dark body {
    background: var(--dark-bg);
    color: var(--text-light);
}

.dark .header {
    background: rgba(16, 25, 34, 0.8);
    border-color: #1e293b;
}

.dark .nav-links a {
    color: #e2e8f0;
}

.dark .hero-desc,
.dark .stats-label,
.dark .features-desc,
.dark footer a,
.dark .footer-desc {
    color: #94a3b8;
}

.dark .feature-card {
    background: #0f1822;
    border-color: #1f2937;
}

.dark .phone-screen {
    background: #0f1822;
    color: #e2e8f0;
}

/* ========================= */
/* GLOBAL */
/* ========================= */
body {
    margin: 0;
    font-family: "Plus Jakarta Sans", sans-serif;
    background: var(--light-bg);
    color: var(--text-dark);
    transition: background var(--transition), color var(--transition);
}

.container {
    max-width: 1280px;
    padding: 0 24px;
    margin: auto;
}

a {
    text-decoration: none;
}

/* ========================= */
/* HEADER */
/* ========================= */
.header {
    position: fixed;
    top: 0;
    width: 100%;
    height: 72px;
    background: rgba(245,247,248,0.85);
    display: flex;
    align-items: center;
    border-bottom: 1px solid #d6dee6;
    backdrop-filter: blur(12px);
    z-index: 50;
}

.nav-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    width: 36px;
    height: 36px;
    background: var(--primary);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 12px;
}

.brand-title {
    font-size: 20px;
    font-weight: 800;
}

.nav-links {
    display: flex;
    gap: 32px;
}

.nav-links a {
    color: var(--text-dark);
    font-size: 14px;
    font-weight: 600;
    transition: var(--transition);
}

.nav-links a:hover {
    color: var(--primary);
}

.nav-actions {
    display: flex;
    gap: 16px;
}

.btn-text {
    background: none;
    border: none;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary);
    border: none;
    border-radius: 12px;
    padding: 10px 18px;
    color: white;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary:hover {
    background: #0b6dd7;
}


/* ========================= */
/* HERO */
/* ========================= */

.hero {
    padding-top: 140px;
    padding-bottom: 100px;
}

.hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
}

.hero-badge {
    background: rgba(13,124,242,0.1);
    color: var(--primary);
    padding: 6px 16px;
    border-radius: 999px;
    display: inline-flex;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
}

.hero-title {
    margin-top: 12px;
    font-size: 56px;
    font-weight: 900;
    line-height: 1.1;
}

.hero-desc {
    max-width: 520px;
    font-size: 17px;
    color: #64748b;
    line-height: 1.6;
}

.text-primary {
    color: var(--primary);
}

/* STORE BUTTONS */
.download-buttons {
    display: flex;
    gap: 18px;
    margin-top: 24px;
}

.store-btn {
    background: #0d141c;
    color: white;
    padding: 14px 22px;
    border-radius: 16px;
    display: flex;
    gap: 14px;
    align-items: center;
    cursor: pointer;
    transition: transform var(--transition);
}

.dark .store-btn {
    background: #1e293b;
}

.store-btn:hover {
    transform: scale(1.05);
}

.store-icon {
    font-size: 34px;
}

.store-small {
    font-size: 10px;
    opacity: 0.8;
    margin: 0;
}

.store-big {
    font-size: 18px;
    margin: 0;
    font-weight: 700;
}

/* ========================= */
/* HERO STATS */
/* ========================= */
.stats {
    display: flex;
    gap: 48px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #d4dce4;
}

.dark .stats {
    border-color: #1e293b;
}

.stats-number {
    color: var(--primary);
    font-size: 28px;
    font-weight: 900;
}

.stats-label {
    color: #64748b;
    font-size: 14px;
}

/* ========================= */
/* PHONE MOCKUP */
/* ========================= */
.hero-right {
    position: relative;
    display: flex;
    justify-content: center;
}

.phone-mockup {
    width: 300px;
    height: 600px;
    background: #0d141c;
    border-radius: 40px;
    padding: 12px;
    border: 6px solid #222;
    position: relative;
    box-shadow: 0 40px 80px -20px rgba(0,0,0,0.5);
}

.phone-top {
    width: 120px;
    height: 26px;
    background: #0d141c;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    position: absolute;
    left: 50%;
    top: 0;
    transform: translateX(-50%);
}

.phone-screen {
    background: white;
    width: 100%;
    height: 100%;
    border-radius: 32px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.dark .phone-screen {
    background: #0f1822;
}

.app-topbar {
    background: var(--primary);
    color: white;
    padding: 24px;
    padding-top: 40px;
    display: flex;
    justify-content: space-between;
    border-bottom-left-radius: 26px;
    border-bottom-right-radius: 26px;
}

.saldo-label {
    opacity: 0.8;
    font-size: 12px;
    margin: 0;
}

.saldo-value {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.saldo-add {
    background: rgba(255,255,255,0.2);
    padding: 6px;
    border-radius: 12px;
}

.app-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}


.service-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
    text-align: center;
}

.service-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.blue { background: #dbeafe; color: #2563eb; }
.green { background: #dcfce7; color: #16a34a; }
.orange { background: #ffedd5; color: #ea580c; }
.purple { background: #f3e8ff; color: #9333ea; }

.service-label {
    font-size: 10px;
    font-weight: 700;
    margin-top: 4px;
}

.last-title {
    text-transform: uppercase;
    font-size: 12px;
    color: #94a3b8;
    font-weight: 700;
}

.last-item {
    display: flex;
    justify-content: space-between;
    background: #f8fafc;
    padding: 12px;
    border-radius: 14px;
}

.dark .last-item {
    background: #1e293b;
}

.last-left {
    display: flex;
    gap: 12px;
    align-items: center;
}

.last-icon {
    width: 32px;
    height: 32px;
    border-radius: 999px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.last-icon.primary {
    background: rgba(13,124,242,0.15);
    color: var(--primary);
}

.last-icon.greenbg {
    background: #dcfce7;
    color: #16a34a;
}

.last-name {
    font-size: 11px;
    font-weight: 800;
    margin: 0;
}

.last-date {
    font-size: 9px;
    color: #64748b;
    margin: 0;
}

.last-out {
    color: #ef4444;
    font-size: 11px;
    font-weight: 700;
}

.last-in {
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
}

/* Floating badges */
.glass {
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.4);
}

.dark .glass {
    background: rgba(16, 25, 34, 0.65);
}

.badge-right,
.badge-left {
    position: absolute;
    padding: 14px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 180px;
}

.badge-right {
    right: -120px;
    top: 25%;
}

.badge-left {
    left: -140px;
    bottom: 25%;
}

.badge-icon {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.badge-icon.green {
    background: #22c55e;
}

.badge-icon.primary {
    background: var(--primary);
}

.badge-title {
    font-size: 11px;
    font-weight: 800;
    margin: 0;
}

.badge-sub {
    font-size: 10px;
    color: #64748b;
    margin: 0;
}

/* ========================= */
/* FEATURES */
/* ========================= */

.features {
    padding: 100px 0;
}

.features-header {
    max-width: 700px;
}

.features-title {
    font-size: 36px;
    font-weight: 800;
    margin: 0;
}

.features-desc {
    color: #64748b;
    font-size: 17px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 32px;
    margin-top: 48px;
}

.feature-card {
    padding: 32px;
    border-radius: 20px;
    border: 1px solid #d6dee6;
    background: var(--light-bg);
    transition: var(--transition);
}

.feature-card:hover {
    border-color: var(--primary);
}

.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(13,124,242,0.1);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 26px;
}

.feature-card h3 {
    margin: 0 0 10px;
    font-size: 20px;
}

.feature-card p {
    color: #64748b;
    margin: 0;
}

/* ========================= */
/* FOOTER */
/* ========================= */

footer {
    border-top: 1px solid #d6dee6;
    padding: 60px 0;
}

.footer-grid {
    display: flex;
    justify-content: space-between;
    gap: 48px;
}

.footer-desc {
    width: 300px;
    font-size: 14px;
    color: #64748b;
}

.footer-columns {
    display: flex;
    gap: 48px;
}

.footer-col {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-title {
    font-weight: 800;
    font-size: 14px;
    margin-bottom: 4px;
}

.footer-col a {
    color: #64748b;
    font-size: 14px;
    transition: var(--transition);
}

.footer-col a:hover {
    color: var(--primary);
}

.footer-bottom {
    border-top: 1px solid #d6dee6;
    margin-top: 40px;
    padding-top: 16px;
    display: flex;
    justify-content: space-between;
    color: #64748b;
}

.footer-icons {
    display: flex;
    gap: 24px;
}

.footer-icons .material-symbols-outlined {
    color: #94a3b8;
    cursor: pointer;
    transition: var(--transition);
}

.footer-icons .material-symbols-outlined:hover {
    color: var(--primary);
}

/* ========================= */
/* RESPONSIVE */
/* ========================= */

@media (max-width: 1024px) {
    .hero-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .download-buttons {
        justify-content: center;
    }

    .stats {
        justify-content: center;
    }

    .hero-right {
        display: flex;
        justify-content: center;
    }

    .badge-right {
        right: -80px;
    }
    .badge-left {
        left: -100px;
    }
}

@media (max-width: 768px) {
    .nav-links {
        display: none;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .footer-grid {
        flex-direction: column;
    }

    .footer-columns {
        flex-direction: column;
        gap: 20px;
    }

    .badge-right {
        display: none;
    }
    .badge-left {
        display: none;
    }
}

@media (max-width: 480px) {
    .phone-mockup {
        transform: scale(0.85);
    }
}

/* Media query untuk menangani tabrakan tombol di layar sedang */
@media (max-width: 992px) {
    .nav-actions {
        gap: 8px; /* Kurangi jarak antar tombol */
    }

    .btn-text {
        font-size: 12px; /* Kurangi ukuran font untuk menghemat ruang */
        padding: 8px; /* Kurangi padding */
    }

    .btn-primary {
        padding: 8px 12px; /* Kurangi padding untuk menghemat ruang */
        font-size: 12px; /* Kurangi ukuran font */
    }
}

@media (max-width: 768px) {
    .nav-actions {
        gap: 6px; /* Lebih kecil lagi di layar kecil */
    }

    .btn-text {
        font-size: 11px;
        padding: 6px;
    }

    .btn-primary {
        padding: 6px 10px;
        font-size: 11px;
    }
}

</style>

</head>

<body>

<header class="header">
    <div class="container nav-wrapper">
        <div class="brand">
            <div class="brand-icon">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <span class="brand-title">PUNYAKIOS</span>
        </div>

        <nav class="nav-links">
            <a href="#">Beranda</a>
            <a href="#">Layanan</a>
            <a href="#">Harga</a>
            <a href="#">Bantuan</a>
        </nav>

        <div class="nav-actions">
            
            <a href="https://play.google.com/store/apps/details?id=com.saldoplus.plus&pli=1" class="btn-primary">Daftar Sekarang</a>
        </div>
    </div>
</header>

<main>

<!-- ================= HERO ================= -->
<section class="hero container">
    <div class="hero-grid">

        <!-- Left -->
        <div class="hero-left">
            <div class="hero-badge">
                <span class="material-symbols-outlined">verified</span>
                Pilihan No. 1 Masyarakat Indonesia
            </div>

            <h1 class="hero-title">
                Solusi Pembayaran <span class="text-primary">Digital</span> Terlengkap
            </h1>

            <p class="hero-desc">
                Kelola tagihan bulanan, isi pulsa, hingga top-up e-wallet hanya dalam satu aplikasi. Aman, mudah, dan tersedia 24/7.
            </p>

            <div class="download-buttons">
               

                <a href="https://play.google.com/store/apps/details?id=com.saldoplus.plus&pli=1" 
   target="_blank" 
   rel="noopener noreferrer" 
   style="text-decoration: none; color: inherit;">
    
    <div class="store-btn">
        <div>
            <p class="store-small">Get it on</p>
            <p class="store-big">Google Play</p>
        </div>
    </div>

</a>

            </div>

            <div class="stats">
                <div>
                    <p class="stats-number">1M+</p>
                    <p class="stats-label">Pengguna Aktif</p>
                </div>
                <div>
                    <p class="stats-number">500K+</p>
                    <p class="stats-label">Transaksi Harian</p>
                </div>
                <div>
                    <p class="stats-number">10K+</p>
                    <p class="stats-label">Mitra Terdaftar</p>
                </div>
            </div>
        </div>

        <!-- Right / Phone Mockup -->
        <div class="hero-right">

            <div class="phone-mockup">

                <div class="phone-top"></div>

                <div class="phone-screen">

                    <!-- Top Bar -->
                    <div class="app-topbar">
                        <div>
                            <p class="saldo-label">Saldo Anda</p>
                            <p class="saldo-value">Rp 2.450.000</p>
                        </div>
                        <div class="saldo-add">
                            <span class="material-symbols-outlined">add_circle</span>
                        </div>
                    </div>

                    <!-- App Content -->
                    <div class="app-content">

                        <div class="service-grid">
                            <div class="service-item">
                                <div class="service-icon blue"><span class="material-symbols-outlined">phone_iphone</span></div>
                                <span class="service-label">Pulsa</span>
                            </div>

                            <div class="service-item">
                                <div class="service-icon green"><span class="material-symbols-outlined">language</span></div>
                                <span class="service-label">Data</span>
                            </div>

                            <div class="service-item">
                                <div class="service-icon orange"><span class="material-symbols-outlined">bolt</span></div>
                                <span class="service-label">PLN</span>
                            </div>

                            <div class="service-item">
                                <div class="service-icon purple"><span class="material-symbols-outlined">account_balance_wallet</span></div>
                                <span class="service-label">Wallet</span>
                            </div>
                        </div>

                        <!-- Last Transactions -->
                        <div class="last-title">Transaksi Terakhir</div>

                        <div class="last-item">
                            <div class="last-left">
                                <div class="last-icon primary"><span class="material-symbols-outlined">bolt</span></div>
                                <div>
                                    <p class="last-name">Token Listrik</p>
                                    <p class="last-date">22 Okt, 14:20</p>
                                </div>
                            </div>
                            <p class="last-out">-Rp 102.500</p>
                        </div>

                        <div class="last-item">
                            <div class="last-left">
                                <div class="last-icon greenbg"><span class="material-symbols-outlined">account_balance_wallet</span></div>
                                <div>
                                    <p class="last-name">Top Up E-Wallet</p>
                                    <p class="last-date">21 Okt, 09:15</p>
                                </div>
                            </div>
                            <p class="last-in">+Rp 250.000</p>
                        </div>

                    </div>
                </div>

                <!-- Badges -->
                <div class="badge-right glass">
                    <div class="badge-icon green"><span class="material-symbols-outlined">check_circle</span></div>
                    <div>
                        <p class="badge-title">Bayar Listrik</p>
                        <p class="badge-sub">Berhasil dalam 2 detik</p>
                    </div>
                </div>

                <div class="badge-left glass">
                    <div class="badge-icon primary"><span class="material-symbols-outlined">shield</span></div>
                    <div>
                        <p class="badge-title">Keamanan Bank</p>
                        <p class="badge-sub">Data terenkripsi 256-bit</p>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>

<!-- ================= FEATURES ================= -->
<section class="features">
    <div class="container">

        <div class="features-header">
            <h2 class="features-title">Layanan Unggulan Kami</h2>
            <p class="features-desc">Nikmati kemudahan transaksi digital dengan berbagai fitur terbaik.</p>
        </div>

        <div class="features-grid">

            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">bolt</span></div>
                <h3>Transaksi Instan</h3>
                <p>Proses cepat untuk semua jenis pembayaran.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">verified_user</span></div>
                <h3>Aman & Terpercaya</h3>
                <p>Keamanan data dan transaksi terenkripsi.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">support_agent</span></div>
                <h3>Layanan 24/7</h3>
                <p>Bantuan pelanggan kapan saja.</p>
            </div>

        </div>

    </div>
</section>

</main>


<!-- ================= FOOTER ================= -->
<footer>
    <div class="container footer-grid">

        <div>
            <div class="brand">
                <div class="brand-icon"><span class="material-symbols-outlined">payments</span></div>
                <span class="brand-title">PUNYAKIOS</span>
            </div>
            <p class="footer-desc">
                Solusi pembayaran digital paling andal di Indonesia.
            </p>
        </div>

        <div class="footer-columns">

            <div class="footer-col">
                <p class="footer-title">Produk</p>
                <a href="#">Isi Pulsa</a>
                <a href="#">Tagihan PLN</a>
                <a href="#">E-Wallet</a>
            </div>

            <div class="footer-col">
                <p class="footer-title">Perusahaan</p>
                <a href="#">Tentang Kami</a>
                <a href="#">Karir</a>
                <a href="#">Kontak</a>
            </div>

            <div class="footer-col">
                <p class="footer-title">Legal</p>
                <a href="#">Privasi</a>
                <a href="#">Ketentuan</a>
            </div>

        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2024 PUNYAKIOS. All rights reserved.</p>
        <div class="footer-icons">
            <span class="material-symbols-outlined">public</span>
            <span class="material-symbols-outlined">camera</span>
            <span class="material-symbols-outlined">alternate_email</span>
        </div>
    </div>
</footer>

</body>
</html>
