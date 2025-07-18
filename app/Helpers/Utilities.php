<?php 

if (!function_exists('snakeToCamelCase')) {
    function snakeToCamelCase($input)
    {
        return lcfirst(preg_replace_callback('/_(.)/', function ($matches) {
            return strtoupper($matches[1]);
        }, $input));
    }
}

if (!function_exists('check')) {
    function check($param) {
        echo "<pre>";
        var_dump($param);
    }
}

if (!function_exists('objectToArray')) {
    function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map('objectToArray', $data);
        }

        return $data;
    }
}

if (!function_exists('bulan')) {
    function bulan($bulan){
        switch ($bulan) {
            case 1:
                $bulan = "Januari";
                break;
            case 2:
                $bulan = "Februari";
                break;
            case 3:
                $bulan = "Maret";
                break;
            case 4:
                $bulan = "April";
                break;
            case 5:
                $bulan = "Mei";
                break;
            case 6:
                $bulan = "Juni";
                break;
            case 7:
                $bulan = "Juli";
                break;
            case 8:
                $bulan = "Agustus";
                break;
            case 9:
                $bulan = "September";
                break;
            case 10:
                $bulan = "Oktober";
                break;
            case 11:
                $bulan = "November";
                break;
            case 12:
                $bulan = "Desember";
                break;
            default:
                $bulan = Date('F');
                break;
        }
        
        return $bulan;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($number = null) {
        
        $data = number_format((float) $number, 0, ',', '.');

        return $data;
    }
}

if (!function_exists('tanggal')) {
    function tanggal($tanggal = null) {
        
        if ($tanggal) {            
            $a = explode('-',$tanggal);
            $tanggal = $a['2']." ".bulan($a['1'])." ".$a['0'];
        }

        return $tanggal;
    }
}

if (!function_exists(function: 'formatDateTime')) {
    function dateTimeFormat($dateTime = null) {
        $returnValue = null;

        $date = date('Y-m-d', strtotime($dateTime));
        $time = date('H:i', strtotime($dateTime));

        $returnValue = $date . ' | ' . $time . ' WIB';

        return $returnValue;
    }
}

if (!function_exists('hari')) {
    function hari($tanggal = null) {
        $day = date('l', strtotime($tanggal));

        switch ($day) {
            case 'Sunday':
                $hari = 'Minggu';
                break;
            case 'Monday':
                $hari = 'Senin';
                break;
            case 'Tuesday':
                $hari = 'Selasa';
                break;
            case 'Wednesday':
                $hari = 'Rabu';
                break;
            case 'Thursday':
                $hari = 'Kamis';
                break;
            case 'Friday':
                $hari = 'Jumat';
                break;
            case 'Saturday':
                $hari = 'Sabtu';
                break;
            
            default:
                $hari = null;
                break;
        }

        return $hari;
    }
}

if (!function_exists('generateRandomString')) {
    function generateRandomString($length = null) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < (int) $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}

if (!function_exists('generateRandomUpperCaseAlphabet')) {
    function generateRandomUpperCaseAlphabet($length = null) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}

if (!function_exists('generateRandomLowerCaseAlphabet')) {
    function generateRandomLowerCaseAlphabet($length = null) {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}

if (!function_exists('generateRandomNumber')) {
    function generateRandomNumber($length = null) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}

if (!function_exists('cleanString')) {
    function cleanString($value) {
        // Hilangkan semua karakter non-digit (kecuali angka)
        $value = preg_replace('/[^\d]/u', '', $value);

        // Bersihkan karakter tak terlihat seperti U+202C
        $value = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}]/u', '', $value);

        return $value;
    }
}