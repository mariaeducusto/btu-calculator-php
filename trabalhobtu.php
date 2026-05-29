<?php
$resultado = null;
$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $host      = 'localhost';
    $dbname    = 'calculo_btu';
    $username  = 'root';
    $password  = ''; 

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    
        $largura       = isset($_POST['largura']) ? filter_var($_POST['largura'], FILTER_VALIDATE_FLOAT) : null;
        $comprimento   = isset($_POST['comprimento']) ? filter_var($_POST['comprimento'], FILTER_VALIDATE_FLOAT) : null;
        $tipo_ambiente = isset($_POST['tipo_ambiente']) ? trim($_POST['tipo_ambiente']) : null;

       
        if ($largura === false || $largura <= 0 || $comprimento === false || $comprimento <= 0) {
            $erro = 'Por favor, insira valores numéricos válidos e maiores que zero.';
        } elseif ($tipo_ambiente !== 'residencial' && $tipo_ambiente !== 'comercial') {
            $erro = 'Tipo de ambiente selecionado é inválido.';
        } else {
            
            $area = $largura * $comprimento;

    
            $sql = "SELECT * FROM tabela_btu WHERE area_max_m2 >= :area ORDER BY area_max_m2 ASC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['area' => $area]);
            $faixa = $stmt->fetch();

            if (!$faixa) {
                $erro = 'A área informada (' . number_format($area, 2, ',', '.') . ' m²) ultrapassa o limite máximo coberto pela tabela (70 m²).';
            } else {

                $btus = ($tipo_ambiente === 'residencial') ? $faixa['btu_residencial'] : $faixa['btu_comercial'];
                
                $resultado = [
                    'area'   => number_format($area, 2, ',', '.'),
                    'teto'   => $faixa['area_max_m2'],
                    'tipo'   => ucfirst($tipo_ambiente),
                    'total'  => number_format($btus, 0, ',', '.')
                ];
            }
        }
    } catch (PDOException $e) {
        $erro = 'Erro de infraestrutura (Banco de Dados): ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dimensionamento de BTUs - Ar Condicionado</title>
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-hover: #1b5e20;
            --bg-success: #e8f5e9;
            --border-success: #a5d6a7;
            --bg-error: #ffebee;
            --border-error: #ef9a9a;
            --text-dark: #2c3e50;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7f6;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 460px;
            padding: 35px;
        }

        .card-header h2 {
            color: var(--primary-color);
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e0f2f1;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #546e7a;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cfd8dc;
            border-radius: 6px;
            font-size: 15px;
            color: var(--text-dark);
            outline: none;
            transition: all 0.2s ease-in-out;
        }

        .form-group input:focus, 
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .alert {
            margin-top: 25px;
            padding: 18px;
            border-radius: 8px;
            animation: fadeIn 0.4s ease-in-out;
        }

        .alert-danger {
            background-color: var(--bg-error);
            border: 1px solid var(--border-error);
            color: #c62828;
        }

        .alert-success {
            background-color: var(--bg-success);
            border: 1px solid var(--border-success);
            color: #1b5e20;
            text-align: center;
        }

        .alert-success h3 {
            font-size: 16px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .alert-success ul {
            list-style: none;
            font-size: 14px;
            margin-bottom: 12px;
            color: #37474f;
        }

        .alert-success ul li {
            margin-bottom: 4px;
        }

        .display-btu {
            font-size: 34px;
            font-weight: 800;
            color: var(--primary-hover);
            margin: 8px 0;
        }

        .caption {
            font-size: 11px;
            color: #689f38;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2>Dimensionamento de BTUs</h2>
    </div>
    
    <form action="" method="POST">
        <div class="form-group">
            <label for="largura">Largura do local (m):</label>
            <input type="number" id="largura" name="largura" step="0.01" min="0.01" required 
                   placeholder="Ex: 4.50" value="<?php echo isset($_POST['largura']) ? htmlspecialchars($_POST['largura']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="comprimento">Comprimento do local (m):</label>
            <input type="number" id="comprimento" name="comprimento" step="0.01" min="0.01" required 
                   placeholder="Ex: 6.20" value="<?php echo isset($_POST['comprimento']) ? htmlspecialchars($_POST['comprimento']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="tipo_ambiente">Finalidade do Ambiente:</label>
            <select id="tipo_ambiente" name="tipo_ambiente" required>
                <option value="" disabled selected>Escolha o tipo...</option>
                <option value="residencial" <?php echo (isset($_POST['tipo_ambiente']) && $_POST['tipo_ambiente'] === 'residencial') ? 'selected' : ''; ?>>Residencial</option>
                <option value="comercial" <?php echo (isset($_POST['tipo_ambiente']) && $_POST['tipo_ambiente'] === 'comercial') ? 'selected' : ''; ?>>Comercial</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Processar Capacidade</button>
    </form>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <strong>Erro de validação:</strong> <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <?php if ($resultado): ?>
        <div class="alert alert-success">
            <h3>Análise Concluída com Sucesso</h3>
            <ul>
                <li>Área Privativa: <strong><?php echo $resultado['area']; ?> m²</strong></li>
                <li>Perfil Predial: <strong><?php echo $resultado['tipo']; ?></strong></li>
            </ul>
            <div class="display-btu"><?php echo $resultado['total']; ?> BTUs</div>
            <p class="caption">(Correspondente à faixa técnica de até <?php echo $resultado['teto']; ?> m²)</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>