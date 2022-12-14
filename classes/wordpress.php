<?php
class zw_WordPress_Interna {

    function get_data() {
        $interna = array(
            'version'        => $this->zw_get_wp_version(),
            'update_version' => $this->zw_get_wp_update_version(),
            'plugins'        => $this->zw_get_plugin_info(),
            'pages'          => $this->zw_get_page_info(),
            'posts'          => $this->zw_get_post_info(),
            'themes'         => $this->zw_get_theme_info(),
            'users'          => $this->zw_get_user_info(),
            'upload_size'    => $this->zw_get_upload_size(),
        );
        
        return $interna;
    }


    private function zw_get_wp_version() {
        global $wp_version;

        return $wp_version;
    }

    private function zw_get_wp_update_version() {
        $new_version = "";
        
        $c = get_site_transient('update_core');
        if (is_object($c)) {
            if (!empty($c->updates)) {
                if (!empty($c->updates[0])) {
                    $c = $c->updates[0];
                    if (!isset($c->response) || 'latest' == $c->response) {
                        /* no new version */
                    } elseif ('upgrade' == $c->response) {
                        $new_version = $c->current;
                    }
                }
            }
        }
        
        return $new_version;
    }

    private function zw_get_plugin_info() {
        // Get all plugins
        include_once( 'wp-admin/includes/plugin.php' );
        $all_plugins = get_plugins();

        // Get active plugins
        $active_plugins = get_option('active_plugins');
        
        // Get plugin updates
        wp_update_plugins();
        $update_plugins = get_site_transient( 'update_plugins' );

        // Assemble array of name, version, and whether plugin is active (boolean)
        $plugins = [];
        foreach ( $all_plugins as $key => $value ) {
            $is_active = ( in_array( $key, $active_plugins ) ) ? true : false;
            $plugins[] = array(
                'name'          => $value['Name'],
                'version'       => $value['Version'],
                'updateVersion' => $update_plugins->response[$key]->new_version ?? "",
                'active'        => $is_active,
                'network'       => $value['Network'],
            );
        }

        return $plugins;
    }

    private function zw_get_page_info() {
        $args = array(
            'post_type'        => 'page',
            'post_status'      => array ('publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit', 'custom_status'),
        );

        $page_list = get_pages();
        $pages = [];
        
        foreach ($page_list as $key => $value) {
            $user = get_user_by('id', $value->post_author);
            $pages[] = array(
                'id'          => $value->ID,
                'author'      => $user->data->display_name,
                'date'        => $value->post_date_gmt,
                'title'       => $value->post_title,
                'status'      => $value->post_status,
                'modified'    => $value->post_modified_gmt,
                'parent'      => $value->post_parent,
                'type'        => $value->post_type,
                'comments'    => $value->comment_count,
            );
        
        }

        return $pages;
    }
    
    private function zw_get_post_info() {
        $args = array(
            'numberposts'      => -1,
            'include'          => array(),
            'exclude'          => array(),
            'post_type'        => 'post',
            'post_status'      => array ('publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit', 'custom_status'),
            'suppress_filters' => true,
        );

        $post_list = get_posts($args);
        $posts = [];
        
        foreach ($post_list as $key => $value) {
            $user = get_user_by('id', $value->post_author);
            $pages[] = array(
                'id'          => $value->ID,
                'author'      => $user->data->display_name,
                'date'        => $value->post_date_gmt,
                'title'       => $value->post_title,
                'status'      => $value->post_status,
                'modified'    => $value->post_modified_gmt,
                'parent'      => $value->post_parent,
                'type'        => $value->post_type,
                'comments'    => $value->comment_count,
            );
        
        }

        return $pages;
    }
    
    private function zw_get_theme_info() {
        $all_themes = wp_get_themes();

        $themes = [];
        foreach ( $all_themes as $theme ) {
            $is_out_of_date = !empty($theme->update);
            $themes[] = array(
                'name'           => $theme->get('Name'),
                'version'        => $theme->get('Version'),
                'template'       => $theme->get('Template'),
                'is_out_of_date' => $is_out_of_date,
            );
        }

        return $themes;
    }
    
    
    private function zw_get_user_info() {
        $all_users = get_users();

        $users = [];
        foreach ($all_users as $user) {
            $is_admin = user_can($user->ID, 'administrator');
            $users[] = array(
                'name'           => $user->display_name,
                'email'          => $user->user_email,
                'administrator'  => $is_admin,
            );
        }
        
        return $users;
    }
    
    private function zw_get_upload_size() {
        $upload = wp_get_upload_dir();

        return $this->zw_folder_size($upload["path"]);
    }
    
    private function zw_folder_size($dir) {
        $size = 0;
        $count = 0;
        $dir_array = array_slice(scandir($dir), 2); // omit . and ..
        foreach ($dir_array as $filename) {
            if (is_dir($dir . "/" . $filename)) {
                $new_foldersize = $this->zw_folder_size($dir . "/" . $filename);
                $size += $new_foldersize;
            } else if (is_file($dir . "/" . $filename)){
                $size += filesize($dir . "/" . $filename);
                ++$count;
            }
        }

        return $size;
    }
}