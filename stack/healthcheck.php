<?php 
// This file is part of Stack - http://stack.bham.ac.uk//
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once(dirname(__FILE__) . '/../locallib.php');
require_once('stringutil.class.php');
require_once('options.class.php');
require_once('cas/castext.class.php');
require_once('cas/casstring.class.php');
require_once('cas/cassession.class.php');

// TODO: translate this page....

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);
$PAGE->set_context($context);
$PAGE->set_url('/question/type/stack/stack/test.php');

function stack_generate_maximalocal() {
    global $CFG;

    //print_r($CFG);
    $settings = get_config('qtype_stack');

    $maxloc  = "/* ***********************************************************************/\n";
    $maxloc .= "/* This file is automatically generated at installation time.            */\n";
    $maxloc .= "/* The purpose is to transfer configuration settings to Maxima.          */\n";
    $maxloc .= "/* Hence, you should not edit this file.  Edit your configuration.       */\n";
    $maxloc .= "/* This file is regularly overwritten, so your changes will be lost.     */\n";
    $maxloc .= "/* ***********************************************************************/\n\n";

    $maxloc .= "/* File generated on ".date("F j, Y, g:i a")." */\n\n";

    $dirroot = $CFG->dirroot;
    $root = new STACK_StringUtil($dirroot.'\question\type\stack\stack\maxima');
    $docroot = $root->convertSlashPaths();

    $maxloc .= "/* Add the location to Maxima's search path */\n";
    $maxloc .= "file_search_maxima:append( [sconcat(\"{$docroot}/###".'.{mac,mc}")] , file_search_maxima)$'."\n";
    $maxloc .= "file_search_lisp:append( [sconcat(\"{$docroot}/###".'.{lisp}")] , file_search_lisp)$'."\n";

    //add in the log directory to the search path
    $dataroot = $CFG->dataroot;
    $root = new STACK_StringUtil($dataroot.'/stack');
    $logs = $root->convertSlashPaths();
    $maxloc .= "file_search_maxima:append( [sconcat(\"{$logs}/###".'.{mac,mc}")] , file_search_maxima)$'."\n";
    $maxloc .= "file_search_lisp:append( [sconcat(\"{$logs}/###".'.{lisp}")] , file_search_lisp)$'."\n";

    $maxloc .="\n\nSTACK_SETUP(ex):=block(\n";
    $vnum = substr($settings->maximaversion,2);
    $maxloc .="    MAXIMA_VERSION_NUM:$vnum,\n";

    // Create an array of Maxima settings.
    // These are used by the GNUplot "set terminal" command.  Currently no user interface...
    $maximalocal['MAXIMA_VERSION'] = $settings->maximaversion;
    $maximalocal['PLOT_TERMINAL'] = 'png'; // 'gif' is not recommended.  See GNUPlot documentation.
    $maximalocal['PLOT_TERM_OPT'] = 'large transparent size 450,300';


    $platform = $settings->platform;
    // Windows
    if ('win' == $platform) {
        $maximalocal['DEL_CMD']       = 'del';
        if ($settings->plotcommand == 'gnuplot' or $settings->plotcommand == '') {
            // This does its best to find your version of Gnuplot...
            if ($vnum>25) {
                $maximalocal['GNUPLOT_CMD'] = '"c:/Program Files/Maxima-'.$settings->maximaversion.'-gcl/gnuplot/wgnuplot.exe"';
            } else if ($vnum>23) {
                $maximalocal['GNUPLOT_CMD'] = '"c:/Program Files/Maxima-'.$settings->maximaversion.'/gnuplot/wgnuplot.exe"';
            } else {
                $maximalocal['GNUPLOT_CMD'] = '"c:/Program Files/Maxima-'.$settings->maximaversion.'/bin/wgnuplot.exe"';
            }
        } else {
            $maximalocal['GNUPLOT_CMD'] = $settings->plotcommand;
        }
    } else {
        $maximalocal['DEL_CMD']       = "rm";
        $maximalocal['GNUPLOT_CMD' ]  = $settings->plotcommand;
    }
    $maximalocal['TMP_IMAGE_DIR'] = $path = $CFG->dataroot . '/stack/';
    //TODO where do we put plots properly?!
    $maximalocal['IMAGE_DIR']     = $CFG->dataroot . '/stack/plots/';
    $maximalocal['URL_BASE']      = moodle_url::make_file_url('/question/type/stack/plot.php', '');

    // Loop over this array to format them correctly...
    foreach ($maximalocal as $var => $val) {
        if ('win' == $platform and 'URL_BASE' != $var ) {
            $val = addslashes(str_replace( '/', '\\', $val));
        }
        $maxloc .="    $var:\"$val\",\n";
    }

    $maxloc .="    true)$\n\n";
    $maxloc .= "/* Load the main libraries */\n";
    $maxloc .= "load(\"stackmaxima.mac\")\$\n";

    echo '<pre>'.$maxloc.'</pre>';

    make_upload_directory('stack');

    $fh = fopen($dataroot.'/stack/maximalocal.mac','w');
    if (false === $fh) {
        throw new Exception('Failed to write Maxima configuration file.');
    } else {
        fwrite($fh,$maxloc);
        fclose($fh);
    }
    return true;
}

