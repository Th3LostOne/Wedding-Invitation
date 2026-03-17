<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $posted_name = trim($_POST['guest_name'] ?? '');

    // Verify the submitted name matches the session-verified guest
    if (empty($_SESSION['rsvp_guest']) || $_SESSION['rsvp_guest'] !== $posted_name) {
        header("Location: index.html");
        exit;
    }

    $main_name = $posted_name;
    $main_food = trim($_POST['food'] ?? '');

    $valid_foods = ['Prime Rib', 'Salmon', 'Risotto'];
    if (empty($main_name) || !in_array($main_food, $valid_foods)) {
        die("Invalid input. <a href='index.html'>Go back</a>");
    }

    $extra_names = isset($_POST['extra_names']) ? array_map('trim', $_POST['extra_names']) : [];
    $extra_foods = isset($_POST['extra_foods']) ? array_map('trim', $_POST['extra_foods']) : [];

    foreach ($extra_names as $n) {
        if (empty($n) || strlen($n) > 100) die("Invalid extra guest name.");
    }
    foreach ($extra_foods as $f) {
        if (!in_array($f, $valid_foods)) die("Invalid food choice for extra guest.");
    }

    $party_names_str = implode(" | ", $extra_names) ?: null;
    $party_foods_str = implode(" | ", $extra_foods) ?: null;

    $stmt = $conn->prepare(
        "UPDATE guests SET food_choice = ?, party_names = ?, party_foods = ?, rsvp_submitted_at = NOW()
         WHERE name = ?"
    );
    $stmt->bind_param("ssss", $main_food, $party_names_str, $party_foods_str, $main_name);

    if ($stmt->execute()) {
        // Clear session after successful save
        unset($_SESSION['rsvp_guest']);
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Thank You — Our Wedding</title>
            <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Parisienne&display=swap" rel="stylesheet">
            <style>
                body {
                    min-height: 100vh; background: radial-gradient(circle at top, #fffaf3, #f3e9dc);
                    font-family: 'Playfair Display', serif; display: flex;
                    justify-content: center; align-items: center; padding: 20px;
                    opacity: 0; animation: fadeIn 0.8s ease forwards;
                }
                @keyframes fadeIn { to { opacity: 1; } }
                .card {
                    background: white; max-width: 500px; width: 100%; padding: 60px 45px;
                    border-radius: 18px; text-align: center;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: 1px solid #d4af37;
                }
                h1 { font-family: 'Parisienne', cursive; font-size: 52px; color: #d4af37; margin-bottom: 16px; }
                p  { color: #777; line-height: 1.7; margin-bottom: 32px; font-size: 16px; }
                a  {
                    display: inline-block; padding: 15px 38px; background: #d4af37;
                    color: white; text-decoration: none; border-radius: 10px;
                    font-size: 12px; letter-spacing: 2.5px; text-transform: uppercase;
                    transition: 0.3s;
                }
                a:hover { background: #c4a02f; transform: translateY(-1px); }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>Thank You!</h1>
                <p>Your meal selections have been saved.<br>
                   We can't wait to celebrate with you on September 27th!</p>
                <a href="index.html">← Back to Invitation</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        $stmt->close();
        die("A database error occurred. <a href='index.html'>Go back</a>");
    }
}
?>
