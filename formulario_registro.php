<?php
// Archivo: index.php

// Conexión a la base de datos
$servername = "localhost"; // Cambiar por tu servidor de base de datos
$username = "root"; // Cambiar por tu usuario de base de datos
$password = ""; // Cambiar por tu contraseña de base de datos
$database = "test"; // Cambiar por tu nombre de base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Iniciar o reanudar la sesión
session_start();

// Establecer el formulario actual a mostrar (registro o login)
$formulario_actual = isset($_SESSION['formulario_actual']) ? $_SESSION['formulario_actual'] : 'registro';

// Procesar formulario de registro
if (isset($_POST['submit_registro'])) {
    $correo = $_POST['correo'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    // Validar y procesar archivo
    if ($_FILES['archivo']['error'] == 0) {
        $archivo = $_FILES['archivo']['name'];
        $extension_permitida = array('jpg', 'pdf');
        $archivo_extension = pathinfo($archivo, PATHINFO_EXTENSION);

        if (!in_array(strtolower($archivo_extension), $extension_permitida)) {
            die("Error: Solo se permiten archivos JPG o PDF.");
        }

        $archivo_destino = "archivos/" . $archivo; // Directorio donde se guardarán los archivos
        move_uploaded_file($_FILES['archivo']['tmp_name'], $archivo_destino);
    } else {
        die("Error al subir el archivo.");
    }

    // Guardar en la base de datos
    $query = "INSERT INTO test.actividad (correo, contrasena, archivo) VALUES ('$correo', '$contrasena', '$archivo_destino')";

    if ($conn->query($query) === TRUE) {
        // Cambiar el formulario actual a login
        $_SESSION['formulario_actual'] = 'login';
        header("Location: index.php#login");
        exit();
    } else {
        echo "Error al registrar el usuario: " . $conn->error;
    }
}

// Procesar formulario de inicio de sesión
if (isset($_POST['submit_login'])) {
    $correo = $_POST['correo'];
    $contrasena_ingresada = $_POST['contrasena'];

    // Consulta para obtener la contraseña almacenada para el correo dado
    $query = "SELECT contrasena FROM test.actividad WHERE correo = '$correo'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $contrasena_almacenada = $row['contrasena'];

        // Verificar la contraseña utilizando password_verify
        if (password_verify($contrasena_ingresada, $contrasena_almacenada)) {
            // Iniciar sesión y redirigir al contenido privado
            $_SESSION['correo'] = $correo;
            header("Location: contenido_privado.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Contraseña incorrecta.";
            header("Location: index.php#login");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Usuario no encontrado.";
        header("Location: index.php#login");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro e Inicio de Sesión</title>
</head>
<body>

<?php if ($formulario_actual == 'registro') : ?>
    <!-- Formulario de Registro -->
    <h2>Registro</h2>
    <form action="#registro" method="post" enctype="multipart/form-data">
        Correo: <input type="email" name="correo" required><br>
        Contraseña: <input type="password" name="contrasena" required><br>
        Archivo (JPG o PDF): <input type="file" name="archivo" accept=".jpg, .pdf" required><br>
        <input type="submit" name="submit_registro" value="Registrarse">
    </form>
<?php elseif ($formulario_actual == 'login') : ?>
    <!-- Formulario de Inicio de Sesión -->
    <h2 id="login">Iniciar Sesión</h2>
    <?php if (isset($_SESSION['login_error'])) : ?>
        <p style="color: red;"><?php echo $_SESSION['login_error']; ?></p>
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>
    <form action="#login" method="post">
        Correo: <input type="email" name="correo" required><br>
        Contraseña: <input type="password" name="contrasena" required><br>
        <input type="submit" name="submit_login" value="Iniciar Sesión">
    </form>
<?php endif; ?>

</body>
</html>
