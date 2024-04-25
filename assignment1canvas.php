
<!DOCTYPE HTML>
<html>
<head>
<meta charset="UTF-8">
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
window.onload = function () {
    // url= http://iotserver.com/canvasjs3.6/examples/07-scatter-bubble-charts/assignment1canvas.html
    var tempData = [];
    var humidityData = [];

    function fetchData() {
        // Fetch the JSON data from the server
        $.getJSON("http://iotserver.com/convertAssignment1XMLtoJSON.php", function(jsonData) {
            jsonData.record.forEach(function(item) {
                var dateParts = item.deviceTimestamp.split(" ");
                var parts = dateParts[0].split('-');
                var timeParts = dateParts[1].split(':');
                var date = new Date(parts[0], parts[1]-1, parts[2], timeParts[0], timeParts[1], timeParts[2]);
                tempData.push({ x: date, y: parseFloat(item.temperature) });
                humidityData.push({ x: date, y: parseFloat(item.humidity) });
            });

            createChart();
        }).fail(function() {
            console.error("Failed to fetch data");
        });
    }

    function createChart() {
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            title: {
                text: "Temperature and Humidity Data Over Time"
            },
            axisX: {
                title: "Time",
                valueFormatString: "DD MMM, YYYY HH:mm"
            },
            axisY: {
                title: "Temperature (°C)",
                suffix: "°C"
            },
            axisY2: {
                title: "Humidity (%)",
                suffix: "%"
            },
            legend: {
                cursor: "pointer",
                itemclick: toggleDataSeries
            },
            data: [{
                type: "scatter",
                name: "Temperature",
                showInLegend: true,
                xValueFormatString: "DD MMM, YYYY HH:mm",
                yValueFormatString: "#,##0.##°C",
                dataPoints: tempData
            }, {
                type: "scatter",
                name: "Humidity",
                axisYType: "secondary",
                showInLegend: true,
                xValueFormatString: "DD MMM, YYYY HH:mm",
                yValueFormatString: "#,##0'%'",
                dataPoints: humidityData
            }]
        });
        chart.render();
    }

    function toggleDataSeries(e) {
        if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
            e.dataSeries.visible = false;
        } else {
            e.dataSeries.visible = true;
        }
        chart.render();
    }

    fetchData();
}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; max-width: 920px; margin: 0px auto;"></div>
</body>
</html>
