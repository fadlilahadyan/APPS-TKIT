<?php
/**
 * TEST LOGIC FITUR PRESENSI
 * File ini untuk menguji logika bisnis dari fitur presensi
 * 
 * Cara menjalankan:
 * 1. Letakkan di folder tests/
 * 2. Buka di browser: http://localhost/APPS-TKIT/tests/Test_Logic_Presensi.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// CLASS TEST CASE SEDERHANA
// ============================================
class TestCase {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function assertTrue($condition, $message) {
        $this->tests[] = [
            'condition' => $condition,
            'message' => $message,
            'type' => 'assertTrue'
        ];
    }
    
    public function assertFalse($condition, $message) {
        $this->tests[] = [
            'condition' => !$condition,
            'message' => $message,
            'type' => 'assertFalse'
        ];
    }
    
    public function assertEquals($expected, $actual, $message) {
        $this->tests[] = [
            'condition' => $expected == $actual,
            'message' => $message . " (Expected: " . $this->formatValue($expected) . ", Actual: " . $this->formatValue($actual) . ")",
            'type' => 'assertEquals',
            'expected' => $expected,
            'actual' => $actual
        ];
    }
    
    public function assertNotEquals($expected, $actual, $message) {
        $this->tests[] = [
            'condition' => $expected != $actual,
            'message' => $message . " (Expected not: " . $this->formatValue($expected) . ", Actual: " . $this->formatValue($actual) . ")",
            'type' => 'assertNotEquals'
        ];
    }
    
    public function assertNull($value, $message) {
        $this->tests[] = [
            'condition' => is_null($value),
            'message' => $message,
            'type' => 'assertNull'
        ];
    }
    
    public function assertNotNull($value, $message) {
        $this->tests[] = [
            'condition' => !is_null($value),
            'message' => $message,
            'type' => 'assertNotNull'
        ];
    }
    
    private function formatValue($value) {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_null($value)) return 'null';
        if (is_string($value)) return "'$value'";
        if (is_array($value)) return 'array(' . count($value) . ')';
        return $value;
    }
    
    public function run() {
        $this->passed = 0;
        $this->failed = 0;
        
        foreach ($this->tests as $test) {
            if ($test['condition']) {
                $this->passed++;
            } else {
                $this->failed++;
            }
        }
    }
    
    public function getResults() {
        return [
            'tests' => $this->tests,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => count($this->tests)
        ];
    }
}

// ============================================
// FUNGSI-FUNGSI LOGIC PRESENSI
// ============================================

/**
 * Fungsi 1: Validasi Status Kehadiran
 * Memeriksa apakah status kehadiran valid
 */
function isValidStatus($status) {
    $validStatus = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
    return in_array($status, $validStatus);
}

/**
 * Fungsi 2: Format Tanggal Indonesia
 * Mengubah format tanggal Y-m-d menjadi d-m-Y
 */
function formatTanggalIndonesia($tanggal) {
    if (empty($tanggal)) return '';
    $date = date_create($tanggal);
    return date_format($date, 'd-m-Y');
}

/**
 * Fungsi 3: Hitung Persentase Kehadiran
 * Menghitung persentase kehadiran berdasarkan jumlah hadir dan total hari
 */
function hitungPersentaseKehadiran($jumlahHadir, $totalHari) {
    if ($totalHari <= 0) return 0;
    return round(($jumlahHadir / $totalHari) * 100, 2);
}

/**
 * Fungsi 4: Generate ID Absen Otomatis
 * Membuat ID absen dengan format ABS + angka random
 */
