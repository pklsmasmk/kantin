<?php

$file = __DIR__ . '/data.json';

function loadData($file)
{
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveData($file, $records)
{
    if (!is_writable(dirname($file))) {
        $_SESSION['error'] = 'Folder tidak bisa ditulis. Periksa permission folder.';
        return false;
    }
    return file_put_contents($file, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $records = loadData($file);

        $name = trim($_POST['name'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'hutang';
        $dueDate = $_POST['dueDate'] ?? date('Y-m-d');
        $paymentMethod = $_POST['paymentMethod'] ?? 'Cash';
        $description = trim($_POST['description'] ?? '');
        $items = $_POST['items'] ?? []; // tambahan

        if ($name !== '' && $amount > 0) {
            $newRecord = [
                'id' => uniqid('rec_'),
                'type' => $type,
                'name' => $name,
                'amount' => $amount,
                'dueDate' => $dueDate,
                'status' => 'belum lunas',
                'paymentMethod' => $paymentMethod,
                'description' => $description,
                'items' => $items,
                'createdAt' => date('Y-m-d H:i:s')
            ];

            $records[] = $newRecord;

            if (saveData($file, $records) !== false) {
                $_SESSION['success'] = 'Data berhasil ditambahkan!';
                header('Location: ' . ($type === 'hutang' ? 'hutang.php' : 'piutang.php'));
                exit;
            } else {
                $_SESSION['error'] = 'Gagal menyimpan data ke file.';
            }
        } else {
            $_SESSION['error'] = 'Nama dan jumlah wajib diisi dengan benar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Hutang/Piutang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-plus-circle text-primary"></i> Tambah Data Baru</h3>
                <a href="/?q=piutang" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php elseif (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    Form Tambah Data Hutang/Piutang
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis</label>
                                <select name="type" class="form-select" required>
                                    <option value="hutang">Hutang</option>
                                    <option value="piutang">Piutang</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Rp)</label>
                                <input type="number" name="amount" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Jatuh Tempo</label>
                                <input type="date" name="dueDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="paymentMethod" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Dana">Dana</option>
                                    <option value="Ovo">Ovo</option>
                                    <option value="Gopay">Gopay</option>
                                    <option value="Qris">Qris</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="description" class="form-control" placeholder="Opsional">
                            </div>
                        </div>

                        <div id="itemList" class="mt-4" style="display:none;">
                            <h6>Detail Item:</h6>
                            <ul class="list-group" id="itemContainer"></ul>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="/?q=menu" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const nama = localStorage.getItem("pendingPiutangNama");
  const total = localStorage.getItem("pendingPiutangTotal");
  const ket = localStorage.getItem("pendingPiutangKet");
  const items = JSON.parse(localStorage.getItem("pendingPiutangItems") || "[]");

  if (nama && total) {
    document.querySelector("select[name='type']").value = "piutang";
    document.querySelector("input[name='name']").value = nama;
    document.querySelector("input[name='amount']").value = total;
    if (ket) document.querySelector("input[name='description']").value = ket;

    if (items.length > 0) {
      const listContainer = document.getElementById("itemList");
      const itemUl = document.getElementById("itemContainer");
      listContainer.style.display = "block";
      items.forEach(it => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between";
        li.textContent = `${it.nama} x${it.qty} - Rp${it.harga}`;
        itemUl.appendChild(li);
      });

      const form = document.querySelector("form");
      const inputHidden = document.createElement("input");
      inputHidden.type = "hidden";
      inputHidden.name = "items";
      inputHidden.value = JSON.stringify(items);
      form.appendChild(inputHidden);
    }

    localStorage.removeItem("pendingPiutangNama");
    localStorage.removeItem("pendingPiutangTotal");
    localStorage.removeItem("pendingPiutangKet");
    localStorage.removeItem("pendingPiutangItems");
  }
});
</script>
</body>
</html>
