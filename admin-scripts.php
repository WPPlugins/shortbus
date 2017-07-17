<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/shortbus/js/codemirror/codemirror-compressed.js"></script>
<script type="text/javascript">

// @todo: encapsulate
//var Shortbus = function() {}();

var editor;
var shortcode_id;

jQuery(function() {

    // initialize codemirror
    editor = CodeMirror.fromTextArea(document.getElementById("shortcode-content"), {
        mode: "php",
        theme: "neat",
        indentUnit: 4,
        tabMode: "shift",
        lineNumbers: true
    });

    // load
    jQuery(".sb-option").live("click", function() {
        shortcode_id = jQuery(this).attr("rel");
        jQuery("#shortcode-response").hide();
        jQuery("#sb-select-value").html(jQuery(this).html());
        jQuery("#sb-select-box").removeClass("active");
        jQuery("#sb-select-popup").toggle();
        editor.setValue("");
        var data = {
            method: "load",
            action: "shortbus",
            id: jQuery(this).attr("rel")
        };
        if ("" == data.id) {
            jQuery("#shortcode-area").hide();
            jQuery("#shortcode-intro").show();
        }
        else {
            jQuery("#save-area").hide();
            jQuery("#loading-area").show();
            jQuery.post(ajaxurl, data, function(response) {
                editor.setValue(response.data);
                jQuery("#shortcode-intro").hide();
                jQuery("#shortcode-area").show();
                jQuery("#loading-area").hide();
                jQuery("#save-area").show();
            }, "json");
        }
    });

    // select box
    jQuery("#sb-select-box").click(function() {
        jQuery(this).toggleClass("active");
        jQuery("#sb-select-popup").toggle();
    });

    // filter select box options
    jQuery("#sb-filter-input").keyup(function() {
        var keyword = jQuery(this).val();
        if ("" == keyword) {
            jQuery(".sb-option").show();
        }
        else {
            jQuery(".sb-option").each(function() {
                var html = jQuery(this).html();
                var regex = new RegExp(keyword, "i");
                if (0 <= html.search(regex)) {
                    jQuery(this).show();
                }
                else {
                    jQuery(this).hide();
                }
            });
        }
    });

    // add
    jQuery("#add-shortcode").click(function() {
        var data = {
            method: "add",
            action: "shortbus",
            name: jQuery("#shortcode-name").val()
        };

        if ("" == data.name) return;
        jQuery.post(ajaxurl, data, function(response) {
            if ("ok" == response.status) {
                editor.setValue("");
                jQuery("#shortcode-name").val("");
                jQuery("#sb-select-options").append('<div class="sb-option" rel="'+response.data.id+'">'+data.name+'</div>');
                jQuery("#sb-select-value").html(data.name);
                jQuery("#shortcode-intro").hide();
                jQuery("#shortcode-area").show();
                shortcode_id = response.data.id;
            }
            jQuery("#shortcode-response").html("<p>"+response.status_message+"</p>");
            jQuery("#shortcode-response").show();
        }, "json");
    });

    // edit
    jQuery("#edit-shortcode").click(function() {
        var data = {
            method: "edit",
            action: "shortbus",
            id: shortcode_id,
            content: editor.getValue()
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery("#shortcode-response").html("<p>"+response.status_message+"</p>");
            jQuery("#shortcode-response").show();
        }, "json");
    });

    // delete
    jQuery("#delete-shortcode").click(function() {
        if (confirm("Are you sure you want to delete this shortcode?")) {
            var data = {
                method: "delete",
                action: "shortbus",
                id: shortcode_id
            };
            jQuery.post(ajaxurl, data, function(response) {
                jQuery("#shortcode-area").hide();
                jQuery("#shortcode-intro").show();
                jQuery("#sb-select-value").html("Select one");
                jQuery(".sb-option[rel="+shortcode_id+"]").remove();
                jQuery("#shortcode-response").html("<p>"+response.status_message+"</p>");
                jQuery("#shortcode-response").show();
                shortcode_id = null;
            }, "json");
        }
    });

    // export
    jQuery("#export").click(function() {
        jQuery("#import-area").hide();
        jQuery("#export-area").toggle();
    });

    jQuery("#do-export").click(function() {
        jQuery("#export-content").val("");
        var data = { method: "export", action: "shortbus" };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery("#export-content").val(response.data);
        }, "json");
    });

    // import
    jQuery("#import").click(function() {
        jQuery("#export-area").hide();
        jQuery("#import-area").toggle();
    });

    jQuery("#do-import").click(function() {
        var data = {
            method: "import",
            action: "shortbus",
            content: jQuery("#import-content").val(),
            do_replace: jQuery("#import-replace").is(":checked") ? 1 : 0
        };
        jQuery.post(ajaxurl, data, function(response) {
            if ("error" == response.status) {
                jQuery("#shortcode-response").html("<p>"+response.status_message+"</p>");
                jQuery("#shortcode-response").show();
            }
            else {
                window.location = "";
            }
        }, "json");
    });
});
</script>
