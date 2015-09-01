<?php
//function to define non-current years to exclude from previous / next links
$nua_years = array( get_the_terms($post->ID, "years") );
array_pop($nua_years[0]);
$nua_previous_years = array();
foreach($nua_years[0] as $a_year){
    array_push($nua_previous_years, $a_year->term_id);
}
$nua_exclude = implode(',',$nua_previous_years);
?>