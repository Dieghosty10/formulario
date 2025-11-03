<?php
$errors = [];
$saved = false;
$show_json = false;

$nombre = $email = $telefono = $asunto = $mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['view_json'])) {
        $show_json = true;
    } else {
        $nombre   = trim($_POST['nombre']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $asunto   = trim($_POST['asunto']   ?? '');
        $mensaje  = trim($_POST['mensaje']  ?? '');

        if ($nombre === '') $errors[] = 'Indica tu nombre.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Indica un correo válido.';
        if ($mensaje === '') $errors[] = 'Escribe un mensaje.';
        if ($telefono !== '' && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $telefono)) $errors[] = 'Revisa el teléfono.';

        if (!$errors) {
            $registro = [
                'nombre'   => $nombre,
                'email'    => $email,
                'telefono' => $telefono,
                'asunto'   => $asunto,
                'mensaje'  => $mensaje,
                'fecha'    => date('c'),
            ];

            $archivo = 'data.json';

            $datos = [];
            if (is_file($archivo)) {
                $contenido = file_get_contents($archivo);
                if ($contenido !== false && trim($contenido) !== '') {
                    $dec = json_decode($contenido, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                        $datos = $dec;
                    }
                }
            }

            $datos[] = $registro;
            $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (@file_put_contents($archivo, $json, LOCK_EX) === false) {
                $errors[] = 'No se pudo guardar la información. Revisa los permisos del servidor.';
            } else {
                $saved = true;
                $nombre = $email = $telefono = $asunto = $mensaje = '';
            }
        }
    }
}

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if ($show_json) {
    $archivo = 'data.json';
    $json_data = is_file($archivo) ? file_get_contents($archivo) : '[]';
    $datos_json = json_decode($json_data, true);
    $json_formateado = json_encode($datos_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1 class="title">Contacto</h1>
                <p class="subtitle">Déjanos tu mensaje y te responderemos.</p>
                
                <div class="view-toggle">
                    <form method="post">
                        <?php if (!$show_json): ?>
                            <button type="submit" name="view_json" class="btn secondary">Ver JSON</button>
                        <?php else: ?>
                            <button type="button" onclick="window.location.href=''" class="btn secondary">Volver</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="content">
                <?php if ($show_json): ?>
                    <div class="alert info">
                        <b>Vista JSON</b> - Datos almacenados en data.json
                    </div>
                    
                    <div class="json-viewer" id="json-viewer">
                        <?php
                        function highlight_json($json) {
                            $json = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
                            $json = preg_replace('/&quot;(.*?)&quot;:\s*/', '<span class="json-key">&quot;$1&quot;</span>: ', $json);
                            $json = preg_replace('/: &quot;(.*?)&quot;/', ': <span class="json-string">&quot;$1&quot;</span>', $json);
                            $json = preg_replace('/: ([\d\.]+)/', ': <span class="json-number">$1</span>', $json);
                            $json = preg_replace('/: (true|false)/', ': <span class="json-boolean">$1</span>', $json);
                            $json = preg_replace('/: null/', ': <span class="json-null">null</span>', $json);
                            return $json;
                        }
                        echo '<pre>' . highlight_json($json_formateado ?? '[]') . '</pre>';
                        ?>
                    </div>

                <?php else: ?>
                    <?php if ($saved && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <div class="alert success">
                            <b>Mensaje enviado</b>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert error">
                            <b>Revisa los siguientes puntos:</b>
                            <ul>
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form class="form" method="post">
                        <div class="grid-2">
                            <div class="field">
                                <label for="nombre">Nombre</label>
                                <input class="input" type="text" id="nombre" name="nombre" placeholder="Nombre y apellidos" value="<?= e($nombre) ?>" required autocomplete="name">
                            </div>
                            <div class="field">
                                <label for="email">Correo</label>
                                <input class="input" type="email" id="email" name="email" placeholder="tu@correo.com" value="<?= e($email) ?>" required autocomplete="email">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="field">
                                <label for="telefono">Teléfono (opcional)</label>
                                <input class="input" type="tel" id="telefono" name="telefono" placeholder="+34 600 123 456" value="<?= e($telefono) ?>" autocomplete="tel">
                            </div>
                            <div class="field">
                                <label for="asunto">Asunto (opcional)</label>
                                <input class="input" type="text" id="asunto" name="asunto" placeholder="Asunto" value="<?= e($asunto) ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="mensaje">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" class="input" placeholder="Escribe tu mensaje..." required><?= e($mensaje) ?></textarea>
                        </div>

                        <div class="actions">
                            <div class="btn-group">
                                <button class="btn" type="submit">Enviar mensaje</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>