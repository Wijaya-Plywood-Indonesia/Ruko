<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RUKO — Operational Management System</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                         Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue",
                         Arial, sans-serif;
            line-height: 1.7;
            color: #24292f;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        header {
            text-align: center;
            margin-bottom: 48px;
        }

        header img {
            max-width: 360px;
            margin-bottom: 24px;
        }

        h1 {
            font-size: 2.6rem;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 1.8rem;
            border-bottom: 1px solid #d0d7de;
            padding-bottom: 6px;
            margin-top: 56px;
        }

        h3 {
            margin-top: 32px;
        }

        p {
            margin: 14px 0;
        }

        ul {
            margin-left: 20px;
        }

        code, pre {
            background: #f6f8fa;
            border-radius: 6px;
        }

        pre {
            padding: 16px;
            overflow-x: auto;
        }

        .badges img {
            margin: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }

        th, td {
            padding: 10px 12px;
            border: 1px solid #d0d7de;
            text-align: left;
        }

        th {
            background: #f6f8fa;
        }

        footer {
            margin-top: 80px;
            text-align: center;
            font-size: 0.9rem;
            color: #57606a;
        }

        .lang-divider {
            margin: 64px 0;
            text-align: center;
            font-weight: bold;
            color: #57606a;
        }
    </style>
</head>
<body>

<div class="container">

<header>
    <!-- Ganti logo jika ada -->
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" alt="Project Logo">

    <h1>RUKO</h1>
    <p><strong>Operational & Data Management Web Application</strong></p>
    <p>Developed by <strong>Wijaya Plywood Indonesia</strong></p>

    <div class="badges">
        <img src="https://img.shields.io/badge/PHP-8.x-777bb3">
        <img src="https://img.shields.io/badge/Laravel-Framework-red">
        <img src="https://img.shields.io/badge/Status-Active-success">
        <img src="https://img.shields.io/badge/License-MIT-blue">
    </div>
</header>

<h2>🇮🇩 Tentang Proyek</h2>

<p>
    <strong>RUKO</strong> adalah aplikasi web yang dikembangkan untuk mendukung
    pengelolaan data dan proses operasional secara terstruktur, konsisten,
    dan mudah dikembangkan.
</p>

<p>
    Proyek ini dirancang sebagai fondasi sistem yang dapat digunakan
    untuk kebutuhan <strong>internal organisasi</strong> maupun
    dikembangkan lebih lanjut sebagai <strong>open-source project</strong>.
</p>

<h3>🎯 Tujuan</h3>
<ul>
    <li>Menyediakan sistem operasional terpusat</li>
    <li>Meningkatkan konsistensi dan validasi data</li>
    <li>Mengurangi proses manual dan human error</li>
    <li>Menyediakan arsitektur aplikasi yang scalable</li>
</ul>

<h3>👨‍💻 Informasi Pengembangan</h3>
<table>
    <tr><th>Nama Proyek</th><td>RUKO</td></tr>
    <tr><th>Dikembangkan oleh</th><td>Wijaya Plywood Indonesia</td></tr>
    <tr><th>Mulai Dikembangkan</th><td>2025</td></tr>
    <tr><th>Status</th><td>Aktif dikembangkan</td></tr>
    <tr><th>Jenis</th><td>Web Application</td></tr>
</table>

<h3>🛠️ Teknologi</h3>
<ul>
    <li>PHP 8.x</li>
    <li>Laravel Framework</li>
    <li>Blade Template Engine</li>
    <li>MySQL / MariaDB</li>
    <li>Vite, Composer, NPM</li>
</ul>

<h3>🚀 Instalasi Singkat</h3>
<pre><code>git clone https://github.com/Wijaya-Plywood-Indonesia/Ruko.git
cd Ruko
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve</code></pre>

<div class="lang-divider">— English Version —</div>

<h2>🇬🇧 About the Project</h2>

<p>
    <strong>RUKO</strong> is a web application designed to support structured
    operational and data management with a focus on scalability,
    maintainability, and long-term use.
</p>

<p>
    It can be deployed as an internal system or adapted as an open-source
    solution for broader use cases.
</p>

<h3>🎯 Goals</h3>
<ul>
    <li>Centralized operational data management</li>
    <li>Consistent validation and data integrity</li>
    <li>Reduced manual processes</li>
    <li>Scalable application architecture</li>
</ul>

<h3>🛠️ Tech Stack</h3>
<ul>
    <li>PHP 8.x</li>
    <li>Laravel Framework</li>
    <li>Blade Templates</li>
    <li>MySQL / MariaDB</li>
</ul>

<h3>🚀 Getting Started</h3>
<pre><code>git clone https://github.com/Wijaya-Plywood-Indonesia/Ruko.git
cd Ruko
composer install
npm install
php artisan serve</code></pre>

<footer>
    © 2025 Wijaya Plywood Indonesia — RUKO Project
</footer>

</div>
</body>
</html>
