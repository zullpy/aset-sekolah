<?php
/**
 * Data Aset Router - Mengarahkan ke KIB A di dalam folder data-aset secara default
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_auth(['administrator', 'operator']);

if (strtolower($_SESSION['role'] ?? '') === 'operator') {
    header('Location: aset-kelas.php');
    exit;
}

header('Location: data-aset/kib-a.php');
exit;
