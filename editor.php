<?php
session_start();

$usersFile   = 'user.json';
$commitsFile = 'commits.json';
$linesFile   = 'lines.json';
$codeFile    = 'codea.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $newCode = $_POST['code'];
    $oldCode = file_exists($codeFile) ? file_get_contents($codeFile) : '';

    $oldLines = explode("\n", $oldCode);
    $newLines = explode("\n", $newCode);

    file_put_contents($codeFile, $newCode);

    $linesJson = file_exists($linesFile)
        ? json_decode(file_get_contents($linesFile), true)
        : [];

    $owners = [];
    foreach ($linesJson as $l) {
        $owners[$l['line']] = $l['users'];
    }
$updatedLines = [];
$currentUser = $_SESSION['username'];

$oldCount = count($oldLines);
$newCount = count($newLines);


if ($oldCount === $newCount) {

    for ($i = 0; $i < $newCount; $i++) {

        $lineNo = $i + 1;
        $oldText = $oldLines[$i] ?? '';
        $newText = $newLines[$i] ?? '';

        $prevUsers = $owners[$lineNo] ?? [];

        
        if ($oldText !== $newText) {
            if (!in_array($currentUser, $prevUsers)) {
                $prevUsers[] = $currentUser;
            }
        }

        $updatedLines[] = [
            'line'  => $lineNo,
            'users' => $prevUsers
        ];
    }

} 

else if ($newCount > $oldCount) {

    $iOld = 0;

    for ($iNew = 0; $iNew < $newCount; $iNew++) {

        $lineNo = $iNew + 1;

        
        if (!isset($oldLines[$iOld]) || $newLines[$iNew] !== $oldLines[$iOld]) {
            $updatedLines[] = [
                'line' => $lineNo,
                'users' => [$currentUser]
            ];
        } else {
            $updatedLines[] = [
                'line' => $lineNo,
                'users' => $owners[$iOld + 1] ?? []
            ];
            $iOld++;
        }
    }
}


file_put_contents(
    $linesFile,
    json_encode($updatedLines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

if (!file_exists($commitsFile)) {
    file_put_contents(
        $commitsFile,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

$commitsData = json_decode(file_get_contents($commitsFile), true);
if (!is_array($commitsData)) {
    $commitsData = [];
}

if ($oldCode !== $newCode) {

    $lineNumber = substr_count($newCode, "\n") + 1;

    $lastLine = '';
$pos = strrpos($newCode, "\n");

if ($pos !== false) {
    $lastLineText = substr($newCode, $pos + 1);
} else {
    $lastLineText = $newCode;
}


    $linesChanged = abs(count($newLines) - count($oldLines));

    $commitsData[] = [
        'username'      => $currentUser,
        'time'          => date('Y-m-d H:i:s'),
        'lines_changed' => $lineNumber,
        'code'          => $lastLineText
    ];
}

file_put_contents(
    $commitsFile,
    json_encode($commitsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode(['status' => 'success']);
exit();

}


?>

<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:tahoma;background:#f5f5f5;padding:20px}
.editor-wrapper{display:flex;border:1px solid #ccc;height:500px}
#line-numbers{width:120px;background:#eee;overflow:auto;font-family:monospace;padding:10px}
#code-editor{flex:1;font-family:monospace;padding:10px;border:none;outline:none}
</style>
</head>
<body>

<div>
    <strong><?php echo $_SESSION['fullname']; ?></strong>
    <form method="POST" action="logout.php" style="display:inline">
        <button type="submit">EXIT</button>
    </form>
</div>

<div class="editor-wrapper">
    <div id="line-numbers"></div>
    <textarea id="code-editor"><?php
        if (file_exists($codeFile)) {
            echo htmlspecialchars(file_get_contents($codeFile));
        }
    ?></textarea>
</div>
<script>
const editor = document.getElementById('code-editor');
const lineNumbers = document.getElementById('line-numbers');

let linesData = <?php
echo json_encode(
    file_exists($linesFile)
        ? json_decode(file_get_contents($linesFile), true)
        : []
);
?>;

function renderLines() {
    let html = '';
    linesData.forEach(l => {
        let usersDisplay = '';
        if (l.users && l.users.length > 0) {
            if (l.users.length === 1) {
                usersDisplay = l.users[0];
            } else {
                usersDisplay = l.users[0] + ', ...';
            }
        }
        html += `<div>${l.line} | ${usersDisplay}</div>`;
    });
    lineNumbers.innerHTML = html;
}

renderLines();

function fetchLinesData() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'lines.json', true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    linesData = JSON.parse(xhr.responseText);
                    renderLines();
                } catch (e) {
                    console.error(e);
                }
            } else {
                console.error('XHR Error:', xhr.status);
            }
        }
    };

    xhr.send();
}

fetchLinesData();
setInterval(fetchLinesData, 1000);

let timer;
editor.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(save, 500);
});

function save() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    JSON.parse(xhr.responseText);
                    location.reload();
                } catch (e) {
                    console.error(e);
                }
            }
        }
    };

    xhr.send('code=' + encodeURIComponent(editor.value));
}
</script>

</body>
</html>
