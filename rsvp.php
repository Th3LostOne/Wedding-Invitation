<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? '');

    if (!empty($name) && strlen($name) <= 100) {
        $stmt = $conn->prepare("SELECT id, guest_limit FROM guests WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $limit = (int)$row['guest_limit'];

            // Store verified guest in session so save_menu.php can confirm identity
            $_SESSION['rsvp_guest'] = $name;

            // Track when they first opened the RSVP
            $ts = $conn->prepare("UPDATE guests SET rsvp_submitted_at = NOW() WHERE name = ? AND rsvp_submitted_at IS NULL");
            $ts->bind_param("s", $name);
            $ts->execute();
            $ts->close();

            header("Location: menu.php?guest=" . urlencode($name) . "&limit=" . $limit);
            exit;
        } else {
            // Not on guest list — friendly error page
            $errorMsg = htmlspecialchars($name) . " was not found on the guest list.";
        }
        $stmt->close();
    } else {
        $errorMsg = "Please enter a valid name.";
    }
}

$error = $errorMsg ?? "Something went wrong. Please try again.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found — Our Wedding</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh; background: radial-gradient(circle at top, #fffaf3, #f3e9dc);
            font-family: 'Playfair Display', serif; display: flex;
            justify-content: center; align-items: center; padding: 20px;
        }
        .card {
            background: white; max-width: 480px; width: 100%; padding: 50px 40px;
            border-radius: 18px; text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: 1px solid #d4af37;
        }
        h2 { color: #d4af37; margin-bottom: 14px; }
        p  { color: #777; margin-bottom: 28px; line-height: 1.6; }
        a  {
            display: inline-block; padding: 14px 36px; background: #d4af37;
            color: white; text-decoration: none; border-radius: 10px;
            font-size: 13px; letter-spacing: 2px; text-transform: uppercase;
        }
        a:hover { background: #c4a02f; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Name Not Found</h2>
        <p><?= $error ?><br><br>Please check the spelling and try again, or contact the couple.</p>
        <a href="index.html">← Back to Invitation</a>
    </div>
</body>
</html>
