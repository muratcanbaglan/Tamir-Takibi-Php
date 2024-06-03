<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Borç Takip Sistemi</title>
    <script>
    function showAlert(message) {
        document.getElementById('alertMessage').innerText = message;
        document.getElementById('alertBox').style.display = 'block';
    }

    function closeAlert() {
        document.getElementById('alertBox').style.display = 'none';
        window.location.href = window.location.href; // Sayfayı yenile
    }
</script>
    <style>
        #alertBox {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: #f44336;
            color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        #alertBox button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div id="alertBox">
    <p id="alertMessage"></p>
    <button onclick="closeAlert()">Tamam</button>
</div>

<?php
// JSON dosyasının adı
$json_file = 'tamirkayit.json';

// JSON dosyasını oku
if (file_exists($json_file)) {
    $data = json_decode(file_get_contents($json_file), true);
} else {
    $data = ['borclar' => [], 'odemeler' => [], 'manuel_odeme' => []];
}

// Formdan gelen verileri işle
$action_performed = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['borc_ekle'])) {
        // Tamir borcu ekle
        $aciklama = $_POST['aciklama'];
        $fiyat = floatval($_POST['fiyat']);
        $tarih = $_POST['tarih'];
        $data['borclar'][] = ['aciklama' => $aciklama, 'fiyat' => $fiyat, 'tarih' => $tarih];
        $action_performed = 'Tamir borcu eklendi';
    } elseif (isset($_POST['odeme_ekle'])) {
        // Ödeme ekle
        $borc_index = intval($_POST['odeme_ekle']);
        if (isset($data['borclar'][$borc_index])) {
            $data['odemeler'][] = $data['borclar'][$borc_index];
            unset($data['borclar'][$borc_index]);
            $data['borclar'] = array_values($data['borclar']); // Diziyi yeniden indeksle
            $action_performed = 'Ödeme eklendi';
        }
    } elseif (isset($_POST['borc_sil'])) {
        // Borç sil
        $borc_index = intval($_POST['borc_sil']);
        if (isset($data['borclar'][$borc_index])) {
            unset($data['borclar'][$borc_index]);
            $data['borclar'] = array_values($data['borclar']); // Diziyi yeniden indeksle
            $action_performed = 'Borç silindi';
        }
    } elseif (isset($_POST['manuel_odeme_ekle'])) {
        // Manuel ödeme ekle
        $manuel_aciklama = $_POST['manuel_aciklama'];
        $manuel_fiyat = floatval($_POST['manuel_fiyat']);
        $data['manuel_odeme'][] = ['aciklama' => $manuel_aciklama, 'fiyat' => $manuel_fiyat, 'tarih' => date('Y-m-d')];
        $action_performed = 'Manuel ödeme eklendi';
    } elseif (isset($_POST['odeme_sil'])) {
        // Ödeme sil
        $odeme_index = intval($_POST['odeme_sil']);
        if (isset($data['odemeler'][$odeme_index])) {
            unset($data['odemeler'][$odeme_index]);
            $data['odemeler'] = array_values($data['odemeler']); // Diziyi yeniden indeksle
            $action_performed = 'Ödeme silindi';
        } elseif (isset($data['manuel_odeme'][$odeme_index])) {
            unset($data['manuel_odeme'][$odeme_index]);
            $data['manuel_odeme'] = array_values($data['manuel_odeme']); // Diziyi yeniden indeksle
            $action_performed = 'Manuel ödeme silindi';
        }
    }

    // JSON dosyasına kaydet
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
    
    // Formun yeniden gönderilmesini önlemek için sayfayı yeniden yönlendirmiyoruz.
    // Bunun yerine JavaScript ile kullanıcıya bildirim gösteriyoruz.
    if ($action_performed) {
        echo "<script>showAlert('$action_performed');</script>";
    }
}

// Toplam kalan borç ve toplam ödenen borç hesapla
$toplam_kalan_borc = array_sum(array_column($data['borclar'], 'fiyat')) - array_sum(array_column($data['manuel_odeme'], 'fiyat'));
$toplam_odenen_borc = array_sum(array_column($data['odemeler'], 'fiyat')) + array_sum(array_column($data['manuel_odeme'], 'fiyat'));
?>

<h2>Borç Takip Sistemi</h2>
<p>Toplam Kalan Borç: <?php echo number_format($toplam_kalan_borc, 2); ?> TL</p>
<p>Toplam Ödenen Borç: <?php echo number_format($toplam_odenen_borc, 2); ?> TL</p>

<form method="post">
    <h3>Yeni Tamir Borcu Ekle</h3>
    <label for="aciklama">Açıklama:</label>
    <input type="text" id="aciklama" name="aciklama" required>
    <br>
    <label for="fiyat">Fiyat:</label>
    <input type="number" id="fiyat" name="fiyat" step="0.01" required>
    <br>
    <label for="tarih">Tarih:</label>
    <input type="date" id="tarih" name="tarih" required>
    <br>
    <button type="submit" name="borc_ekle">Tamir Borcu Ekle</button>
</form>

<form method="post">
    <h3>Manuel Ödeme Ekle</h3>
    <label for="manuel_aciklama">Açıklama:</label>
    <input type="text" id="manuel_aciklama" name="manuel_aciklama" value="Manuel Ödeme" required>
    <br>
    <label for="manuel_fiyat">Tutar:</label>
    <input type="number" id="manuel_fiyat" name="manuel_fiyat" step="0.01" required>
    <br>
    <button type="submit" name="manuel_odeme_ekle">Manuel Ödeme Ekle</button>
</form>

<h3>Borçlar</h3>
<form method="post">
    <table border="1">
        <tr>
            <th>#</th>
            <th>Açıklama</th>
            <th>Fiyat</th>
            <th>Tarih</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($data['borclar'] as $index => $borc): ?>
            <tr>
                <td><?php echo $index; ?></td>
                <td><?php echo htmlspecialchars($borc['aciklama']); ?></td>
                <td><?php echo number_format($borc['fiyat'], 2); ?> TL</td>
                <td><?php echo htmlspecialchars($borc['tarih']); ?></td>
                <td>
                    <button type="submit" name="odeme_ekle" value="<?php echo $index; ?>">Ödeme Ekle</button>
                    <button type="submit" name="borc_sil" value="<?php echo $index; ?>">Satır Seç ve Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</form>

<h3>Ödemeler</h3>
<form method="post">
    <table border="1">
        <tr>
            <th>#</th>
            <th>Açıklama</th>
            <th>Fiyat</th>
            <th>Tarih</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($data['odemeler'] as $index => $odeme): ?>
            <tr>
                <td><?php echo $index; ?></td>
                <td><?php echo htmlspecialchars($odeme['aciklama']); ?></td>
                <td><?php echo number_format($odeme['fiyat'], 2); ?> TL</td>
                <td><?php echo htmlspecialchars($odeme['tarih']); ?></td>
                <td>
                    <button type="submit" name="odeme_sil" value="<?php echo $index; ?>">Ödeme Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($data['manuel_odeme'] as $index => $odeme): ?>
            <tr>
                <td><?php echo 'Manuel-' . $index; ?></td>
                <td><?php echo htmlspecialchars($odeme['aciklama']); ?></td>
                <td><?php echo number_format($odeme['fiyat'], 2); ?> TL</td>
                <td><?php echo htmlspecialchars($odeme['tarih']); ?></td>
                <td>
                    <button type="submit" name="odeme_sil" value="<?php echo $index; ?>">Ödeme Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</form>

</body>
</html>
