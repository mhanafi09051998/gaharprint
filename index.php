<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gahar Print - Cetak Cepat, Kualitas Hebat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Gahar Print</h1>
        <p>Solusi Cetak Online Anda</p>
    </header>

    <main>
        <section id="upload-section">
            <h2>Formulir Pemesanan</h2>
            <form id="upload-form" action="upload.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="order_number">Nomor Pemesanan Shopee</label>
                    <input type="text" id="order_number" name="order_number" placeholder="Masukkan nomor pemesanan Anda" required>
                </div>
                <div class="form-group">
                    <label for="whatsapp_number">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="input-group-prefix">+62</span>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number" placeholder="81234567890" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="file-input">Unggah File Anda</label>
                    <input type="file" id="file-input" name="files[]" multiple required>
                    <p class="help-text">Maksimal 1000 file, total ukuran 500 MB.</p>
                </div>
                <div id="file-preview"></div>
                <button type="submit" class="btn">Unggah File</button>
            </form>
        </section>

        <section id="history-section">
            <h2>Riwayat Unggahan</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Pemesanan</th>
                            <th>Nomor WhatsApp</th>
                            <th>Jumlah File</th>
                            <th>Tanggal Unggah</th>
                            <th>Status Cetak</th>
                        </tr>
                    </thead>
                    <tbody id="history-table-body">
                        <!-- Data riwayat akan dimuat di sini oleh JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>