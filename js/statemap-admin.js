/**
 * Created by morgan on 1/6/16.
 */
jQuery(document).ready(function() {

    jQuery('#upload_statemap_image_button').click(function() {
        formfield = jQuery('#upload_statemap_image').attr('name');
        tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
        return false;
    });

    window.send_to_editor = function(html) {
        imgurl = jQuery('img',html).attr('src');
        jQuery('#upload_statemap_image').val(imgurl);
        tb_remove();
    }

    jQuery('.colorSelector').wpColorPicker();

});