<?php
require_once '../Database/config.php';
require_once '../Database/functions_piutang.php';

function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function addRecord($recordData)
{
    $database = db_kantin();
    $db = $database->pdo;

    try {
        $db->beginTransaction();

        // Validasi data sebelum insert
        if (empty($recordData['id']) || empty($recordData['name']) || $recordData['amount'] <= 0) {
            throw new Exception("Data tidak valid");
        }

        // Check if record already exists
        $checkQuery = "SELECT id FROM records WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$recordData['id']]);

        if ($checkStmt->fetch()) {
            throw new Exception("Record dengan ID ini sudah ada");
        }

        // Insert record
        $query = "INSERT INTO records (id, type, name, amount, dueDate, status, paymentMethod, description, createdAt) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $recordData['id'],
            $recordData['type'],
            $recordData['name'],
            $recordData['amount'],
            $recordData['dueDate'],
            'belum lunas',
            $recordData['paymentMethod'],
            $recordData['description'],
            date('Y-m-d H:i:s')
        ]);

        // Insert items jika ada
        if (!empty($recordData['items']) && is_array($recordData['items'])) {
            $query = "INSERT INTO items (record_id, nama, qty, harga) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);

            foreach ($recordData['items'] as $item) {
                if (!empty($item['nama']) && !empty($item['qty']) && !empty($item['harga'])) {
                    // Validasi item data
                    $qty = (int) $item['qty'];
                    $harga = (float) $item['harga'];

                    if ($qty <= 0 || $harga <= 0) {
                        continue; // Skip item yang tidak valid
                    }

                    $stmt->execute([
                        $recordData['id'],
                        sanitizeInput($item['nama']),
                        $qty,
                        $harga
                    ]);
                }
            }
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error adding record: " . $e->getMessage());
        return false;
    }
}

