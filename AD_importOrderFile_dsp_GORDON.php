<?php
function dspExcelTest($screenData) {
?>
<div id='stdPage'>
	<h1 style="padding-left: 14px;">Import Order File<br></h1>
	<div id="inputContainer">
		<form method="POST" id="form1" name="form1" enctype="multipart/form-data" onSubmit="return false;">
			<span onmouseover="tooltip.show('XLSX file containing in order: username, sequence #, comment field, and active/inactive')" onmouseout="tooltip.hide();">
				<img src="images/Info_icon_20px.png" width="15" height="15">
			</span>
			<input type="file" accept=".xlsx" id="myFile" name="myFile" onchange="handleFile(event)">
			<button onclick="myFunction()">Submit</button>
		</form>
		<div style="clear:both"></div>
	</div>
	<div><br><br><br><div id="excel_data" class="mt-5"></div></div>
	<div id="errorMsg" class="ui-state-error ui-corner-all ui-helper-hidden"></div>
	<div id="successMsg" class="ui-state-highlight ui-corner-all ui-helper-hidden"></div>
	<br/><hr><br/>
	<div id="dspResults">
		<p id="resultsCallback" style="display:none;"></p>
		<!-- use this to display table name upon receipt of results from AJAX request -->
		<input type="hidden" name="hideVal" id="hideVal" value="">
	</div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.2/xlsx.full.min.js"></script>
<script>
    function handleFile(event) {
        const input = event.target;
        const reader = new FileReader();
        reader.onload = function() {
            const data = new Uint8Array(reader.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
            displayExcelData(json);
        };
        reader.readAsArrayBuffer(input.files[0]);
    }

    function displayExcelData(data) {
        let table = "<table border='1'>";
        for (let row = 0; row < data.length; row++) {
            table += "<tr>";
            for (let cell = 0; cell < data[row].length; cell++) {
                table += "<td>" + data[row][cell] + "</td>";
            }
            table += "</tr>";
        }
        table += "</table>";
        document.getElementById('excel_data').innerHTML = table;
    }
</script>
<?php 	
}
?>