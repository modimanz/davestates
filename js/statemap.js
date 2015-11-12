jQuery(document).ready(function () {
    //alert(statemap_params.hoverColor);

    url = statemap_params.statemapUrl;
    statecode = statemap_params.statecode;

    //alert(statecode);
    if (statecode.length != 2) {
        statecode = [];
        //jQuery('#vmap').vectorMap('set', 'selectedRegions',  [statecode]);
    } else {
        statecode = [statecode];
        //alert('test');
    }


    jQuery('#vmap').vectorMap({
        map: 'usa_en',
        enableZoom: true,
        backgroundColor: '#000000',
        color: '#ff0000',
        hoverColor: '#3300ff',
        selectedColor: '#0033ff',
        showTooltip: true,
        selectedRegions: statecode,
        onRegionClick: function (event, code, region) {
            window.location.replace(url + region.toLowerCase());
        }
    });

});