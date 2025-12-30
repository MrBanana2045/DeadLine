Create a file server for the editor using this source code
```php
<?php
session_start();

$serverListFile = 'serverlist.json';
$servers = file_exists($serverListFile) ? json_decode(file_get_contents($serverListFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serverName'])) {
    $serverName = trim($_POST['serverName']);

    $serverDir = __DIR__ . '/servers/' . $serverName;
    if(!is_dir($serverDir)){
        mkdir($serverDir, 0777, true);
    }

$filesToCopy = ['editor.php', 'lineNumber.php'];
foreach($filesToCopy as $file){
    $source = __DIR__ . '/files/' . $file;
    $dest   = $serverDir . '/' . $file;

    if(file_exists($source)){
        copy($source, $dest);
    }
}


    $newServer = [
        'serverName' => $serverName,
        'username'   => $_SESSION['username'],
        'time'       => date('Y-m-d H:i:s')
    ];
    $servers[] = $newServer;

    file_put_contents($serverListFile, json_encode($servers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    exit();
}

echo json_encode(['servers'=>$servers]);
?>
```
* In the <pre>mkdir($serverDir, 0777, true);</pre> field, it adds the server name to the servers folder.
* In this part <pre>copy($source, $dest);</pre> editor copies lineNumber from the file folder to the desired server side.

If you want to make it easy for users to create a server, add this source code to the index file.
```html
<?php
session_start();

if(!isset($_SESSION['username'])){
    header('Location: .../login.php');
    exit();
}
?>

<button id="new-server-btn">New Server</button>
<div id="server-list"></div>

<script>
document.getElementById('new-server-btn').addEventListener('click', () => {
    let serverName = prompt("server name");
    if (!serverName) return;

    fetch('crserver.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'serverName=' + encodeURIComponent(serverName)
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success'){
            alert("created server");
            renderServerList(data.servers);
        }
    })
});

function renderServerList(servers){
    const container = document.getElementById('server-list');
    container.innerHTML = '';
    servers.forEach(s => {
        container.innerHTML += `<div><a href='http://LINK/servers/${s.serverName}'>${s.serverName}</a> | ${s.username} | ${s.time}</div>`;
    });
}

fetch('crserver.php')
    .then(r => r.json())
    .then(data => renderServerList(data.servers || []));
</script>
```
