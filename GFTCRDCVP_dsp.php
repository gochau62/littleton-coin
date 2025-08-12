<?php
/*    ***************************************************  -->
<!--  * Program Name - GFTCRDCVP_dsp.php                *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/15/2025                         *  -->
<!--  ***************************************************   */
?>

<?php
function dspExcelTest($screenData) {    
// display background color
$backgroundColor = (isset($_SESSION['ErrorMessage']) && $_SESSION['ErrorMessage'] > " ") ? '#00FF00' : '#CCFFCC';
?>

<style>
    #stdPage {
        background: <?php echo $backgroundColor; ?>;
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(60,60,60,0.18);
        padding: 32px 34px 38px 34px;
        position: relative;
    }
    #stdPage h1 {
        font-size: 1.45rem;
        margin: 0 0 22px 0;
        letter-spacing: 1px;
        font-weight: 700;
        color: #1C4532;
        text-align: center;
        padding: 0;
    }
    #inputContainer {
        margin-bottom: -22px;
        text-align: center;
    }
    #inputContainer input[type="file"] {
        margin: 0 10px 0 0;
        font-size: 0.8rem;
        width: 60%;
        border-radius: 4px;
        padding: 4px;
        border: 1px solid #b4b4b4;
        background: #f8f8f8;
    }
    #inputContainer button {
        padding: 6px 21px;
        font-size: 0.8rem;
        cursor: pointer;
        margin-left: 4px;
    }
    #excel_data table {
        width: 100%;
    }
</style>

<div id='stdPage' style="background-color: <?php echo $backgroundColor; ?>;">
	<h1 style="padding-left: 14px;">Upload GiftCard File<br></h1>
	<div id="inputContainer">
		<form method="POST" id="form1" name="form1" enctype="multipart/form-data" onSubmit="return false;">
			<span onmouseover="tooltip.show('XLSX file containing in order: SKU#, Design, Giftcard#, CVV')" onmouseout="tooltip.hide();">
				<img src="images/Info_icon_20px.png" width="15" height="15">
			</span>
			<input type="file" accept=".xlsx" id="myFile" name="myFile" onchange="handleFile(event)">
			<button type="button" onclick="myFunction()">Submit</button>
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


<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.2/xlsx.full.min.js"></script>
<script>
function handleFile(event) {
    const input = event.target;
    const reader = new FileReader();
    reader.onload = function() {
        const data = new Uint8Array(reader.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const cardNums = cvcardNums(workbook);

        fetch('GFTCRDCVP_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=check_dupes&cardnums=${encodeURIComponent(JSON.stringify(cardNums))}`
        })
        .then(response => response.json())
        .then(dupes => {
            const dupeSet = new Set(dupes.map(d => d.trim()));
            if (dupeSet.size > 0) {
                swal(
                    "Duplicates Found",
                    "Total duplicates found: " + dupeSet.size + "\n\nAre you sure you want to proceed?",
                    "warning"
                );
            }
            let html = `
                <div style="
                    display: inline-block; 
                    border: 2px solid #1C4532; 
                    border-radius: 6px; 
                    padding: 10px; 
                    margin-bottom: 15px; 
                    background-color: #f9f9f9;
                    font-size: 0.9rem;
                ">
                    <strong style="display: block; margin-bottom: 6px; text-align: center;">Legend</strong>
                    <div style="margin-bottom: 4px;">
                        <span style="background-color: #ffd1d1ff; display: inline-block; width: 16px; height: 16px; border: 1px solid #ccc; margin-right: 6px; vertical-align: middle;"></span>
                        Duplicate
                    </div>
                    <div>
                        <span style="background-color: white; display: inline-block; width: 16px; height: 16px; border: 1px solid #ccc; margin-right: 6px; vertical-align: middle;"></span>
                        New Entry
                    </div>
                </div>
            `;

            workbook.SheetNames.forEach(function(sheetName, idx) {
                const worksheet = workbook.Sheets[sheetName];
                const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                html += `<h3>Sheet: ${sheetName}</h3>`;
                html += displayExcelData(json, dupes);
            });
        document.getElementById('excel_data').innerHTML = html;
        })
    };
    reader.readAsArrayBuffer(input.files[0]);
}

function displayExcelData(data, dupes = []) {
    const dupeSet = new Set(dupes.map(d => d.trim()));
    console.log("Duplicate Set:", Array.from(dupeSet));
    let table = "<table border='1' style='margin-bottom: 20px; border-collapse: collapse;'>";
    for (let row = 0; row < data.length; row++) {
        const rawCardNum = data[row][2];
        const cardNum = rawCardNum !== undefined ? String(rawCardNum).trim() : '';
        const isDupe = dupeSet.has(cardNum);
        console.log(`Row ${row} CardNum: "${cardNum}" | IsDupe: ${isDupe}`);

        table += "<tr>";
        for (let cell = 0; cell < data[row].length; cell++) {
            const cellData = data[row][cell] !== undefined ? data[row][cell] : "";
            if (row === 0) {
                // Header row styling
                table += `<th style="background-color: #8DCE8C; font-weight: bold; padding: 2px;">${cellData}</th>`;
            } else {
                const style = isDupe ? " style='background-color: #ffd1d1ff; color: black;'" : "";
                table += `<td${style}>${cellData}</td>`;
            }
        }

        table += "</tr>";
    }
    table += "</table><br>";
    return table;
}

function cvcardNums(workbook) {
    const allCardNums = [];
    workbook.SheetNames.forEach(function(sheetName) {
        const worksheet = workbook.Sheets[sheetName];
        const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        for (let row = 1; row < json.length; row++) {
            const cardNum = json[row][2];
            if (cardNum !== undefined && cardNum !== null && String(cardNum).trim() !== '') {
                allCardNums.push(String(cardNum).trim());
            }
        }
    });
    return allCardNums;
}

</script>
<?php 	
}
?>