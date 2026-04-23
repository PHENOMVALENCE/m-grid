<?php
declare(strict_types=1);

$hash = "";
$password = "";
$verifyHash = "";
$verifyResult = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = (string) ($_POST["action"] ?? "generate");
    $password = (string) ($_POST["password"] ?? "");

    if ($action === "generate" && $password !== "") {
        // Same approach as login/register flow.
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($action === "verify") {
        $verifyHash = (string) ($_POST["verify_hash"] ?? "");
        if ($password !== "" && $verifyHash !== "") {
            $verifyResult = password_verify($password, $verifyHash);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        .result {
            margin-top: 20px;
            word-break: break-all;
            background: #eee;
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Password Hash Generator</h2>
    <p>Use this to generate a hash, then update <code>users.password_hash</code> in your database.</p>

    <form method="POST">
        <input type="hidden" name="action" value="generate">
        <label>Enter Password:</label>
        <input type="text" name="password" required>
        <button type="submit">Generate Hash</button>
    </form>

    <?php if (!empty($hash)): ?>
        <div class="result">
            <strong>Original Password:</strong><br>
            <?php echo htmlspecialchars($password); ?><br><br>

            <strong>Generated Hash (copy this):</strong><br>
            <textarea readonly rows="3" style="width:100%;margin-top:8px;"><?php echo htmlspecialchars($hash); ?></textarea>
        </div>
    <?php endif; ?>

    <hr style="margin: 28px 0;">
    <h3>Verify Password Against Existing Hash</h3>
    <form method="POST">
        <input type="hidden" name="action" value="verify">
        <label>Password:</label>
        <input type="text" name="password" required>
        <label>Existing Hash:</label>
        <textarea name="verify_hash" rows="3" style="width:100%;margin-top:10px;" required><?php echo htmlspecialchars($verifyHash); ?></textarea>
        <button type="submit">Verify</button>
    </form>
    <?php if ($verifyResult !== null): ?>
        <div class="result">
            <strong>Verification Result:</strong><br>
            <?php echo $verifyResult ? "MATCH (password is correct for this hash)" : "NO MATCH"; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>