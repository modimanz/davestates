jQuery(document).ready(function () {
    //alert(statemap_params.hoverColor);

    url = (typeof statemap_params.statemapUrl === 'undefined') ? '' : statemap_params.statemapUrl;
    statecode = (typeof statemap_params.statecode === 'undefined') ? '' : statemap_params.statecode;

    if (url == '') {
        alert('Oooh no URL');
    }
    //alert(url);
    if (statecode.length != 2) {
        statecode = [];
        //jQuery('#vmap').vectorMap('set', 'selectedRegions',  [statecode]);
    } else {
        statecode = [statecode];
        //alert('test');
    }

    function doStateMap() {
        jQuery('#vmap').vectorMap({
            map: 'usa_en',
            enableZoom: true,
            borderColor: '#ff0000',
            borderOpacity: 1,
            borderWidth: 2,
            backgroundColor: '#000000',
            normalizeFunction: 'linear',
            color: '#ff0000',
            hoverColor: '#3300ff',
            selectedColor: '#0033ff',
            showTooltip: true,
            selectedRegions: statecode,
            onRegionClick: function (event, code, region) {
                window.location.replace(url + region.toLowerCase().replace(/ /g, '-'));
            }
        });
    }


    function sizeMap() {
        var cWidth = jQuery('#davestates-map').width();
        var cHeight = (cWidth / 1.4);

        //alert(cHeight);

        map = jQuery('#vmap').data('mapObject');

        jQuery('#vmap').css({
            'width': cWidth,
            'height': cHeight
        });

        map.width = cWidth;
        map.height = cHeight;
        map.resize();
        map.canvas.setSize(map.width, map.height);
        map.applyTransform();

    }

    doStateMap();
    sizeMap();
    sizeMap();
    jQuery(window).on('resize', sizeMap);
});