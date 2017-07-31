<?php
/**
 * This is a hook utility function that allows for calculating upcoming dates 
 * by using an existing field as starting point. Supported offset are 
 *    - days
 *    - months
 *    - years
 *    - hours (not supported currently)
 *    - minutes (not supported currently)
 *    - seconds (not supported currently)
 *
 * Offseting by hours, minutes or seconds will assume that a Date field has 
 * a time value 00:00:00, and therefore should only be used with a Datetime field as source
    @DATECALC=[date], 7, d
    
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

    // Function to convert dates to proper format
    function formatDate(date, format) {
      
      // Initialize Date object and extract YYYY, (M)M, (D)D
      var d = new Date(date), 
      year = d.getFullYear(), 
      month = ''+ (d.getMonth() + 1), 
      day = '' + d.getDate();

      // Pad with leading zeros if needed
      if (day.length < 2) day = '0' + day;
      if (month.length < 2) month = '0' + month;

      // Return date formated as requested
      if (format == 'ymd') {return [year, month, day].join('-');}
      else if (format == 'dmy') {return [day, month, year].join('-');}
      else if (format == 'mdy') {return [month, day, year].join('-');};
    };



    // Actually run the hook over all document
    $(document).ready(function() {
    	var datecalcFields = <?php print json_encode($startup_vars) ?>;
      console.log('datecalcFields = ', datecalcFields);
    	
      // Loop through each field contained in datecalc_fields
    	$.each(datecalcFields, function(targetFieldName, params) {
    		console.log('targetFieldName = ', targetFieldName);
        console.log('params = ', params);

        // Get parent tr from table
        var targetFieldTr = $('tr[sq_id="' + targetFieldName + '"]');
    		console.log('targetFieldTr = ', targetFieldTr);
        
        // Extract current input, probbaly useless but let's keep track of code
        var targetFieldInput = $('input', targetFieldTr);
    		console.log('targetFieldInput = ', targetFieldInput);
        
        var csvOptions = params.params.split(",");
    		console.log('csvOptions[0] = ', csvOptions[0]);
        console.log('csvOptions[1] = ', csvOptions[1]);
        console.log('csvOptions[2] = ', csvOptions[2]);
        // csvOptions should now be array with strings
    		// [0] => time of origin
    		// [1] => offset (must be converted to Number)
    		// [2] => unit
        

        // Try a simple thing: copy the content of origin to target
        //var targetFieldInput = $('input:text[name="test_origin_date"]')
        //console.log('targetFieldInput = ' + targetFieldInput);




    		// Work with originField to get its format etc, and content
    		var originFieldName = csvOptions[0].replace(/[\[|\]]/g, '');
        console.log('originField = ' + originFieldName);

    		// Get content of origin date field
    		var originFieldTr = $('tr[sq_id="' + originFieldName + '"]');
    		console.log('originFieldTr = ', originFieldTr);
        var originFieldInput = $('input', originFieldTr);
    		console.log('originFieldInput = ', originFieldInput);
        var originFieldValidationType = $(originFieldInput).attr('fv')
        console.log('originFieldValidationType = ', originFieldValidationType);
        
        // Initialize output date at origin date
        console.log('originFieldInput.val() = ', originFieldInput.val());
        var targetDate = new Date(originFieldInput.val());
        console.log('targetDate = ', targetDate);

    		// This will only handle dates and not datetimes, for now...
        if (originFieldValidationType == 'date_ymd' | originFieldValidationType == 'date_dmy' | originFieldValidationType == 'date_mdy') {
    			// Add days
          if (csvOptions[2] == 'd') {
            console.log('Origin day = ', targetDate.getDate());
            console.log('Target day = ', targetDate.getDate() + Number(csvOptions[1]));
            targetDate.setDate(targetDate.getDate() + Number(csvOptions[1]));
          }
          else if (csvOptions[2] == 'm') {
            console.log('Origin month = ', targetDate.getMonth());
            console.log('Target month = ', targetDate.getMonth() + Number(csvOptions[1]));
            targetDate.setMonth(targetDate.getMonth() + Number(csvOptions[1]));
          }
          else if (csvOptions[2] == 'y') {
            console.log('Origin year = ', targetDate.getYear());
            console.log('Target year = ', targetDate.getYear() + Number(csvOptions[1]));
            targetDate.setYear(targetDate.getYear() + Number(csvOptions[1]));
          };
    		};
        console.log('targetDate + offset = ', targetDate);

        // Write the target date in the field only if it is empty (i.e. has not been calculated previously)
          if ($(targetFieldInput).val() === '') {
            if (originFieldValidationType == 'date_ymd') {$(targetFieldInput).val(formatDate(targetDate, 'ymd'))}
            else if (originFieldValidationType == 'date_dmy') {$(targetFieldInput).val(formatDate(targetDate, 'dmy'))}
            else if (originFieldValidationType == 'date_mdy') {$(targetFieldInput).val(formatDate(targetDate, 'mdy'))};
          };
        });
});
</script>
