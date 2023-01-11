// JS Code to select textarea
function copy() {
    
    let textarea = document.getElementById("tips_export");
    textarea.select();
    document.execCommand("copy");
}