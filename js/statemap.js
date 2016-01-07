jQuery(document).ready(function ($) {
    //alert(statemap_params.hoverColor);

    url = (typeof statemap_params.statemapUrl === 'undefined') ? '' : statemap_params.statemapUrl;
    statecode = (typeof statemap_params.statecode === 'undefined') ? '' : statemap_params.statecode;

    //if (url == '') {
    //    alert('Oooh no URL');
    //}
    //alert(url);
    if (statecode.length != 2) {
        statecode = [];
        //jQuery('#vmap').vectorMap('set', 'selectedRegions',  [statecode]);
    } else {
        statecode = [statecode];
        //alert('test');
    }

    var hoverColors = statemap_params.hoverColors;


    var output = '';
    for (var property in hoverColors) {
        output += property + ': ' + hoverColors[property]+'; ';
    }
    alert(output);

    function doStateMap() {
        $('#vmap').vectorMap({
            map: 'usa_en',
            enableZoom: true,
            borderColor: '#ff0000',
            borderOpacity: 1,
            borderWidth: 2,
            backgroundColor: '#000000',
            normalizeFunction: 'linear',
            color: 'transparent',
            hoverColor: '#3300ff',
            hoverColors: hoverColors,/*{
                al: "#b80000",
                ak: "#b80000",
                az: "#b80000",
                ar: "#b80000",
                ca: "#b80000",
                co: "#b80000",
                ct: "#b80000",
                de: "#b80000",
                fl: "#b80000",
                ga: "#b80000",
                hi: "#b80000",
                id: "#b80000",
                il: "#b80000",
                in: "#b80000",
                ia: "#b80000",
                ks: "#b80000",
                ky: "#b80000",
                la: "#b80000",
                me: "#b80000",
                md: "#b80000",
                ma: "#b80000",
                mi: "#b80000",
                mn: "#b80000",
                ms: "#b80000",
                mo: "#191175",
                mt: "#191175",
                ne: "#191175",
                nv: "#191175",
                nh: "#191175",
                nj: "#191175",
                nm: "#191175",
                ny: "#191175",
                nc: "#191175",
                nd: "#191175",
                oh: "#191175",
                ok: "#191175",
                or: "#191175",
                pa: "#191175",
                ri: "#191175",
                sc: "#191175",
                sd: "#191175",
                tn: "#191175",
                tx: "#191175",
                ut: "#191175",
                vt: "#191175",
                va: "#191175",
                wa: "#191175",
                wv: "#191175",
                wi: "#191175",
                wy: "#191175"
            },*/
            selectedColor: '#0033ff',
            showTooltip: true,
            selectedRegions: statecode,
            onRegionClick: function (event, code, region) {
                window.location.replace(url + region.toLowerCase().replace(/ /g, '-'));
            }
        });
    }


    function sizeMap() {
        var cWidth = $('#davestates-map').width();
        var cHeight = (cWidth / 1.4);

        //alert(cHeight);

        map = $('#vmap').data('mapObject');

        $('#vmap').css({
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
    $(window).on('resize', sizeMap);
});