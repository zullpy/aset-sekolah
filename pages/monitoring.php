<?php
/**
 * Router Monitoring Aset -> mengarahkan ke halaman lokasi di pages/monitoring/
 */

$ruang = strtolower(trim((string) ($_GET['ruang'] ?? 'kelas-1')));
$map = [
    'semua'     => 'monitoring/semua.php',
    'all'       => 'monitoring/semua.php',
    'kelas-1'   => 'monitoring/kelas-1.php',
    'kelas-2'   => 'monitoring/kelas-2.php',
    'kelas-3'   => 'monitoring/kelas-3.php',
    'kelas-4'   => 'monitoring/kelas-4.php',
    'kelas-5'   => 'monitoring/kelas-5.php',
    'kelas-6'   => 'monitoring/kelas-6.php',
    'umum'      => 'monitoring/aset-umum.php',
    'aset-umum' => 'monitoring/aset-umum.php',
];

$target = $map[$ruang] ?? 'monitoring/kelas-1.php';
header('Location: ' . $target, true, 302);
exit;
