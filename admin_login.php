<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require "db.php";
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $row['id'];
            $_SESSION['admin_username'] = $username;
            header("Location: admin_dashboard.php");
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold:   #c9a84c;
            --gold-h: #a07830;
            --bg:     #f4f4f2;
            --white:  #ffffff;
            --text:   #2d2d2d;
            --muted:  #888;
            --border: #e0ddd8;
            --error:  #c0392b;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: var(--bg);
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text);
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 48px 44px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .sub {
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 36px;
        }

        .error {
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-radius: 6px;
            color: var(--error);
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 18px;
        }

        input {
            display: block;
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 12px;
            background: #fafaf9;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.12);
        }

        input::placeholder { color: #bbb; }

        button {
            display: block;
            width: 100%;
            padding: 13px;
            margin-top: 6px;
            background: var(--gold);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        button:hover { background: var(--gold-h); transform: translateY(-1px); }

        .back {
            display: inline-block;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back:hover { color: var(--gold); }
    </style>
</head>
<body>
    <div class="card">
        <p class="card-title">Admin</p>
        <p class="sub">Guest Management</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <input type="text"     name="username" placeholder="Username" required autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit">Sign In</button>
        </form>

        <a href="index.html" class="back">← Back to Invitation</a>
    </div>
</body>
</html>
