<?php
/*
  Copyright (c) 2005-2014 by CyberSEO (http://www.cyberseo.net). All Rights Reserved.
  The CyberSEO WordPress plugin is commercial software; you can't redistribute,
  try to decode and/or modify it without written permission of the author.
 */

if (!function_exists("add_action")) {
    @require_once("../../../wp-config.php");
    status_header(404);
    nocache_headers();
    @include(get_404_template());
    exit();
}
?>

<?php
// if form submitted
if (isset($_POST['Submit'])) {
    $csyn_wa_options['email'] = $_POST['email'];
    $csyn_wa_options['pass'] = $_POST['pass'];
    $csyn_wa_options['plan'] = $_POST['plan'];
    $csyn_wa_options['standard_quality'] = $_POST['standard_quality'];
    $csyn_wa_options['turing_quality'] = $_POST['turing_quality'];
    $csyn_wa_options['nonested'] = @$_POST['nonested'];
    $csyn_wa_options['sentence'] = @$_POST['sentence'];
    $csyn_wa_options['paragraph'] = @$_POST['paragraph'];
    $csyn_wa_options['nooriginal'] = @$_POST['nooriginal'];
    $csyn_wa_options['protected'] = $_POST['protected'];
    $csyn_wa_options['synonyms'] = $_POST['synonyms'];

    $update_cseo_query = update_option(CSYN_WORDAI_OPTIONS, $csyn_wa_options);
    if ($update_cseo_query) {
        $text = 'WordAi Setting Updated<br />';
    } else {
        $text = __('No Option Updated');
    }
}
?>
<?php
if (!empty($text)) {
    echo '<div id="message" class="updated fade"><p>' . $text . '</p></div>';
}
?>
<div class="wrap">
 
    <a href="http://www.cyberseo.net/partners/wordai.php" target="_blank"><img class="alignleft" style="margin:6px;" src="<?php echo plugins_url('/images/wordai32.png', __FILE__);  ?>" /></a>
    <h2>WordAi Settings</h2>

    <div class="metabox-holder postbox-container">
        <form method="post" name="general_settings">
            <table class="form-table">

                <tr>
                    <th align="left">Email</th>
                    <td align="left">
                        <input type="text" name="email" size="40" value="<?php echo $csyn_wa_options['email']; ?>"> - your <a href="http://www.cyberseo.net/partners/wordai.php" target="_blank"><strong>WordAi</strong></a> login email.
                    </td>
                </tr>

                <tr>
                    <th align="left">Pass</th>
                    <td align="left">
                        <input type="text" name="pass" size="40" value="<?php echo $csyn_wa_options['pass']; ?>"> - your <a href="http://www.cyberseo.net/partners/wordai.php" target="_blank"><strong>WordAi</strong></a> password.
                    </td>
                </tr>	

                <tr>
                    <th align="left">Plan</th>
                    <td align="left">
                        <select name="plan" size="1">
                            <?php
                            echo '<option ' . (($csyn_wa_options['plan'] == "standard") ? 'selected ' : '') . 'value="standard">standard</option>' . "\n";
                            echo '<option ' . (($csyn_wa_options['plan'] == "turing") ? 'selected ' : '') . 'value="turing">turing</option>' . "\n";
                            ?>
                        </select>
                    </td>
                </tr>  	

                <tr>
                    <th align="left">Standard Quality</th>
                    <td align="left">
                        <input type="text" name="standard_quality" size="5" value="<?php echo $csyn_wa_options['standard_quality']; ?>"> - a numeric value between 0 and 100. The lower the number, the more unique, and the higher the number, the more readable. This option is used with Standard Plan only.
                    </td>
                </tr>	                            

                <tr>
                    <th align="left">Turing Quality</th>
                    <td align="left">
                        <select name="turing_quality" size="1">
                            <?php
                            echo '<option ' . (($csyn_wa_options['turing_quality'] == "Regular") ? 'selected ' : '') . 'value="Regular">Regular</option>' . "\n";
                            echo '<option ' . (($csyn_wa_options['turing_quality'] == "Unique") ? 'selected ' : '') . 'value="Unique">Unique</option>' . "\n";
                            echo '<option ' . (($csyn_wa_options['turing_quality'] == "Very Unique") ? 'selected ' : '') . 'value="Very Unique">Very Unique</option>' . "\n";
                            echo '<option ' . (($csyn_wa_options['turing_quality'] == "Readable") ? 'selected ' : '') . 'value="Readable">Readable</option>' . "\n";
                            echo '<option ' . (($csyn_wa_options['turing_quality'] == "Very Readable") ? 'selected ' : '') . 'value="Very Readable">Very Readable</option>' . "\n";
                            ?>
                        </select> - select the desired output quality depending on how readable vs unique you want your spin to be. This option is used with Turing Plan only.
                    </td>
                </tr>  	                          

                <tr>
                    <th align="left">No Nested</th>
                    <td align="left"><input type="checkbox" name="nonested"
                        <?php
                        if ($csyn_wa_options['nonested'] == 'on') {
                            echo "checked";
                        }
                        ?> /> - enable it to turn off nested spinning (will help readability but hurt uniqueness).
                    </td>
                </tr>         

                <tr>
                    <th align="left">Sentence</th>
                    <td align="left"><input type="checkbox" name="sentence"
                        <?php
                        if ($csyn_wa_options['sentence'] == 'on') {
                            echo "checked";
                        }
                        ?> /> - enable it if you want paragraph editing, where WordAi will add, remove, or switch around the order of sentences in a paragraph (recommended!)
                    </td>
                </tr>      

                <tr>
                    <th align="left">Paragraph</th>
                    <td align="left"><input type="checkbox" name="paragraph"
                        <?php
                        if ($csyn_wa_options['paragraph'] == 'on') {
                            echo "checked";
                        }
                        ?> /> - enable it if you want WordAi to do paragraph spinning - perfect for if you plan on using the same Spintax many times.
                    </td>
                </tr>       

                <tr>
                    <th align="left">No Original</th>
                    <td align="left"><input type="checkbox" name="nooriginal"
                        <?php
                        if ($csyn_wa_options['nooriginal'] == 'on') {
                            echo "checked";
                        }
                        ?> /> - enable it if you do not want to include the original word in Spintax (if synonyms are found). This is the same thing as creating a "Super Unique" spin.
                    </td>
                </tr>           

                <tr>
                    <th align="left">Protected</th>
                    <td align="left">
                        <input type="text" name="protected" size="40" value="<?php echo $csyn_wa_options['protected']; ?>"> - comma-separated protected words (do not put spaces inbetween the words).
                    </td>
                </tr>	

                <tr>
                    <th align="left">Synonyms</th>
                    <td align="left">
                        <input type="text" name="synonyms" size="40" value="<?php echo $csyn_wa_options['synonyms']; ?>"> - add your own synonyms (Syntax: word1|synonym1,word two|first synonym 2|2nd syn). (comma separate the synonym sets and | separate the individuals synonyms).
                    </td>
                </tr>	                            

            </table>

            <br />
            <div align="center">
                <input type="submit" name="Submit" class="button-primary"
                       value="Update Options" />&nbsp;&nbsp;<input type="button"
                       name="cancel" value="Cancel" class="button"
                       onclick="javascript:history.go(-1)" />
            </div>
        </form>
    </div>
</div>
