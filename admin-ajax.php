<?php
global $wpdb;

$id = (int) $_POST['id'];
$method = $_POST['method'];

// load
if ('load' == $method) {
    $row = $wpdb->get_row("SELECT content FROM `{$wpdb->prefix}shortbus` WHERE id = '$id' LIMIT 1");
    echo $this->json_response('ok', null, (string) $row->content);
}

// add 
elseif ('add' == $method) {
    $name = trim($_POST['name']);

    if (!preg_match('/^[A-Za-z0-9\-_ ]+$/', $name)) {
        echo $this->json_response('error', 'Please use only alphanumeric characters, spaces, hyphens, and underscores.');
    }
    else {
        $wpdb->insert($wpdb->prefix . 'shortbus', array('name' => $name));
        echo $this->json_response('ok', 'Shortcode added.', array('id' => (int) $wpdb->insert_id));
    }
}

// edit
elseif ('edit' == $method) {
    $content = mysql_real_escape_string(stripslashes($_POST['content']), $wpdb->dbh);
    $wpdb->query("UPDATE `{$wpdb->prefix}shortbus` SET content = '$content' WHERE id = '$id' LIMIT 1");
    echo $this->json_response('ok', 'Shortcode saved.');
}

// delete
elseif ('delete' == $method) {
    $wpdb->query("DELETE FROM `{$wpdb->prefix}shortbus` WHERE id = '$id' LIMIT 1");
    echo $this->json_response('ok', 'Shortcode deleted.');
}

// export
elseif ('export' == $method) {
    $data = $wpdb->get_results("SELECT name, content FROM `{$wpdb->prefix}shortbus` ORDER BY id");
    echo $this->json_response('ok', 'Export created.', json_encode($data));
    
}

// import
elseif ('import' == $method) {
    $do_replace = (int) $_POST['do_replace'];
    $import_content = json_decode(stripslashes($_POST['content']));

    if (empty($import_content) || false == $import_content) {
        echo $this->json_response('error', 'Nothing to import.');
        die();
    }

    // get all shortcode names
    $shortcode_names = array();
    $results = $wpdb->get_results("SELECT id, name FROM `{$wpdb->prefix}shortbus` ORDER BY name");
    foreach ($results as $result) {
        $shortcode_names[$result->name] = $result->id;
    }

    // loop through and insert new shortcodes
    foreach ($import_content as $item) {

        $name = mysql_real_escape_string($item->name, $wpdb->dbh);
        $content = mysql_real_escape_string($item->content, $wpdb->dbh);
        $update_id = isset($shortcode_names[$name]) ? $shortcode_names[$name] : 0;

        if (!$update_id) {
            $wpdb->query("INSERT INTO `{$wpdb->prefix}shortbus` (name, content) VALUES ('$name', '$content')");
        }
        elseif ($do_replace) {
            $wpdb->query("UPDATE `{$wpdb->prefix}shortbus` SET content = '$content' WHERE id = '$update_id' LIMIT 1");
        }
    }

    echo $this->json_response('ok', 'Import successful.');
}
die();
