<?php
// Script to get list of plugins from WordPress installations in a folder
// Useful for environments where you have multiple WordPress installations in one folder

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ( $_GET['path'] ) {

    $excluded_folders = [
        '.git',
        'vendor',
        'cache',
    ];

    $included_folders = [
        'wp-content',
        'plugins',
    ];

    $plugins = [];

    $plugin_data = [];

    $folder_to_find = 'plugins';
    
    // get just the directory listing of folders
    $dirs = array_filter(glob( $_GET['path'] . '*'), 'is_dir');
    // var_dump( $dirs );
    $directories = [];
    foreach( $dirs as $dir ) {
        $dir_name = explode( '/', $dir );
        $dir_name = array_pop( $dir_name );
        $directories[$dir_name] = $dir;
    }
    // var_dump( $directories );

    if ( @$_GET['folders'] ) {
        foreach( $_GET['folders'] as $folder ) {
            echo $folder . '<br>';
        }
        
        $plugin_folders = [];

        // $found_plugin_folder = false;
        foreach( $_GET['folders'] as $folder ) {
            $path = $folder;
            // look in this folder for a wp-content/plugins folder
            $wp_content_plugins = array_filter(glob( $path . '/wp-content/plugins'), 'is_dir');
            // var_dump($wp_content_plugins);
            if ( count( $wp_content_plugins ) == 1 ) {
                $plugin_folders[] = $wp_content_plugins[0];
            } else {
                // look at the main list of folders and loop over them - basically 1 level deep
                $dirs = array_filter(glob( $path . '/*'), 'is_dir');
                foreach( $dirs as $dir ) {
                    $wp_content_plugins = array_filter(glob( $dir . '/wp-content/plugins'), 'is_dir');
                    // var_dump($wp_content_plugins);
                    if ( count( $wp_content_plugins ) == 1 ) {
                        $plugin_folders[] = $wp_content_plugins[0];
                    }
                }
            }
            // var_dump($files);
        }
        // var_dump( $plugin_folders );
        // now go in there and get the list of plugins
        foreach( $plugin_folders as $plugin_folder ) {
            $plugins = array_merge( $plugins, array_filter(glob( $plugin_folder . '/*'), 'is_dir') );
        }
        // var_dump( $plugins );

        // go into each plugin folder and get the plugin name
        foreach( $plugins as $plugin ) {
            $php_files = glob( $plugin . '/*.php' );
            foreach( $php_files as $php_file ) {
                $handle = fopen( $php_file, 'r' );
                $valid = false; // init as false
                while (($buffer = fgets($handle)) !== false) {
                    if (strpos(strtolower( $buffer ), "plugin name:") !== false) {
                        $valid = TRUE;
                        // get the plugin name, path, etc
                        $temp_data = new StdClass();
                        $temp_data->path = $plugin;
                        $temp_data->file = $php_file;
                        $temp_data->name = trim( substr( $buffer, strpos( $buffer, ':' ) + 1 ) );
                        $plugin_data[] = $temp_data;
                        fclose($handle);
                        break; // Once you find the string, you should break out the loop.
                    }      
                }
                // fclose($handle);
            }
        }
        // var_dump( $plugin_data );
        // sort by name
        usort( $plugin_data, function( $a, $b ) { return strcmp( $a->name, $b->name ); } );
        // var_dump( $plugin_data );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Plugin Lister</title>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css" integrity="sha512-1k7mWiTNoyx2XtmI96o+hdjP8nn0f3Z2N4oF/9ZZRgijyV4omsKOXEnqL1gKQNPy2MTSP9rIEWGcH/CInulptA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css" integrity="sha512-PT0RvABaDhDQugEbpNMwgYBCnGCiTZMh9yOzUsJHDgl/dMhD9yjHAwoumnUk3JydV3QTcIkNDuN40CJxik5+WQ==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" referrerpolicy="no-referrer"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js" integrity="sha512-BkpSL20WETFylMrcirBahHfSnY++H2O1W+UnEEO4yNIl+jI2+zowyoGJpbtk6bx97fBXf++WJHSSK2MV4ghPcg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <form action="" method="get">
        <div>Path: <input type="text" name="path" value="<?php echo $_GET['path']; ?>"></div>
        <div><button type="submit">Scan path for folders</button></div>
        <hr>
        <?php if ( @$directories ) {
            $i = 0;
            foreach ( $directories as $folder => $path ) {
                ?>
                <div>
                    <label for="folder-<?php echo $i; ?>">
                        <input type="checkbox" id="folder-<?php echo $i; ?>" name="folders[]" value="<?php echo $path; ?>">
                        <?php echo $folder; ?>
                    </label>
                </div>
                <?php
                $i++;
            } ?>
            <button type="submit">Scan folders for plugin folder</button>
        <?php } ?>
    </form>
    <?php if ( count( $plugin_data ) ) { ?>
        <table id="myTable" class="">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Path</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $plugin_data as $plugin ) { ?>
                    <tr>
                        <td><?php echo $plugin->name; ?></td>
                        <td><?php echo $plugin->path; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
    <script>
        $(document).ready( function () {
            $('#myTable').DataTable();
        } );
    </script>
</body>
</html>