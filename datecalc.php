<?php
/**
 * This is a hook utility function that allows for calculating upcoming dates 
 * by using an existing field as starting point. Supported offset are 
 *    - days
 *    - months
 *    - years
 *    - hours
 *    - minutes
 *    - seconds
 *
 * Offseting by hours, minutes or seconds will assume that a Date field has 
 * a time value 00:00:00, and therefore should only be used with a Datetime field as source
    @DATECALC={[date], 7, d}
    
    Thomas Obadia
    Pasteur Institute, Paris, France
**/
error_reporting(E_ALL);

$term = '@DATECALC';
 
hook_log("Starting $term for project $project_id", "DEBUG");
 
///////////////////////////////
// Enable hook_functions and hook_fields for this plugin (if not already done)
if (!isset($hook_functions)) {
   $file = HOOK_PATH_FRAMEWORK . 'resources/init_hook_functions.php';
   if (file_exists($file)) {
      include_once $file;
 
      // Verify it has been loaded
      if (!isset($hook_functions)) { hook_log("ERROR: Unable to load required init_hook_functions."); return; }
   } else {
      hook_log ("ERROR: In Hooks - unable to include required file $file while in " . __FILE__);
   }
}
 
// See if the term defined in this hook is used on this page
if (!isset($hook_functions[$term])) {
   hook_log ("Skipping $term on $instrument of $project_id - not used.", "DEBUG");
   return;
}
 
//////////////////////////////
// List al the fields to inject and log some things for debug purposes
$startup_vars = $hook_functions[$term];
error_log("Startup Vars in " . __FILE__);
error_log(print_r($startup_vars, true));
?>

<script type='text/javascript'>
    $(document).ready(function() {
    	var datecalcFields = <?php print json_encode($startup_vars) ?>;

    	// Loop through each field contained in datecalc_fields
    	$(datecalcFields).each(function(field, params) {
    		var fieldTr = $('tr[sq_id=' + field + ']');
    		var fieldInput = $('input', fieldTr); // probably useless
    		var csvOptions = params.params.split(",");
    		// csvOptions should now be array with 
    		// [0] => time of origin
    		// [1] => offset
    		// [2] => unit

    		// Work with originField to get its format etc, and content
    		var originField = csvOptions[0].replace(/[\[|\]]/g, '');
    		// NEXT TWO LINES ARE DEFINITELY NOT POSSIBLE.
    		// Must find another way to get field validation
    		//var originFieldDictionary = <?php REDCap::getDataDictionary('array', false, originField)?>;
    		//var originFieldValidationType = originFieldDictionary[7];

    		// THIS MIGHT BE A BETTER WAY...
    		var originTr = $('tr[sq_id=' + originField + ']');
    		var originInput = $('input', originTr);
    		var originFieldValidationType = $(originInput).attr('fv')

        // Initialize output date
        var targetDate = new Date(fieldInput);

        // Some debug logs
        console.log('fieldTr = ' + fieldTr + ' / fieldInput = ' + fieldInput + 
          ' / csvOptions = {' + csvOptions[0] + ',' + csvOptions[1] + ',' + csvOptions[2] + '}' + 
          ' / originField = ' originField + ' / originTr = ' + originTr + ' / originInput = ' + 
          originInput + ' / originFieldValidationType = ' + originFieldValidationType + ' / targetDate = ' + 
          targetDate);

    		if (originFieldValidationType == 'date_ymd') {
    			// Add days
          if (unit == 'd') {
            targetDate = targetDate.setDate(targetDate.getDate() + csvOptions[1]);
          };
          else if (unit == 'm') {
            targetDate = targetDate.setMonth(targetDate.getMonth() + csvOptions[1]);
          };
          else if (unit == 'y') {
            targetDate = targetDate.setYear(targetDate.getYear() + csvOptions[1]);
          }
    		};

        // Will be amended at a later stage when I get time, but should be straightforward if I can get the first one running
    		//else if (originFieldValidationType == 'date_dmy') {
    			//what to do...
    		};
    		//else if (originFieldValidationType == 'date_mdy') {
    			//what to dooo....
    		};

        // New logs
        console.log(targetDate);

        // Need to actually write down the content to the field. How... ?

    	});
    });
</script>