echo $OUTPUT->header();
?>
<h1>STACK healthcheck</h1>

<h2>Check LaTeX is being converted correctly</h2>
STACK generates LaTeX on the fly, and enables teachers to write LaTeX in questions.
It assumes that LaTeX will be converted by a moodle filter.  Below are samples of displayed and inline expressions in LaTeX which should be
appear correctly in your browser.  Problems here indicate incorrect moodle filter settings, not faults with STACK itself.
<h3>Single and double dollar math environments</h3>
<?php echo format_text('$$ \sum_{n=1}^\infty \frac{1}{n^2} = \frac{\pi^2}{6}.$$'); ?>
<br />
<?php echo format_text('$ \sum_{n=1}^\infty \frac{1}{n^2} = \frac{\pi^2}{6}.$'); ?>

<h3>Matching displayed and inline LaTeX tags.</h3>
<?php echo format_text('\[ \sum_{n=1}^\infty \frac{1}{n^2} = \frac{\pi^2}{6}.\]'); ?>
<br />
<?php echo format_text('\( \sum_{n=1}^\infty \frac{1}{n^2} = \frac{\pi^2}{6}.\)'); ?>
<br />
(These are currently not generated by STACK on the fly, but may be written by some question authors.)

<h2>Trying to automatically write the Maxima configuration file</h2>
<?php 
if (stack_generate_maximalocal()) {
    echo 'Success!';
}


echo '<h2>Trying to connect to the CAS</h2>';

echo '<p>We are trying to evaluate the following cas text:</p>';
$string = 'The derivative of @ x^4/(1+x^4) @ is $$ \frac{d}{dx} \frac{x^4}{1+x^4} = @ diff(x^4/(1+x^4),x) @. $$';
echo '<pre>'.$string.'</pre>';

$ct           = new stack_cas_text($string);
$displaytext  = $ct->get_display_castext();
$errs         = $ct->get_errors();

echo '<p>', format_text($displaytext), '</p>';
echo $errs;

echo '<p>We are trying to evaluate the following cas text:</p>';
$string = 'Two example plots below.  @plot([x^4/(1+x^4),diff(x^4/(1+x^4),x)],[x,-3,3])@  @plot([sin(x),x,x^2,x^3],[x,-3,3],[y,-3,3])@';
echo '<pre>'.$string.'</pre>';

$ct           = new stack_cas_text($string);
$displaytext  = $ct->get_display_castext();
$errs         = $ct->get_errors();

echo '<p>', format_text($displaytext), '</p>';
echo $errs;
echo '<p>There should be two different plots.  If two identical plots are seen then this is an error in naming the plot files.  If no errors are returned, but a plot is not displayed then one of the following may help.  (i) check read permissions on the two temporary directories. (ii) change the options used by GNUPlot to create the plot.  Currently there is no web interface to these options.</p>';

echo $OUTPUT->footer();