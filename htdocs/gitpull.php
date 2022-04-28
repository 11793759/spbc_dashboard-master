<?php

$output = shell_exec("cd /home/vcap/app/htdocs/; git pull --no-edit;");
print "<pre>".$output."</pre>";

?>
