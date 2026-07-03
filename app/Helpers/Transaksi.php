<?php

if (!function_exists('hitung_biaya_admin')) {
    function hitung_biaya_admin($total_harga)
    {
        $totalHarga = (float) $total_harga;

        if ($totalHarga <= 20000000) {
            return $totalHarga * 0.005;
        }

        return $totalHarga * 0.0075;
    }
}

if (!function_exists('hitung_diskon_kupon')) {
    function hitung_diskon_kupon($total_harga, $kupon_code)
    {
        $totalHarga = (float) $total_harga;
        $kodeKupon = trim((string) $kupon_code);

        if ($kodeKupon === 'HEMAT') {
            return $totalHarga * 0.15;
        }

        if ($kodeKupon === 'SUPER') {
            return $totalHarga * 0.20;
        }

        return 0;
    }
}

if (!function_exists('hitung_cashback')) {
    function hitung_cashback($total_harga)
    {
        $totalHarga = (float) $total_harga;

        if ($totalHarga > 10000000) {
            return $totalHarga * 0.02;
        }

        return 0;
    }
}