// GENERATE FORM TOKEN
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // CEK FORM TOKEN
        $submitted_token = $_POST['form_token'] ?? '';
        if ($submitted_token !== $_SESSION['form_token']) {
            $_SESSION['error'] = 'Form sudah disubmit sebelumnya! Silakan tambah data baru lagi.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        
        // HAPUS TOKEN SETELAH DIGUNAKAN
        unset($_SESSION['form_token']);

        // Sanitize semua input
        $name = sanitizeInput($_POST['name'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $type = sanitizeInput($_POST['type'] ?? 'piutang');
        $dueDate = sanitizeInput($_POST['dueDate'] ?? date('Y-m-d'));
        $paymentMethod = sanitizeInput($_POST['paymentMethod'] ?? 'Cash');
        $description = sanitizeInput($_POST['description'] ?? '');

        // Validasi items JSON
        $items = [];
        if (isset($_POST['items']) && !empty($_POST['items'])) {
            $decodedItems = json_decode($_POST['items'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedItems)) {
                $items = $decodedItems;
            }
        }

        // Validasi input
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nama wajib diisi.';
        }

        if ($amount <= 0) {
            $errors[] = 'Jumlah harus lebih dari 0.';
        }

        if (!in_array($type, ['piutang', 'hutang'])) {
            $errors[] = 'Jenis transaksi tidak valid.';
        }

        if (!validateDate($dueDate)) {
            $errors[] = 'Format tanggal tidak valid.';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
        } else {
            $newRecord = [
                'id' => uniqid('rec_'),
                'type' => $type,
                'name' => $name,
                'amount' => $amount,
                'dueDate' => $dueDate,
                'paymentMethod' => $paymentMethod,
                'description' => $description,
                'items' => $items
            ];

            if (addRecord($newRecord)) {
                $_SESSION['success'] = 'Data berhasil ditambahkan!';
                // GUNAKAN JavaScript redirect untuk menghindari header issues
                echo '<script>window.location.href = "/?q=piutang_hasilpiutang";</script>';
                exit;
            } else {
                $_SESSION['error'] = 'Gagal menyimpan data ke database.';
            }
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
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px 15px;
        }

        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .item-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .error-border {
            border-color: #dc3545 !important;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-plus-circle text-primary"></i> Tambah Data Baru</h3>
                    <a href="/?q=piutang_hasilpiutang" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> Form Tambah Data Hutang/Piutang</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="tambahForm">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?? '' ?>">
                            <input type="hidden" name="items" id="itemsInput" value="[]">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jenis Transaksi <span
                                            class="text-danger">*</span></label>
                                    <select name="type" class="form-select" required>
                                        <option value="piutang" selected>Piutang</option>
                                        <option value="hutang">Hutang</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Pemberi/Penerima <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="Masukkan nama"
                                        required
                                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jumlah (Rp) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" min="1" placeholder="0"
                                        required
                                        value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal Jatuh Tempo <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="dueDate" class="form-control"
                                        value="<?= isset($_POST['dueDate']) ? htmlspecialchars($_POST['dueDate']) : date('Y-m-d') ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Metode Pembayaran <span
                                            class="text-danger">*</span></label>
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
                                    <label class="form-label fw-semibold">Keterangan</label>
                                    <input type="text" name="description" class="form-control" placeholder="Opsional"
                                        value="<?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?>">
                                </div>
                            </div>

                            <div id="itemList" class="mt-4" style="display:none;">
                                <h6 class="fw-semibold text-primary">Detail Item:</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <ul class="list-group item-list" id="itemContainer"></ul>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 gap-2">
                                <a href="/?q=piutang_hasilpiutang" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Simpan Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('tambahForm');
            
            // PREVENT DUPLICATE SUBMISSION
            form.addEventListener('submit', function (e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                
                let isValid = true;
                const amount = parseFloat(document.querySelector('input[name="amount"]').value);
                const name = document.querySelector('input[name="name"]').value.trim();
                const dueDate = document.querySelector('input[name="dueDate"]').value;

                document.querySelectorAll('.form-control, .form-select').forEach(el => {
                    el.classList.remove('error-border');
                });

                if (amount <= 0 || isNaN(amount)) {
                    document.querySelector('input[name="amount"]').classList.add('error-border');
                    isValid = false;
                }

                if (!name) {
                    document.querySelector('input[name="name"]').classList.add('error-border');
                    isValid = false;
                }

                if (!dueDate) {
                    document.querySelector('input[name="dueDate"]').classList.add('error-border');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Data';
                    alert('Harap periksa kembali data yang diinput. Field yang ditandai dengan * wajib diisi dengan benar.');
                    return false;
                }
            });

            // Load data dari localStorage jika ada
            const pendingData = {
                nama: localStorage.getItem("pendingPiutangNama"),
                total: localStorage.getItem("pendingPiutangTotal"),
                ket: localStorage.getItem("pendingPiutangKet"),
                items: JSON.parse(localStorage.getItem("pendingPiutangItems") || "[]")
            };

            if (pendingData.nama && pendingData.total) {
                document.querySelector("select[name='type']").value = "piutang";
                document.querySelector("input[name='name']").value = pendingData.nama;
                document.querySelector("input[name='amount']").value = pendingData.total;

                if (pendingData.ket) {
                    document.querySelector("input[name='description']").value = pendingData.ket;
                }

                if (pendingData.items.length > 0) {
                    displayItems(pendingData.items);
                    localStorage.removeItem("pendingPiutangNama");
                    localStorage.removeItem("pendingPiutangTotal");
                    localStorage.removeItem("pendingPiutangKet");
                    localStorage.removeItem("pendingPiutangItems");
                }
            }
        });

        function displayItems(items) {
            const itemList = document.getElementById("itemList");
            const itemContainer = document.getElementById("itemContainer");
            const itemsInput = document.getElementById("itemsInput");

            itemList.style.display = "block";
            itemContainer.innerHTML = "";

            items.forEach((item, index) => {
                const li = document.createElement("li");
                li.className = "list-group-item d-flex justify-content-between align-items-center";
                li.innerHTML = `
            <div>
                <strong>${escapeHtml(item.nama)}</strong><br>
                <small class="text-muted">${item.qty} x ${formatRupiah(item.harga)}</small>
            </div>
            <span class="badge bg-primary rounded-pill">${formatRupiah(item.qty * item.harga)}</span>
        `;
                itemContainer.appendChild(li);
            });

            itemsInput.value = JSON.stringify(items);
        }

        function formatRupiah(amount) {
            return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>