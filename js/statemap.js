jQuery(document).ready(function () {
    //alert(statemap_params.hoverColor);

    url = statemap_params.statemapUrl;


    jQuery('#vmap').vectorMap({
        map: 'usa_en',
        enableZoom: true,
        backgroundColor: '#000000',
        color: '#ff0000',
        hoverColor: '#3300ff',
        selectedColor: '#0033ff',
        showTooltip: true,
        onRegionClick: function (event, code, region) {
            window.location.replace(url + region.toLowerCase());
        }
    });

    //alert('test');
});