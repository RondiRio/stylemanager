<?php
// includes/utils.php
function formatar_moeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatar_data($data) {
    return date('d/m/Y', strtotime($data));
}

function formatar_hora($hora) {
    return date('H:i', strtotime($hora));
}

function redirecionar_com_mensagem($url, $msg, $tipo = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'tipo' => $tipo];
    header("Location: $url");
    exit;
}
function ajustar_cor($hex, $fator) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $fator)));
    $g = max(0, min(255, $g + ($g * $fator)));
    $b = max(0, min(255, $b + ($b * $fator)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function contraste_cor($hex) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luma = ($r * 0.299 + $g * 0.587 + $b * 0.114);
    return $luma > 186 ? '#a5a5a5ff' : '#ffffff';
}
if (!function_exists('redirecionar')) {
    function redirecionar($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('redirecionar_com_mensagem')) {
    function redirecionar_com_mensagem($url, $mensagem, $tipo = 'success') {
        // Armazena mensagem na sessão
        $_SESSION['flash'] = [
            'mensagem' => $mensagem,
            'tipo' => $tipo
        ];
        redirecionar($url);
    }
}

if (!function_exists('mostrar_flash')) {
    function mostrar_flash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            echo '<div class="alert alert-' . htmlspecialchars($flash['tipo']) . ' alert-dismissible fade show">';
            echo htmlspecialchars($flash['mensagem']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['flash']);
        }
    }
}

if (!function_exists('formatar_telefone')) {
    function formatar_telefone($telefone) {
        if (!$telefone) return '';
        $telefone = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }
        return $telefone;
    }
}
if (!function_exists('formatar_duracao')) {
    function formatar_duracao($minutos) {
        $h = floor($minutos / 60);
        $m = $minutos % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}min";
        if ($h > 0) return "{$h}h";
        return "{$m}min";
    }
}
?>