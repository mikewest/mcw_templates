<?php
/*
    ZEM TEMPLATE CONFIG
    -------------------------------------------------------------------------
*/
    $plugin['version']      = '0.1';
    $plugin['author']       = 'Mike West';
    $plugin['author_uri']   = 'http://mikewest.org/';
    $plugin['description']  = 'File Based Templates';
    $plugin['type']         = 1; // 0 for regular plugin; 1 if it includes admin-side code
    @include_once('zem_tpl.php');

/*
    PLUGIN CONFIG
    -------------------------------------------------------------------------
*/
    $mcw_templates['dir']               = '_templates';

    $mcw_templates['import_new']        = TRUE;

    $mcw_templates['prefix_page']       = 'page_';
    $mcw_templates['prefix_form']       = 'form_';
    $mcw_templates['prefix_css']        = 'css_';

    $mcw_templates['extension_page']    = '.html';
    $mcw_templates['extension_form']    = '.html';
    $mcw_templates['extension_css']     = '.css';

/*
    PLUGIN CODE (no editing below this line, please)
    -------------------------------------------------------------------------
*/
    $mcw_templates['full_path']                 = $GLOBALS['txpcfg']['txpath'].'/'.$mcw_templates['dir'].'/';

    $mcw_templates['error']['template']         = "
        <h1 class='failure'>%s</h1>
        <p>%s</p>
    ";
    $missing_dir_head = "Template Directory Missing";
    $missing_dir_text = "The template directory `<strong>".$mcw_templates['full_path']."</strong>` does not exist, and could not be automatically created.  Would you mind creating it yourself by running something like</p><pre><code>    mkdir ".$mcw_templates['full_path']."\n    chmod 777 ".$mcw_templates['full_path']."</code></pre><p>That should fix the issue.  You could also adjust the plugin's directory by modifying <code>\$mcw_templates['dir']</code> (or, if you're *crazy*, <code>\$mcw_templates['full_path']</code>) in the plugin's code.";
    $mcw_templates['error']['missing_dir'] = sprintf($mcw_templates['error']['template'], $missing_dir_head, $missing_dir_text);

    $cant_write_head    = "Template Directory Not Writable";
    $cant_write_text    = "I can't seem to write to the template directory `<strong>".$mcw_templates['full_path']."</strong>`.  Would you mind running something like</p><pre><code>    chmod 777 ".$mcw_templates['full_path']."</code></pre><p>to fix the problem?";
    $mcw_templates['error']['cant_write'] = sprintf($mcw_templates['error']['template'], $cant_write_head, $cant_write_text);

    $GLOBALS['_MCW_TEMPLATES_CONFIG'] = $mcw_templates;

    /*
        PLUGIN CODE::HELP
        -----------------------------------------------------------------
    */
        if (0) {
        ?>
# --- BEGIN PLUGIN HELP ---

<h1>Import/Export Templates as Files</h1>

<p>This plugin creates a new tab under `extensions`, enabling the trivial export of pages, forms, and CSS rules to a specified folder for convenient editing, and the subsequent import of new and updated files.</p>

<h2>Requirements</h2>

<p>This plugin has only been tested on <strong>4.03</strong>.  It might work on other version, but no promises!</p>

<p>Regardless of where it's been tested, this plugin messes around with your database.  <strong>Do not use it without backing up your database</strong>.</p>

<h2>Setup</h2>
<p>
    By default, the plugin looks for a directory named <code>_templates</code>
    in your <code>textpattern</code> directory.  If the directory doesn't exist,
    the plugin will attempt to create it the first time you export your
    templates. This creation will almost certainly fail, since the
    <code>textpattern</code> directory usually isn't writable.  In that case,
    you'll need to create this  directory, and ensure that the web server has
    write access.  If your site is hosted at
    <code>/users/home/myuser/web/public/</code>, then the following commands
    could be used:
</p>
<pre>
<code>
    cd /users/home/myuser/web/public/
    mkdir ./textpattern/_templates
    chmod 777 ./textpattern/_templates
</code>
</pre>

<h2>Usage</h2>
<p>To use the plugin, simply select 'import' or 'export' from the dropdown on the plugin's tab.  Couldn't be simpler.</p>

# --- END PLUGIN HELP ---
        <?php
        }

    /*
        PLUGIN CODE::INSTANTIATION
        -----------------------------------------------------------------
    */
        if (@txpinterface == 'admin') {
            $import         = 'mcw_templates';
            $import_tab     = 'Template Files';

            // Set the privilege levels for our new event
            add_privs($import, '1,2');

            // Add a new tab under 'extensions' associated with our event
            register_tab("extensions", $import, $import_tab);

            // 'zem_admin_test' will be called to handle the new event
            register_callback("mcw_templates", $import);
        }

        function mcw_templates_bereit() {
            global $_MCW_TEMPLATES_CONFIG;

            if (!is_dir($_MCW_TEMPLATES_CONFIG['full_path'])) {
                if (!@mkdir($_MCW_TEMPLATES_CONFIG['full_path'], 0777)) {
                    echo $_MCW_TEMPLATES_CONFIG['error']['missing_dir'];
                    return false;
                }
            }
            if (!is_writable($_MCW_TEMPLATES_CONFIG['full_path'])) {
                echo $_MCW_TEMPLATES_CONFIG['error']['cant_write'];
                return false;
            }
            return true;
        }

        function mcw_templates($event, $step) {
            pagetop("Process Templates", "");
            print "
            <style type='text/css'>
                .success { color: #009900; }
                .failure { color: #FF0000; }
            </style>

            <table cellpadding='0' cellspacing='0' border='0' id='list' align='center'>
            <tr>
            <td>
            ";
            if ($step == 'import') {
                mcw_templates_import();
            } elseif ($step == 'export') {
                mcw_templates_export();
            } else {
                print "<h1>Import/Export Templates</h1>".form(
                    graf('Are we importing or exporting?'.
                        selectInput('step', array('import'=>'Import','export'=>'Export'), '', 1).
                        fInput('submit', 'go', 'Go', 'smallerbox').
                        eInput('mcw_templates')
                    )
                );
            }
            print "
            </td>
            </tr>
            </table>
            ";
        }

    /*
        PLUGIN CODE::IMPORT FUNCTION
        -----------------------------------------------------------------
    */
        function mcw_templates_import() {
            global $_MCW_TEMPLATES_CONFIG;

            /*
                If the directory doesn't exist, throw an
                error with instructions.
            */
            if (!mcw_templates_bereit()) {
                return;
            }

            print "
                <h1>Importing Templates</h1>
                <ul>
            ";

            $dir = opendir($_MCW_TEMPLATES_CONFIG['full_path']);

            while ($filename = readdir($dir))  {
                /*
                    Import pages:

                    If the filename begins with the page prefix, and ends with
                    the page extension, then process it.
                */
                $page_regex = $_MCW_TEMPLATES_CONFIG['prefix_page'].'(.+)'.$_MCW_TEMPLATES_CONFIG['extension_page'];
                $form_regex = $_MCW_TEMPLATES_CONFIG['prefix_form'].'(.+)'.$_MCW_TEMPLATES_CONFIG['extension_form'];
                $css_regex  = $_MCW_TEMPLATES_CONFIG['prefix_css'] .'(.+)'.$_MCW_TEMPLATES_CONFIG['extension_css'];

                // css  = name, css
                // page = name, user_html
                // form = name, type, Form


                if (preg_match('/'.$page_regex.'/', $filename, $pageName)) {
                    $pageName = addslashes($pageName[1]);
                    $f = $_MCW_TEMPLATES_CONFIG['full_path'].$filename;
                    if ($pageData = file($f)) {
                        $pageData = addslashes(implode('', $pageData));

                        if (safe_field('name', 'txp_page', "name='$pageName'")) {
                            $result = safe_update('txp_page', "`user_html` = '".$pageData."'", "`name` = '".$pageName."'");
                            $success = ($result)?1:0;
                        } else {
                            $result = safe_insert('txp_page', "`user_html` = '".$pageData."', `name` = '".$pageName."'");
                            $success = ($result)?1:0;
                        }

                        if ($success) {
                            print "<li><span class='success'>Successfully imported</span> page '".stripslashes($pageName)."'</li>";
                        } else {
                            print "<li><span class='failure'>Failed importing</span> page '".stripslashes($pageName)."'</li>";
                        }
                    }
                } elseif (preg_match('/'.$form_regex.'/', $filename, $formName)) {
                    $formName = addslashes($formName[1]);
                    $f = $_MCW_TEMPLATES_CONFIG['full_path'].$filename;
                    if ($formData = file($f)) {
                        $formData = addslashes(implode('', $formData));

                        if (safe_field('name', 'txp_form', "name='$formName'")) {
                            $result = safe_update('txp_form', "`Form` = '".$formData."'", "`name` = '".$formName."'");
                            $success = ($result)?1:0;
                        } else {
                            $result = safe_insert('txp_form', "`Form` = '".$formData."', `type` = 'misc', `name` = '".$formName."'");
                            $success = ($result)?1:0;
                        }

                        if ($success) {
                            print "<li><span class='success'>Successfully imported</span> form '".stripslashes($formName)."'</li>";
                        } else {
                            print "<li><span class='failure'>Failed importing</span> form '".stripslashes($formName)."'</li>";
                        }
                    }
                } elseif (preg_match('/'.$css_regex.'/', $filename, $cssName)) {
                    $cssName = addslashes($cssName[1]);
                    $f = $_MCW_TEMPLATES_CONFIG['full_path'].$filename;
                    if ($cssData = file($f)) {
                        $cssData = base64_encode(implode('', $cssData));

                        if (safe_field('css', 'txp_css', "name='$cssName'")) {
                            $result = safe_update('txp_css', "`css` = '".$cssData."'", "`name` = '".$cssName."'");
                            $success = ($result)?1:0;
                        } else {
                            $result = safe_insert('txp_css', "`css` = '".$cssData."', `name` = '".$cssName."'");
                            $success = ($result)?1:0;
                        }

                        if ($success) {
                            print "<li><span class='success'>Successfully imported</span> css '".stripslashes($cssName)."'</li>";
                        } else {
                            print "<li><span class='failure'>Failed importing</span> css '".stripslashes($cssName)."'</li>";
                        }
                    }
                }
            }

            print "
                </ul>
            ";


        }



    /*
        PLUGIN CODE::EXPORT FUNCTION
        -----------------------------------------------------------------
    */
        function mcw_templates_export() {
            global $_MCW_TEMPLATES_CONFIG;

            /*
                If the directory doesn't exist, throw an
                error with instructions.
            */
            if (!mcw_templates_bereit()) {
                return;
            }

            /*
                Begin Export
            */
            $export = array(
                "page"  => "user_html",
                "form"  => "Form",
                "css"   => "css"
            );

            foreach ($export as $exportType => $fieldName) {
                print "
                    <h1>Exporting: ".$exportType." files</h1>
                    <ul class='results'>
                ";

                $things = safe_rows('name,'.$fieldName, "txp_".$exportType, '1=1');

                foreach ($things as $thing) {
                    $fileName   =   $_MCW_TEMPLATES_CONFIG['prefix_'.$exportType] .
                                    $thing['name'] .
                                    $_MCW_TEMPLATES_CONFIG['extension_'.$exportType];

                    // Decode CSS:
                    if ($fieldName == 'css') {
                        $thing['css'] = base64_decode($thing['css']);
                    }

                    $f = @fopen($_MCW_TEMPLATES_CONFIG['full_path'].$fileName, "w+");
                    if ($f) {
                        fwrite($f,$thing[$fieldName]);
                        fclose($f);
                        print "
                        <li><span class='success'>Successfully exported</span> ".$exportType." '".$thing['name']."' to '".$fileName."'</li>
                        ";
                    } else {
                        print "
                        <li><span class='failure'>Failure exporting</span> ".$exportType." '".$thing['name']."' to '".$fileName."'</li>
                        ";
                    }
                }
                print "
                    </ul>
                ";
            }
        }
?>