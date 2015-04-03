$(document).ready(function(){
    $( "#disc" ).autocomplete(
    {
        source:'search_disc.php',
        minChars: 2,
        autoFill: true,
        selectFirst: true,
        width: '140px',
        maxItemsToShow: 3
    })
})
