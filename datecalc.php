<?php
/**
 * This is a hook utility function that allows for calculating upcoming dates 
 * by using an existing field as starting point. Supported offset are 
 *    - days (d)
 *    - months (m)
 *    - years (y)
 *    - hours (h)
 *    - minutes (min)
 *    - seconds (s)
 *
 * Usage:
    @DATECALC=[date],7,d
    
    Thomas Obadia
    Institut Pasteur, Paris, France
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

// Also list all the variables needed for PHP devtools
$record_id = REDCap::getRecordIdField();

?>

<script type='text/javascript'>

    // Function to convert dates to proper format
    function formatDate(date, format) {
      
      // Initialize Date object and extract YYYY, (M)M, (D)D
      var d = new Date(date), 
      year = d.getFullYear(), 
      month = '' + (d.getMonth() + 1),             // range 0-11, add 1
      day = '' + d.getDate(),
      hours = '' + d.getHours(),
      minutes = '' + d.getMinutes(),
      seconds = '' + d.getSeconds();

      // Pad with leading zeros if needed
      if (day.length < 2) day = '0' + day;
      if (month.length < 2) month = '0' + month;
      if (hours.length < 2) hours = '0' + hours;
      if (minutes.length < 2) minutes = '0' + minutes;
      if (seconds.length < 2) seconds = '0' + seconds;

      // Return date formated as requested
      if (format == 'ymd') {return [year, month, day].join('-');}
      else if (format == 'dmy') {return [day, month, year].join('-');}
      else if (format == 'mdy') {return [month, day, year].join('-');}
      else if (format == 'datetime_ymd') {return [year, month, day].join('-') + ' ' + [hours, minutes].join(':');}
      else if (format == 'datetime_dmy') {return [day, month, year].join('-') + ' ' + [hours, minutes].join(':');}
      else if (format == 'datetime_mdy') {return [month, day, year].join('-') + ' ' + [hours, minutes].join(':');}
      else if (format == 'datetime_seconds_ymd') {return [year, month, day].join('-') + ' ' + [hours, minutes, seconds].join(':');}
      else if (format == 'datetime_seconds_dmy') {return [day, month, year].join('-') + ' ' + [hours, minutes, seconds].join(':');}
      else if (format == 'datetime_seconds_mdy') {return [month, day, year].join('-') + ' ' + [hours, minutes, seconds].join(':');};
    };



    // Actually run the hook over all document
    $(document).ready(function() {
    	var datecalcFields = <?php print json_encode($startup_vars) ?>;
      //console.log('datecalcFields = ', datecalcFields);
    	
      // Loop through each field contained in datecalc_fields
    	$.each(datecalcFields, function(targetFieldName, params) {
    		//console.log('targetFieldName = ', targetFieldName);
        //console.log('params = ', params);

        // Get parent tr from table
        var targetFieldTr = $('tr[sq_id="' + targetFieldName + '"]');
    		//console.log('targetFieldTr = ', targetFieldTr);
        
        // Extract current input, probbaly useless but let's keep track of code
        var targetFieldInput = $('input', targetFieldTr);
    		//console.log('targetFieldInput = ', targetFieldInput);
        
        var csvOptions = params.params.split(",");
    		//console.log('csvOptions[0] = ', csvOptions[0]);
        //console.log('csvOptions[1] = ', csvOptions[1]);
        //console.log('csvOptions[2] = ', csvOptions[2]);
        
        // csvOptions should now be array with strings
    		// [0] => time of origin
    		// [1] => offset (must be converted to Number)
    		// [2] => unit
        

        // Try a simple thing: copy the content of origin to target
        //var targetFieldInput = $('input:text[name="test_origin_date"]')
        //console.log('targetFieldInput = ' + targetFieldInput);

    		// Work with originField to get its format etc, and content
    		var originFieldName = csvOptions[0].replace(/[\[|\]]/g, '');
        //console.log('originField = ' + originFieldName);

    		// Get content of origin date field
    		var originFieldTr = $('tr[sq_id="' + originFieldName + '"]');
    		var originFieldInput = $('input', originFieldTr);
    		var originFieldValidationType = $(originFieldInput).attr('fv');
        //console.log('originFieldTr = ', originFieldTr);
        //console.log('originFieldInput = ', originFieldInput);
        //console.log('originFieldValidationType = ', originFieldValidationType);

        // JavaScript is dumb: it doesn't allow to specify date(time) formats
        // Let' do this by hand since it's not practical to use libraries like moments.js, unfortunately...
        // First, split into date/time (if any) and add a dummy time element
        var originFieldInputParts = originFieldInput.val().split(' ');
        if (originFieldInputParts.length == '1') originFieldInputParts.push('')
        // Identify separator in the date part
        var dateSeparator = originFieldInputParts[0].indexOf('/')
        if (dateSeparator == '-1') dateSeparator = '-';
        //console.log('originFieldInputParts = ', originFieldInputParts);
        //console.log('dateSeparator = ', dateSeparator);
        var dateParts = originFieldInputParts[0].split(dateSeparator);
        // Reshape date(time) input into short format actually handled by JS
        if (originFieldValidationType == 'date_ymd' | originFieldValidationType == 'datetime_ymd' | originFieldValidationType == 'datetime_seconds_ymd') {
          var modifiedFieldInput = dateParts[1] + '-' + dateParts[2] + '-' + dateParts[0] + ' ' + originFieldInputParts[1]
        }
        else if (originFieldValidationType == 'date_dmy' | originFieldValidationType == 'datetime_dmy' | originFieldValidationType == 'datetime_seconds_dmy') {
          var modifiedFieldInput = dateParts[1] + '-' + dateParts[0] + '-' + dateParts[2] + ' ' + originFieldInputParts[1]
        }
        else if (originFieldValidationType == 'date_mdy' | originFieldValidationType == 'datetime_mdy' | originFieldValidationType == 'datetime_seconds_mdy') {
          var modifiedFieldInput = dateParts[0] + '-' + dateParts[1] + '-' + dateParts[2] + ' ' + originFieldInputParts[1]
        }


        // Initialize output date at origin date and derive origin Epoch Time (from there)
        //console.log('originFieldInput.val() = ', originFieldInput.val());
        var targetDate = new Date(modifiedFieldInput);
        var originTime = targetDate.getTime();
        //console.log('targetDate (origin value) = ', targetDate);
        //console.log('originTime (origin value) = ', originTime);

    		// When validating as a Date and no Datetime, ignore adding hours, minutes or secods
        if (originFieldValidationType == 'date_ymd' | originFieldValidationType == 'date_dmy' | originFieldValidationType == 'date_mdy') {
    			// Add days
          if (csvOptions[2] == 'd') {
            //console.log('Origin day = ', targetDate.getDate());
            //console.log('Target day = ', targetDate.getDate() + Number(csvOptions[1]));
            targetDate.setDate(targetDate.getDate() + Number(csvOptions[1]));
          }
          // Add months
          else if (csvOptions[2] == 'm') {
            //console.log('Origin month = ', targetDate.getMonth());
            //console.log('Target month = ', targetDate.getMonth() + Number(csvOptions[1]));
            targetDate.setMonth(targetDate.getMonth() + Number(csvOptions[1]));
          }
          // Add years
          else if (csvOptions[2] == 'y') {
            //console.log('Origin year = ', targetDate.getYear());
            //console.log('Target year = ', targetDate.getYear() + Number(csvOptions[1]));
            targetDate.setYear(targetDate.getYear() + Number(csvOptions[1]));
          };
    		}

        // When validating as a DateTime, must account for possible change in days, months etc depending on amount added
        else if (originFieldValidationType == 'datetime_ymd' | originFieldValidationType == 'datetime_dmy' | originFieldValidationType == 'datetime_mdy' | originFieldValidationType == 'datetime_seconds_ymd' | originFieldValidationType == 'datetime_seconds_dmy' | originFieldValidationType == 'datetime_seconds_mdy') {
          // Add seconds
          if (csvOptions[2] == 's') {
            var targetTime = originTime + Number(csvOptions[1])*1000;
            targetDate = new Date(targetTime);
            //console.log('Origin time = ', originTime);
            //console.log('Offset = ', Number(csvOptions[1])*1000);
            //console.log('Target time = ', targetTime);
            //console.log('Target date = ', targetDate);
          }
          // Add minutes
          else if (csvOptions[2] == 'min') {
            var targetTime = originTime + Number(csvOptions[1])*60*1000;
            targetDate = new Date(targetTime);
            //console.log('Origin time = ', originTime);
            //console.log('Offset = ', Number(csvOptions[1])*60*1000);
            //console.log('Target time = ', targetTime);
            //console.log('Target date = ', targetDate);
          }
          // Add hours
          else if (csvOptions[2] == 'h') {
            var targetTime = originTime + Number(csvOptions[1])*60*60*1000;
            targetDate = new Date(targetTime);
            //console.log('Origin time = ', originTime);
            //console.log('Offset = ', Number(csvOptions[1])*60*60*1000);
            //console.log('Target time = ', targetTime);
            //console.log('Target date = ', targetDate);
          }
          // Add days
          else if (csvOptions[2] == 'd') {
            targetDate.setDate(targetDate.getDate() + Number(csvOptions[1]));
            //console.log('Origin day = ', targetDate.getDate());
            //console.log('Target day = ', targetDate.getDate() + Number(csvOptions[1]));
          }
          else if (csvOptions[2] == 'm') {
            targetDate.setMonth(targetDate.getMonth() + Number(csvOptions[1]));
            //console.log('Origin month = ', targetDate.getMonth());
            //console.log('Target month = ', targetDate.getMonth() + Number(csvOptions[1]));
          }
          else if (csvOptions[2] == 'y') {
            targetDate.setYear(targetDate.getYear() + Number(csvOptions[1]));
            //console.log('Origin year = ', targetDate.getYear());
            //console.log('Target year = ', targetDate.getYear() + Number(csvOptions[1]));
          };
        };
        //console.log('targetDate + offset = ', targetDate);

        // Write the target date in the field only if it is empty (i.e. has not been calculated previously)
          if ($(targetFieldInput).val() === '') {
            if (originFieldValidationType == 'date_ymd') {$(targetFieldInput).val(formatDate(targetDate, 'ymd'))}
            else if (originFieldValidationType == 'date_dmy') {$(targetFieldInput).val(formatDate(targetDate, 'dmy'))}
            else if (originFieldValidationType == 'date_mdy') {$(targetFieldInput).val(formatDate(targetDate, 'mdy'))}
            else if (originFieldValidationType == 'datetime_ymd') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_ymd'))}
            else if (originFieldValidationType == 'datetime_dmy') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_dmy'))}
            else if (originFieldValidationType == 'datetime_mdy') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_mdy'))}
            else if (originFieldValidationType == 'datetime_seconds_ymd') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_seconds_ymd'))}
            else if (originFieldValidationType == 'datetime_seconds_dmy') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_seconds_dmy'))}
            else if (originFieldValidationType == 'datetime_seconds_mdy') {$(targetFieldInput).val(formatDate(targetDate, 'datetime_seconds_mdy'))};
          };
        });
});
</script>
