<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERVER</title>
    <style>
        #container {
            display: flex;
            width: 100%; 
        }

        .left-content, .right-content {
            flex: 1; 
            padding: 20px;
        }
    </style>
    <meta http-equiv="refresh" content="5;url=http://iotserver.com/webserver.php">
</head>
<body>
    <div id="container">
        <div class="left-content">
            <?php
            $xmlFilePath = 'data.xml';
            if (isset($_GET['deviceTimestamp'], $_GET['temperature'], $_GET['humidity'], $_GET['state'], $_GET['temperature_threshold'], $_GET['humidity_threshold'])) {
                $device_timestamp = $_GET['deviceTimestamp'];
                $temperature = $_GET['temperature'];
                $humidity = $_GET['humidity'];
                $state = $_GET['state'];
                $temperature_threshold = $_GET['temperature_threshold'];
                $humidity_threshold = $_GET['humidity_threshold'];
                $server_timestamp = date('Y-m-d H:i:s');

                if (file_exists($xmlFilePath)) {
                    $xml = simplexml_load_file($xmlFilePath);
                    if ($xml === false) {
                        echo "<p>Error loading XML file.</p>";
                        exit;
                    }
                } else {
                    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><records></records>');
                }

                $record = $xml->addChild('record');
                $record->addChild('deviceTimestamp', htmlspecialchars($device_timestamp));
                $record->addChild('temperature', htmlspecialchars($temperature));
                $record->addChild('humidity', htmlspecialchars($humidity));
                $record->addChild('state', htmlspecialchars($state));
                $record->addChild('temperatureThreshold', htmlspecialchars($temperature_threshold));
                $record->addChild('humidityThreshold', htmlspecialchars($humidity_threshold));
                $record->addChild('serverTimestamp', htmlspecialchars($server_timestamp));
                $xml->asXML($xmlFilePath);

                echo '<p>Data saved successfully.</p>';
            }

            if (file_exists($xmlFilePath)) {
                $xml = simplexml_load_file($xmlFilePath);
                if ($xml) {
                    $highWindCount = 0;
                    $collisionCount = 0;
                    $totalRecords = count($xml->record);

                    echo "<h2>Data from XML</h2>";
                    echo "<p>Total timestamps recorded: $totalRecords</p>";
                    $lastRecord = $xml->record[$totalRecords - 1];
                    echo "<p>Current Temperature Threshold: " . htmlspecialchars($lastRecord->temperatureThreshold) . "°C</p>";
                    echo "<p>Current Humidity Threshold: " . htmlspecialchars($lastRecord->humidityThreshold) . "%</p>";

                    echo "<table border='1'>";
                    echo "<tr><th>Device Timestamp</th><th>Temperature</th><th>Humidity</th><th>State</th><th>Details</th></tr>";

                    foreach ($xml->record as $record) {
                        $tempAbove = floatval($record->temperature) > floatval($record->temperatureThreshold);
                        $humidityAbove = floatval($record->humidity) > floatval($record->humidityThreshold);

                        if ($tempAbove && $humidityAbove || $record->state == "collision" || $record->state == "windy") {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($record->deviceTimestamp) . "</td>";
                            echo "<td>" . htmlspecialchars($record->temperature) . "°C</td>";
                            echo "<td>" . htmlspecialchars($record->humidity) . "%</td>";
                            echo "<td>" . htmlspecialchars($record->state) . "</td>";
                            echo "<td>";
                            if ($tempAbove && $humidityAbove) echo "Above Temp & Humidity Thresholds ";
                            if ($record->state == "collision") echo "Collision Event; ";
                            if ($record->state == "windy") {
                                $highWindCount++;
                                echo "High Wind State; ";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        if ($record->state == "collision") $collisionCount++;
                    }
                    echo "</table>";
                    echo "<p>Total High Wind States Recorded: $highWindCount</p>";
                    echo "<p>Total Collision Events Recorded: $collisionCount</p>";
                } else {
                    echo "<p>Error loading XML file.</p>";
                }
            } else {
                echo "<p>No data available.</p>";
            }
            ?>
        </div>
        <div class="right-content">
            <?php echo "<iframe src='http://iotserver.com/canvasjs3.6/examples/07-scatter-bubble-charts/assignment1canvas.php' width='100%' height='400px'></iframe>"; ?>
        </div>
    </div>
</body>
</html>
