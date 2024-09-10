<?php
    require_once 'db_connections.inc.php';

    $err_msg = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $err_msg = 'Username and password cannot be empty';
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    session_regenerate_id(true);

                    header('Location: index.php');
                    exit();
                } else {
                    $err_msg = 'Invalid username or password';
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password_hash', $hashed_password);
                $stmt->execute();

                $new_user_id = $pdo->lastInsertId();

                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;

                session_regenerate_id(true);

                header('Location: index.php');
                exit();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyborg Chat - Login</title>
    <link rel="stylesheet" href="assets/css/login-styles.css">
</head>
<body>
    <form method="post" class="login-wrapper w-72 mx-auto" style="margin-top: 150px;">
        <h1 class="w-full text-center my-4 font-semibold text-xl">Cyborg Developers</h1>
        <label class="input input-bordered flex items-center gap-2 mb-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 16 16"
              fill="currentColor"
              class="h-4 w-4 opacity-70">
              <path
                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
            </svg>
            <input name="username" type="text" class="grow" placeholder="Username" />
        </label>
        <label class="input input-bordered flex items-center gap-2 mb-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 16 16"
              fill="currentColor"
              class="h-4 w-4 opacity-70">
              <path
                fill-rule="evenodd"
                d="M14 6a4 4 0 0 1-4.899 3.899l-1.955 1.955a.5.5 0 0 1-.353.146H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2.293a.5.5 0 0 1 .146-.353l3.955-3.955A4 4 0 1 1 14 6Zm-4-2a.75.75 0 0 0 0 1.5.5.5 0 0 1 .5.5.75.75 0 0 0 1.5 0 2 2 0 0 0-2-2Z"
                clip-rule="evenodd" />
            </svg>
            <input name="password" type="password" class="grow" placeholder="Password"/>
        </label>
        <?php 
            if($err_msg != ""){
                echo '<span class="p-2 text-red-900 bg-red-200 block my-2">'.$err_msg.'</span>';
            }
        ?>
        <input name="submit" type="submit" value="Continue" class="btn btn-neutral w-full my-3">
    </form>
</body>
</html>