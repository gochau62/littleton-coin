<?php
/*    ***************************************************  -->
<!--  * Program Name - AIS_WavePickSearch_dsp.php       *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/29/2024                         *  -->
<!--  ***************************************************   */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wave Pick Search</title>
    <style>
        /* search field for wavePickInput */
        .search-input {
            width: 100%;
            max-width: 1000px;
            padding: 14px;
            border-radius: 50px;
            border: 2px solid #ccc;
            font-size: 13px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            outline: none; 
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            border-color: #007bff; 
        }

        /* search button used to trigger the ajax call */
        button {
            padding: 10px; 
            border: none; 
            background-color: #007bff; 
            color: white; 
            font-size: 16px; 
            border-radius: 50px; 
            cursor: pointer; 
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        #inputContainer {
            text-align: center;
        }

        #count {
            text-align: center;
            size: 18;
            font-weight: bold;
        }

        h1 {
            text-align: center;
        }

    </style>
</head>

<?php
function dspWaveSearch($screenData) {
?>
<div id='stdPage'>
    <h1>Wave Pick Search<br></h1>
    <!-- create tooltip icon as well as input field for wavePickInput, create button with ajax call function -->
    <div id="inputContainer">
        <span onmouseover="tooltip.show('Enter your search item then search for query')" onmouseout="tooltip.hide();">
            <img src="images/Info_icon_20px.png" width="15" height="15">
        </span>    
        <input type="text" size="40" maxlength="30" name="wavePickInput" id="wavePickInput" class="search-input">

        <script>
            var input = document.getElementById("wavePickInput");
            // If the user presses the "Enter" key on the keyboard
	        input.addEventListener("keypress", function(event) {
  		        if (event.key === "Enter") {
    	            document.getElementById("button").click();
  		        }
	        });
        </script>

        <button id="button" type="button" onclick="myFunction()">Submit</button>
        </form>
        <div style="clear:both"></div>
    </div>

    <!-- shows number of records found by the search -->
    <br><div id="count">Number of records found: </div>
    <div><br><br><br><div id="query_data" class="mt-5">
        <!-- create table as a reference to show location of search results -->
        <table><tr><th>Selection</th><th>Sequence #</th><th>Wave Pick Text</th></tr></table>
    </div></div> 
    <div id="errorMsg" class="ui-state-error ui-corner-all ui-helper-hidden"></div>
    <div id="successMsg" class="ui-state-highlight ui-corner-all ui-helper-hidden"></div>
    <br/><hr><br/>

    <div id="dspResults">
        <p id="resultsCallback" style="display:none;"></p>
        <!-- use this to display table name upon receipt of results from AJAX request -->
        <input type="hidden" name="hideVal" id="hideVal" value="">
    </div>
</div>
<?php
} 
?>
