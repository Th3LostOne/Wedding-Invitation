<?php
session_start();

// Only allow access if the session guest matches the URL parameter
$url_guest = trim($_GET['guest'] ?? '');
if (empty($_SESSION['rsvp_guest']) || $_SESSION['rsvp_guest'] !== $url_guest) {
    header("Location: index.html");
    exit;
}

$guest_name    = htmlspecialchars($url_guest);
$limit         = isset($_GET['limit']) ? (int)$_GET['limit'] : 1;
$extra_allowed = $limit - 1;
?>
<!DOCTYPE html>
<html lang="en">
    <?php /* guest_name / limit already set above */ ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Menu Selection</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #d4af37; --dark: #2b2b2b; --bg: #fdfaf6; }
        body { 
            background: radial-gradient(circle at top, #fffaf3 0%, #f3e9dc 100%);
            font-family: 'Playfair Display', serif;
            color: var(--dark);
            display: flex; justify-content: center; padding: 40px 20px;
        }
        .container { 
            background: white; width: 100%; max-width: 600px;
            padding: 50px 40px; border-radius: 20px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.08);
            border: 1px solid var(--gold);
            text-align: center;
        }
        h1 { font-family: 'Great Vibes', cursive; font-size: 50px; color: var(--gold); margin: 0; }
        .subtitle { text-transform: uppercase; letter-spacing: 2px; font-size: 13px; color: #888; margin-bottom: 40px; }
        
        /* Guest Card Styling */
        .guest-card { 
            background: #fffcf9; border: 1px solid #eee; 
            padding: 25px; border-radius: 12px; margin-bottom: 25px; 
            text-align: left; transition: 0.3s;
        }
        .guest-card h3 { margin-top: 0; color: var(--gold); border-bottom: 1px solid #f0e6d2; padding-bottom: 10px; }
        
        .meal-option {
            display: flex; align-items: center; padding: 12px;
            margin: 8px 0; border: 1px solid #eee; border-radius: 8px;
            cursor: pointer; background: white;
        }
        .meal-option:hover { border-color: var(--gold); }
        .meal-option input { margin-right: 15px; accent-color: var(--gold); }

        .add-guest-btn {
            background: none; border: 2px dashed var(--gold);
            color: var(--gold); padding: 15px; width: 100%;
            border-radius: 10px; cursor: pointer; font-weight: 600;
            margin-bottom: 30px; transition: 0.3s;
        }
        .add-guest-btn:hover { background: #fffcf0; }

        .submit-btn {
            background: var(--gold); color: white; border: none;
            padding: 18px; width: 100%; border-radius: 8px;
            font-size: 18px; font-weight: 600; cursor: pointer;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }
        .submit-btn:hover { background: #b8952d; transform: translateY(-2px); }

        input[type="text"] {
            width: 100%; padding: 12px; margin-top: 10px;
            border: 1px solid #ddd; border-radius: 6px; font-family: inherit;
        }
    </style>
</head>

<div class="container">
    <h1>Welcome, <?= $guest_name ?>!</h1>
    <p class="subtitle">Personalize your party's menu</p>

    <form action="save_menu.php" method="POST">
        <input type="hidden" name="guest_name" value="<?= $guest_name ?>">

        <div class="guest-card">
            <h3>Primary Guest</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Selection for: <?= $guest_name ?></p>
            
            <label class="meal-option">
                <input type="radio" name="food" value="Prime Rib" required>
                <div>
                    <strong>Prime Rib Roast</strong>
                    <p style="margin: 0; font-size: 13px; color: #777; font-style: italic;">Slow-roasted herb crust served with garlic mash and red wine jus.</p>
                </div>
            </label>

            <label class="meal-option">
                <input type="radio" name="food" value="Salmon">
                <div>
                    <strong>Atlantic Salmon</strong>
                    <p style="margin: 0; font-size: 13px; color: #777; font-style: italic;">Pan-seared with lemon-dill butter and seasonal grilled greens.</p>
                </div>
            </label>

            <label class="meal-option">
                <input type="radio" name="food" value="Risotto">
                <div>
                    <strong>Mushroom Risotto</strong>
                    <p style="margin: 0; font-size: 13px; color: #777; font-style: italic;">Creamy Arborio rice with wild forest mushrooms and truffle oil.</p>
                </div>
            </label>
        </div>

        <div id="additionalGuests"></div>

        <button type="button" class="add-guest-btn" onclick="addNewGuest()">+ Add Another Guest</button>
        <button type="submit" class="submit-btn">Confirm All Selections</button>
    </form>
</div>

<script>
let count = 0;
const totalLimit = <?php echo isset($_GET['limit']) ? (int)$_GET['limit'] : 1; ?>;

function addNewGuest() {
    // Current total = 1 (Primary) + count (Extras)
    if ((count + 1) < totalLimit) {
        count++;
        const div = document.createElement('div');
        div.className = 'guest-card';
        div.innerHTML = `
            <h3>Guest #${count + 1}</h3>
            <input type="text" name="extra_names[]" placeholder="Enter Guest Full Name" required style="margin-bottom: 15px;">
            <div style="margin-top:5px;">
                ${mealChoicesHTML(count)}
            </div>
        `;
        document.getElementById('additionalGuests').appendChild(div);
        
        // Hide button if we just hit the limit
        if ((count + 1) === totalLimit) {
            document.querySelector('.add-guest-btn').style.display = 'none';
        }
    } else {
        alert("Maximum guest limit reached for your party.");
    }
}
// We define the HTML once so it's easy to update the descriptions in one place
const mealChoicesHTML = (index) => `
    <label class="meal-option">
        <input type="radio" name="extra_foods[${index}]" value="Prime Rib" required>
        <div>
            <strong>Prime Rib Roast</strong>
            <p style="margin: 0; font-size: 12px; color: #777; font-style: italic;">Slow-roasted herb crust with garlic mash.</p>
        </div>
    </label>
    <label class="meal-option">
        <input type="radio" name="extra_foods[${index}]" value="Salmon">
        <div>
            <strong>Atlantic Salmon</strong>
            <p style="margin: 0; font-size: 12px; color: #777; font-style: italic;">Pan-seared with lemon-dill butter.</p>
        </div>
    </label>
    <label class="meal-option">
        <input type="radio" name="extra_foods[${index}]" value="Risotto">
        <div>
            <strong>Mushroom Risotto</strong>
            <p style="margin: 0; font-size: 12px; color: #777; font-style: italic;">Wild mushrooms and truffle oil.</p>
        </div>
    </label>
`;
</script>
</body>
</html>