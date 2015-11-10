jQuery(document).ready(function () {
    //alert(statemap_params.hoverColor);



    jQuery('#vmap').vectorMap({
        map: 'usa_en',
        enableZoom: true,
        backgroundColor: '#000000',
        color: '#ff0000',
        hoverColor: '#3300ff',
        selectedColor: '#0033ff',
        showTooltip: true
    });

    //alert('test');
});