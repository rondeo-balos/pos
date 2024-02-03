<style>
    #qr-reader__dashboard_section {
        display: none;
    }
</style>

<script src="https://unpkg.com/html5-qrcode"></script>
<div id="qr-reader" style="width:100%; max-width: 360px; margin-left: auto; margin-right: auto;"></div>
<audio id="beep" src="https://cdn.jsdelivr.net/gh/cdn-assets/files/beep.mp3"></audio>

<script>
var resultContainer = document.getElementById('qr-reader-results');
var lastResult, countResults = 0;

function playBeep() {
    var beep = document.getElementById('beep');
    beep.play();
}

function onScanSuccess(decodedText, decodedResult) {
    playBeep();
    html5QrcodeScanner.clear();
    //if (decodedText !== lastResult) {
    //    ++countResults;
    //    lastResult = decodedText;
        $.ajax('https://10.0.0.34/fetch/' + decodedText, {
            method: 'POST',
            data: [],
            success: function(e) {
                if(e === '0') {
                    alert('Out of stock');
                }
                html5QrcodeScanner.render(onScanSuccess);
            }
        });
    //}
}

var html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
html5QrcodeScanner.render(onScanSuccess);
</script>