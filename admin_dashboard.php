<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require "db.php";

// ── CSRF token ──────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Actions ─────────────────────────────────────────────────
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        die('Invalid request.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $limit = max(1, min(10, (int)($_POST['guest_limit'] ?? 1)));
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO guests (name, guest_limit) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $limit);
            $flash = $stmt->execute() ? "Guest added." : "Could not add guest — name may already exist.";
            $stmt->close();
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['guest_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM guests WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $flash = "Guest removed.";
        }
    }
}

// ── Stats ────────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(rsvp_submitted_at IS NOT NULL)    AS rsvped,
        SUM(rsvp_submitted_at IS NULL)        AS pending,
        SUM(guest_limit)                      AS total_seats,
        SUM(food_choice = 'Prime Rib')        AS prime_rib,
        SUM(food_choice = 'Salmon')           AS salmon,
        SUM(food_choice = 'Risotto')          AS risotto
    FROM guests
")->fetch_assoc();

// ── Guest list ───────────────────────────────────────────────
$guests = $conn->query("SELECT * FROM guests ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold:   #c9a84c;
            --gold-h: #a07830;
            --bg:     #f4f4f2;
            --white:  #ffffff;
            --text:   #2d2d2d;
            --muted:  #888;
            --light:  #b0b0b0;
            --border: #e0ddd8;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            font-family: system-ui, -apple-system, sans-serif;
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
        }

        /* ── Header ── */
        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: 0.3px;
        }

        header a {
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        header a:hover { color: var(--gold); }

        /* ── Layout ── */
        main { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }

        /* ── Flash ── */
        .flash {
            background: #fefbe8;
            border: 1px solid #e8d88a;
            border-radius: 8px;
            padding: 11px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #7a6010;
        }

        /* ── Section label ── */
        .section-title {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--light);
            margin-bottom: 14px;
            font-weight: 600;
        }

        /* ── Stats Grid ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px 18px;
            text-align: center;
        }

        .stat-card .num {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 600;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card .label {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--light);
        }

        /* ── Meal bars ── */
        .meal-bars { margin-bottom: 32px; }

        .bar-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .bar-label { width: 100px; font-size: 13px; color: var(--muted); flex-shrink: 0; }

        .bar-track {
            flex: 1;
            background: #ece9e4;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: var(--gold);
            border-radius: 4px;
            transition: width 0.8s ease;
        }

        .bar-count { font-size: 13px; color: var(--gold); width: 24px; text-align: right; flex-shrink: 0; font-weight: 600; }

        /* ── Add Guest ── */
        .add-section {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .form-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .form-row input,
        .form-row select {
            flex: 1;
            min-width: 150px;
            padding: 10px 12px;
            background: #fafaf9;
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-row input:focus,
        .form-row select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.10);
        }

        .form-row input::placeholder { color: #bbb; }

        .btn-add {
            padding: 10px 24px;
            background: var(--gold);
            border: none;
            border-radius: 7px;
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            white-space: nowrap;
        }

        .btn-add:hover { background: var(--gold-h); transform: translateY(-1px); }

        /* ── Table ── */
        .table-wrap {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            padding: 13px 16px;
            text-align: left;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--light);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            font-weight: 600;
            background: #fafaf9;
        }

        tbody tr { border-bottom: 1px solid #f0ede8; transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #faf8f5; }

        tbody td {
            padding: 13px 16px;
            color: var(--muted);
            vertical-align: middle;
        }

        .td-name { color: var(--text); font-weight: 600; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .badge-confirmed { background: #fef8e7; color: #a07830; border: 1px solid #f0d88a; }
        .badge-pending   { background: #f5f5f5; color: #aaa;    border: 1px solid #e5e5e5; }

        .btn-del {
            background: none;
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--light);
            padding: 5px 12px;
            font-size: 12px;
            font-family: inherit;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-del:hover { border-color: #e74c3c; color: #e74c3c; background: #fdf5f5; }

        .empty-row { text-align: center; padding: 32px; color: var(--light); }

        @media (max-width: 600px) {
            header { padding: 14px 16px; }
            main   { padding: 20px 14px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<header>
    <h1>Admin</h1>
    <a href="admin_logout.php">Sign out</a>
</header>

<main>

    <?php if ($flash): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="num"><?= (int)$stats['total'] ?></div>
            <div class="label">Invited</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= (int)$stats['rsvped'] ?></div>
            <div class="label">RSVPs In</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= (int)$stats['pending'] ?></div>
            <div class="label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= (int)$stats['total_seats'] ?></div>
            <div class="label">Total Seats</div>
        </div>
    </div>

    <!-- Meal breakdown -->
    <?php
    $meals = [
        'Prime Rib' => (int)$stats['prime_rib'],
        'Salmon'    => (int)$stats['salmon'],
        'Risotto'   => (int)$stats['risotto'],
    ];
    $maxMeal = max(1, max($meals));
    ?>
    <div class="meal-bars">
        <p class="section-title">Meal Selections</p>
        <?php foreach ($meals as $label => $count): ?>
        <div class="bar-row">
            <span class="bar-label"><?= $label ?></span>
            <div class="bar-track">
                <div class="bar-fill" style="width:<?= round($count / $maxMeal * 100) ?>%"></div>
            </div>
            <span class="bar-count"><?= $count ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Add Guest -->
    <div class="add-section">
        <p class="section-title">Add Guest</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <input type="text" name="name" placeholder="Full Name" required maxlength="100">
                <select name="guest_limit">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> <?= $i === 1 ? 'guest' : 'guests' ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-add">Add Guest</button>
            </div>
        </form>
    </div>

    <!-- Guest Table -->
    <p class="section-title">Guest List</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Party Size</th>
                    <th>RSVP</th>
                    <th>Meal</th>
                    <th>Party Members</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($guests && $guests->num_rows > 0): ?>
                <?php while ($g = $guests->fetch_assoc()): ?>
                <tr>
                    <td class="td-name"><?= htmlspecialchars($g['name']) ?></td>
                    <td><?= (int)$g['guest_limit'] ?></td>
                    <td>
                        <?php if ($g['rsvp_submitted_at']): ?>
                            <span class="badge badge-confirmed">Confirmed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $g['food_choice'] ? htmlspecialchars($g['food_choice']) : '—' ?></td>
                    <td style="color:#bbb;"><?= $g['party_names'] ? htmlspecialchars($g['party_names']) : '—' ?></td>
                    <td style="color:#bbb;"><?= $g['rsvp_submitted_at'] ? date('M j, Y', strtotime($g['rsvp_submitted_at'])) : '—' ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($g['name'])) ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="action"     value="delete">
                            <input type="hidden" name="guest_id"   value="<?= (int)$g['id'] ?>">
                            <button type="submit" class="btn-del">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty-row">No guests yet. Add one above.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>
</body>
</html>
