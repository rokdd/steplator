<?php
$template="This text will ouput at once char by char and [[this only when the following var {{script_name}} and method {{static_class::method_name}} exists]] else {{author_name}}";
steppped template::output($template, Array('author_name'=>'Robert','script_name'=>'steplator'));
?>