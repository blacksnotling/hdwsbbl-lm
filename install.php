<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2007-2011. All Rights Reserved.
 *      
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *   
 */

define('T_NO_STARTUP', true);
require('header.php'); // Includes and constants.

HTMLOUT::frame_begin(false,false);
title('OBBLM setup');

if (isset($_POST['setup']) || (isset($argv[1]) ? $argv[1] == 'setup' : false)) {
    $setupOK = setup_database();
    if ($setupOK) {
        echo "<br><strong><font color='green'>Finished</font></strong>";
        $helpURL = DOC_URL;
        echo <<<EOL
<br><br>
<strong>What now?</strong><br>
&mdash; Please remove the <em>install.php</i> file from your OBBLM folder and <a href='index.php'>continue to the main page</a>.<br> 
&mdash; Once at the main page login using the coach account 'root' with password 'root'<br>
&mdash; From there you may enter the <em>administration</i> section and add new users (coaches) including changing the root password.<br>
&mdash; For further help visit the <a href='$helpURL'>OBBLM wiki</a>.<br>
<br>
<strong>Need help? Encountering errors?</strong><br>
&mdash; If you are encountering errors please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
        echo "<br><br>";
        HTMLOUT::dnt();
    }
    else {
        echo "<br><strong><font color='red'>Failed</font></strong>";
        echo <<<EOL
<br><br>
<strong>Need help? Encountering errors?</strong><br>
&mdash; If you are encountering errors please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
        
    }
}
else
{
   // Make sure OBBLM is not already installed
   $conn = mysql_up();
   if(mysql_query("DESCRIBE coaches"))
   {
     echo <<<EOL
     It seems OBBLM is already installed.<br>You can safely delete the file <em>install.php</i> and go to the main page.
     <br><br>If you need help please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
   }
   else
   {
    ?>
    Please, <strong>before starting the installation process</strong>, make sure that the MySQL user and database you have specified in <em>settings.php</i> exist and are valid.<br><br>When ready, press the button below :<br><br>

    <form method="POST">
        <input type="submit" name="setup" value="Setup DB for OBBLM">
    </form>
    <?php
   }
}
HTMLOUT::frame_end();