function generateIdAbsen() {
    return 'ABS' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Fungsi 5: Validasi Tanggal
 * Memeriksa apakah format tanggal valid (Y-m-d)
 */
function isValidDateFormat($tanggal) {
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

/**
 * Fungsi 6: Cek Apakah Tanggal Weekend
 * Mengecek apakah tanggal adalah Sabtu atau Minggu
 */
function isWeekend($tanggal) {
    $day = date('N', strtotime($tanggal));
    return ($day == 6 || $day == 7);
}

/**
 * Fungsi 7: Hitung Total Hadir per Bulan
 * Menghitung jumlah kehadiran dalam sebulan (simulasi)
 */
function hitungTotalHadirPerBulan($dataAbsensi, $bulan, $tahun) {
    $total = 0;
    foreach ($dataAbsensi as $absensi) {
        $tgl = strtotime($absensi['tanggal']);
        if (date('m', $tgl) == $bulan && date('Y', $tgl) == $tahun) {
            if ($absensi['status'] == 'Hadir') {
                $total++;
            }
        }
    }
    return $total;
}

/**
 * Fungsi 8: Generate Laporan Bulanan
 * Membuat ringkasan laporan per bulan
 */
function generateLaporanBulanan($dataAbsensi, $bulan, $tahun) {
    $laporan = [
        'bulan' => $bulan,
        'tahun' => $tahun,
        'total_hadir' => 0,
        'total_sakit' => 0,
        'total_izin' => 0,
        'total_alpha' => 0,
        'total_hari' => 0
    ];
    
    foreach ($dataAbsensi as $absensi) {
        $tgl = strtotime($absensi['tanggal']);
        if (date('m', $tgl) == $bulan && date('Y', $tgl) == $tahun) {
            $laporan['total_hari']++;
            switch ($absensi['status']) {
                case 'Hadir': $laporan['total_hadir']++; break;
                case 'Sakit': $laporan['total_sakit']++; break;
                case 'Izin': $laporan['total_izin']++; break;
                case 'Alpha': $laporan['total_alpha']++; break;
            }
        }
    }
    
    return $laporan;
}

/**
 * Fungsi 9: Format Status dengan Warna
 * Mengembalikan HTML span dengan warna sesuai status
 */
function formatStatusWithColor($status) {
    $colors = [
        'Hadir' => '#10b981',
        'Sakit' => '#f59e0b',
        'Izin' => '#3b82f6',
        'Alpha' => '#ef4444'
    ];
    
    $color = isset($colors[$status]) ? $colors[$status] : '#64748b';
    return "<span style='color: $color; font-weight: bold;'>$status</span>";
}

/**
 * Fungsi 10: Validasi Input Presensi
 * Memeriksa kelengkapan data presensi
 */
function validatePresensiInput($tanggal, $id_kelas, $status_siswa) {
    $errors = [];
    
    if (empty($tanggal)) {
        $errors[] = "Tanggal harus diisi";
    } elseif (!isValidDateFormat($tanggal)) {
        $errors[] = "Format tanggal tidak valid";
    }
    
    if (empty($id_kelas)) {
        $errors[] = "Kelas harus dipilih";
    }
    
    if (empty($status_siswa) || !is_array($status_siswa)) {
        $errors[] = "Status siswa harus diisi";
    } else {
        foreach ($status_siswa as $status) {
            if (!isValidStatus($status)) {
                $errors[] = "Status '$status' tidak valid";
                break;
            }
        }
    }
    
    return [
        'is_valid' => empty($errors),
        'errors' => $errors
    ];
}

// ============================================
// DATA SAMPLE UNTUK TESTING
// ============================================

$sampleDataAbsensi = [
    ['tanggal' => '2026-03-01', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-02', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-03', 'status' => 'Sakit'],
    ['tanggal' => '2026-03-04', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-05', 'status' => 'Izin'],
    ['tanggal' => '2026-03-06', 'status' => 'Alpha'],
    ['tanggal' => '2026-03-07', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-08', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-09', 'status' => 'Hadir'],
    ['tanggal' => '2026-03-10', 'status' => 'Sakit'],
];

// ============================================
// JALANKAN TEST
// ============================================
$test = new TestCase();

// Test 1: Validasi Status
$test->assertTrue(isValidStatus('Hadir'), "Status 'Hadir' harus valid");
$test->assertTrue(isValidStatus('Sakit'), "Status 'Sakit' harus valid");
$test->assertTrue(isValidStatus('Izin'), "Status 'Izin' harus valid");
$test->assertTrue(isValidStatus('Alpha'), "Status 'Alpha' harus valid");
$test->assertFalse(isValidStatus('Bolos'), "Status 'Bolos' tidak boleh valid");
$test->assertFalse(isValidStatus(''), "Status kosong tidak boleh valid");

// Test 2: Format Tanggal
$test->assertEquals('04-03-2026', formatTanggalIndonesia('2026-03-04'), "Format tanggal 2026-03-04 harus menjadi 04-03-2026");
$test->assertEquals('01-01-2026', formatTanggalIndonesia('2026-01-01'), "Format tanggal 2026-01-01 harus menjadi 01-01-2026");
$test->assertEquals('', formatTanggalIndonesia(''), "Tanggal kosong harus mengembalikan string kosong");

// Test 3: Persentase Kehadiran
$test->assertEquals(80, hitungPersentaseKehadiran(8, 10), "8 dari 10 = 80%");
$test->assertEquals(100, hitungPersentaseKehadiran(10, 10), "10 dari 10 = 100%");
$test->assertEquals(0, hitungPersentaseKehadiran(0, 10), "0 dari 10 = 0%");
$test->assertEquals(0, hitungPersentaseKehadiran(5, 0), "Total hari 0 = 0%");
$test->assertEquals(33.33, hitungPersentaseKehadiran(1, 3), "1 dari 3 = 33.33%");

// Test 4: Generate ID Absen
$id1 = generateIdAbsen();
$id2 = generateIdAbsen();
$test->assertTrue(strpos($id1, 'ABS') === 0, "ID absen harus diawali dengan 'ABS'");
$test->assertTrue(strlen($id1) == 7 || strlen($id1) == 8, "ID absen harus memiliki panjang 7-8 karakter");
$test->assertNotEquals($id1, $id2, "ID absen harus unik (tidak sama)");

// Test 5: Validasi Format Tanggal
$test->assertTrue(isValidDateFormat('2026-03-04'), "Format Y-m-d harus valid");
$test->assertFalse(isValidDateFormat('04-03-2026'), "Format d-m-Y tidak valid");
$test->assertFalse(isValidDateFormat('2026/03/04'), "Format dengan slash tidak valid");
$test->assertFalse(isValidDateFormat('2026-13-45'), "Tanggal tidak valid");

// Test 6: Cek Weekend
$test->assertFalse(isWeekend('2026-03-04'), "Rabu bukan weekend"); // Rabu
$test->assertTrue(isWeekend('2026-03-07'), "Sabtu adalah weekend"); // Sabtu
$test->assertTrue(isWeekend('2026-03-08'), "Minggu adalah weekend"); // Minggu

// Test 7: Hitung Total Hadir per Bulan
$totalHadirMaret = hitungTotalHadirPerBulan($sampleDataAbsensi, '03', '2026');
$test->assertEquals(6, $totalHadirMaret, "Total hadir bulan Maret harus 6");

// Test 8: Generate Laporan Bulanan
$laporan = generateLaporanBulanan($sampleDataAbsensi, '03', '2026');
$test->assertEquals('03', $laporan['bulan'], "Bulan harus 03");
$test->assertEquals('2026', $laporan['tahun'], "Tahun harus 2026");
$test->assertEquals(6, $laporan['total_hadir'], "Total hadir harus 6");
$test->assertEquals(2, $laporan['total_sakit'], "Total sakit harus 2");
$test->assertEquals(1, $laporan['total_izin'], "Total izin harus 1");
$test->assertEquals(1, $laporan['total_alpha'], "Total alpha harus 1");
$test->assertEquals(10, $laporan['total_hari'], "Total hari harus 10");

// Test 9: Format Status dengan Warna
$hadirHtml = formatStatusWithColor('Hadir');
$test->assertTrue(strpos($hadirHtml, '#10b981') !== false, "Status Hadir harus berwarna hijau");
$test->assertTrue(strpos($hadirHtml, 'Hadir') !== false, "Status Hadir harus mengandung teks 'Hadir'");

// Test 10: Validasi Input Presensi
$validInput = validatePresensiInput('2026-03-04', 'KLS001', ['Hadir', 'Sakit', 'Izin']);
$test->assertTrue($validInput['is_valid'], "Input valid harus lolos validasi");
$test->assertEquals(0, count($validInput['errors']), "Tidak boleh ada error");

$invalidTanggal = validatePresensiInput('', 'KLS001', ['Hadir']);
$test->assertFalse($invalidTanggal['is_valid'], "Tanggal kosong harus tidak valid");
$test->assertTrue(count($invalidTanggal['errors']) > 0, "Harus ada error message");

$invalidStatus = validatePresensiInput('2026-03-04', 'KLS001', ['Hadir', 'Bolos']);
$test->assertFalse($invalidStatus['is_valid'], "Status tidak valid harus ditolak");

$invalidKelas = validatePresensiInput('2026-03-04', '', ['Hadir']);
$test->assertFalse($invalidKelas['is_valid'], "Kelas kosong harus tidak valid");

// Test 11: Edge Cases
$test->assertEquals(0, hitungPersentaseKehadiran(0, 0), "Pembagian dengan nol harus aman");
$test->assertFalse(isValidStatus(''), "Status kosong harus false");
$test->assertFalse(isValidDateFormat(''), "Tanggal kosong harus false");

// Test 12: Bulk Test Status
$allStatus = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
foreach ($allStatus as $status) {
    $test->assertTrue(isValidStatus($status), "Status '$status' harus valid");
}

// Jalankan semua test
$test->run();
$results = $test->getResults();

// ============================================
// TAMPILKAN HASIL TEST
// ============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Logic Presensi - TKIT Fathurrobbany</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f1f5f9; padding: 40px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        
        .summary {
            background: white;
            padding: 20px 30px;
            border-radius: 50px;
            color: #333;
            font-weight: bold;
        }
        
        .summary .passed { color: #10b981; }
        .summary .failed { color: #ef4444; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stat-card .number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-card .label {
            color: #64748b;
            font-size: 16px;
        }
        
        .test-results {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        .test-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .test-item:last-child { border-bottom: none; }
        
        .test-status {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .status-passed { background: #dcfce7; color: #166534; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        
        .test-message {
            flex: 1;
            font-size: 15px;
        }
        
        .test-message small {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
        
        .functions-list {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .functions-list h3 {
            margin-bottom: 15px;
            color: #1e293b;
        }
        
        .functions-list ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
        }
        
        .functions-list li {
            padding: 10px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🧪 Test Logic Presensi</h1>
                <p>Menguji logika bisnis fitur presensi dengan 12 test case</p>
                <p>Waktu test: <?= date('d F Y H:i:s') ?></p>
            </div>
            <div class="summary">
                <span class="passed">✓ <?= $results['passed'] ?></span> | 
                <span class="failed">✗ <?= $results['failed'] ?></span> | 
                Total <?= $results['total'] ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                <div class="number"><?= $results['total'] ?></div>
                <div class="label">Total Test</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <div class="number"><?= $results['passed'] ?></div>
                <div class="label">Passed</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                <div class="number">10</div>
                <div class="label">Fungsi Logic</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white;">
                <div class="number">✓</div>
                <div class="label">Coverage</div>
            </div>
        </div>

        <div class="test-results">
            <h2 style="margin-bottom: 20px;">📋 Detail Test Results</h2>
            
            <?php foreach ($results['tests'] as $index => $test): ?>
            <div class="test-item">
                <div class="test-status <?= $test['condition'] ? 'status-passed' : 'status-failed' ?>">
                    <?= $test['condition'] ? '✓' : '✗' ?>
                </div>
                <div class="test-message">
                    <strong>Test #<?= $index + 1 ?>:</strong> <?= $test['message'] ?>
                    <small>Type: <?= $test['type'] ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="functions-list">
            <h3>📌 Fungsi Logic yang Diuji</h3>
            <ul>
                <li><strong>isValidStatus()</strong> - Validasi status kehadiran</li>
                <li><strong>formatTanggalIndonesia()</strong> - Format tanggal d-m-Y</li>
                <li><strong>hitungPersentaseKehadiran()</strong> - Hitung % kehadiran</li>
                <li><strong>generateIdAbsen()</strong> - Generate ID unik</li>
                <li><strong>isValidDateFormat()</strong> - Validasi format tanggal</li>
                <li><strong>isWeekend()</strong> - Cek hari Sabtu/Minggu</li>
                <li><strong>hitungTotalHadirPerBulan()</strong> - Total hadir bulanan</li>
                <li><strong>generateLaporanBulanan()</strong> - Laporan rekap</li>
                <li><strong>formatStatusWithColor()</strong> - Status dengan warna</li>
                <li><strong>validatePresensiInput()</strong> - Validasi input form</li>
            </ul>
        </div>

        <div class="test-results" style="margin-top: 30px;">
            <h3>📊 Sample Data untuk Testing</h3>
            <pre style="background: #1e293b; color: #a5f3fc; padding: 20px; border-radius: 12px; overflow-x: auto; margin-top: 15px;">
<?php
print_r($sampleDataAbsensi);
?>
            </pre>
        </div>

        <div class="footer">
            <p>© 2026 TKIT Fathurrobbany - Test Logic Presensi</p>
            <p>Dibuat untuk menguji fungsi-fungsi bisnis sebelum implementasi</p>
        </div>
    </div>
</body>
</html>