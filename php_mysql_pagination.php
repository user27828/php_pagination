<?PHP
/**
 * Pagination Sample using the MySQL table mysql.help_keyword
 *
 *	This file is meant to teach the basics using a standalone file, so the bells & whistles are
 *	missing and the it is procedural.
 *
 * (C) 2018 Marc Stephenson / GitHub: user27828
 */
error_reporting(E_ALL);
// For sample purposes only!  It is a bad idea to store credentials in every other file.
$link = mysqli_connect(<HOST>, <USERNAME>, <PASSWORD>, 'mysql');

// Variable assignments
//-------------------------------------------------------------------
// Selection options for $items_per_page.  The user will see links to select these options for "Items per page".
$ITEMS_STEPS        = array(10, 20, 50, 100, 200, 400, 1000);
 
/* For page number links - pages at the beginning, and pages at the end.
 *  Your implementation might automatically generate the pages based on
 *  any factors you decide.
 */
$pages_per_direction = 4;

/* Items per page - check for the query string var "items_per_page", otherwise, assign a default.
 * The default value will be the first value in $ITEMS_STEPS
 */
$items_per_page     = (array_key_exists('items_per_page', $_GET) && !empty($_GET['items_per_page']) )
	? intval($_GET['items_per_page']) : $ITEMS_STEPS[0];

/* An end-user might try to use their own value for &items_per_page=...
 * if it's not a value listed in $ITEMS_STEPS, then reject it, set 
 * $items_per_page to the default.  This prevents someone a scenario 
 * such as: setting items to a billion on a query that may have a billion 
 * results (could easily turn into a DoS vulnerability).
 */
if( !in_array($items_per_page, $ITEMS_STEPS) ) {
	$items_per_page     = $ITEMS_STEPS[0];
	// Redirect to the default page with default items per page.
	header(sprintf('Location: %s?items_per_page=%d', $_SERVER['PHP_SELF'],  $items_per_page));
	exit;
}

// The query variable appended to links if "items_per_page" is set in query string
$items_per_page_qry = (array_key_exists('items_per_page', $_GET) && !empty($_GET['items_per_page']) )
	? sprintf('&amp;items_per_page=%d', $items_per_page) : NULL;

/* Current page number - default is 0 (first page).  For the end-user, 
 * links and text will display the page as $current_page+1.  This is 
 * because no normal person reads the first page as page 0.
 * Strict user variable checking isn't so important here, if the result 
 * LIMIT is overshot, then it's only an empty result.
 */
$current_page       = (array_key_exists('page', $_GET) && !empty($_GET['page']) )
	? intval($_GET['page']) : 0;

// First param in SQL LIMIT clause, based on current page * items per page
$item_sql_start     = $current_page * $items_per_page;

// SQL and related variable assignment
//-------------------------------------------------------------------
// Define the LIMIT clause.  If there's no page params, just limit from the beginning by items per page
$query_limit        = !empty($item_sql_start) 
    ? sprintf('LIMIT %d, %d', $item_sql_start, $items_per_page) : sprintf('LIMIT %d', $items_per_page);
$query              = sprintf('SELECT SQL_CALC_FOUND_ROWS * FROM help_keyword %s', $query_limit);
$result             = mysqli_query($link, $query);
$TOTAL              = mysqli_fetch_array(mysqli_query($link, 'SELECT FOUND_ROWS()'));
$total              = intval($TOTAL['FOUND_ROWS()']);	// Total results, populated via SQL_CALC_FOUND_ROWS/FOUND_ROWS()
$total_modulo       = $total % $items_per_page;			// Modulo is the division remainder of $a/$b
// Total pages/last page - If modulo has a remainder, add an additional page
$total_pages        = ($total_modulo==0) ? $total/$items_per_page : ceil($total/$items_per_page);


// Some basic HTML/CSS
//-------------------------------------------------------------------
print '<!DOCTYPE html><html lang="en-US" class="no-js"><head><title>Pagination Test</title><style>
a { margin: 0 5px; }
.debug { width: 700px; border-bottom: 1px dotted #00F; }
.debug strong { display: inline-block; width: 250px; }
</style></head></body>';
 
// Debugging data display
//-------------------------------------------------------------------
print '<pre><div style="border: 1px dashed #000;"><strong>Debug Data</strong><br />';
printf('<div class="debug"><strong>Items Per Page:</strong> %s</div>',		$items_per_page);
printf('<div class="debug"><strong>Current Page: </strong> %s</div>',		$current_page+1);	// Front end/human readable page#
printf('<div class="debug"><strong>SQL Start item:</strong> %s</div>',		$item_sql_start);
printf('<div class="debug"><strong>SQL Query:</strong> %s</div>',			$query);
printf('<div class="debug"><strong>Total SQL results:</strong> %s</div>',	$total);
printf('<div class="debug"><strong>Total/Last Page:</strong> %s <em>(floor|ceil(total/per page))</em></div>', $total_pages);
print '</div><p>&nbsp;</p>';
 
// DB Result display
//-------------------------------------------------------------------
if( $result ) {
	// Display the SQL results that utilize pagination limits
	print "Count\t| Result Count\t| help_keyword_id\t| name\n"; // Columns - the last 2 are from DB
	print "--------------------------------------------------------------------------\n";
	$i  = 0;
	while( $ROW = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
		$i++;
		printf("%02d\t| %03s\t\t| %s\t\t\t| %s\n", $i, $item_sql_start+$i, $ROW['help_keyword_id'], $ROW['name'] );
	}
	print "--------------------------------------------------------------------------\n";
	print '<p><strong>Notes: </strong> "Count" is the <strong>current</strong> result set, it will only go up to $items_per_page.
	"help_keyword_id" is unrelated to the counts, any similarity to counts is coincidental.</p>';
} else {
	print 'Error - Could not fetch results using query: ' . $query;
	exit;
}
mysqli_free_result($result);
mysqli_close($link);
 
print '</pre>';
 
// Navigation links - Items Per Page & First/Previous/<steps>/Next/Last
//-------------------------------------------------------------------
$LINKS              = array();
foreach($ITEMS_STEPS as $step) {
	// Items per page navigation
	$LINKS[]            = ($step!=$items_per_page) 
		? sprintf('[<a href="%s?items_per_page=%d">%d</a>]', $_SERVER['PHP_SELF'], $step, $step)
		: $step;
}
printf('<p>Items Per Page: %s</p>', implode('&nbsp;|&nbsp;', $LINKS));
 
// First page
printf('<a href="%s?page=0%s">[&lt;&lt;First Page]</a>', $_SERVER['PHP_SELF'], $items_per_page_qry);
if( !empty($current_page) ) {
	printf('<a href="%s?page=%d%s">[&lt;Previous Page]</a>', $_SERVER['PHP_SELF'], $current_page-1, $items_per_page_qry);
}
 
/* Display links to specific page numbers IF we have a sufficient number of pages.
 *  There are many ways to implement this.  The code below is very simple and does not
 *  automatically adjust to the result set.
 */
if( $total_pages > ($pages_per_direction*2) ) {
	// Pages at the beginning
	for($i=1; $i<=$pages_per_direction; $i++) {  // Page "1" is the second page of results
		printf('<a href="%s?page=%d%s">(%d)</a>', $_SERVER['PHP_SELF'], $i-1, $items_per_page_qry, $i);
	}
	 
	print '&nbsp...&nbsp;'; // Spacing between beginning and end links
	 
	// Pages at the end
	for($i=($total_pages-$pages_per_direction); $i<$total_pages+1; $i++) {
		printf('<a href="%s?page=%d%s">(%d)</a>', $_SERVER['PHP_SELF'], $i-1, $items_per_page_qry, $i);
	}
}
 
// Hide the Next/Last page if we only have 1 total page
if( $total_pages > 1 ) {
	// Don't show "Next Page" if there is no next page
	if( $current_page<$total_pages ) {
		printf('<a href="%s?page=%d%s">[Next Page&gt;]</a>', $_SERVER['PHP_SELF'], $current_page+1, $items_per_page_qry);
	}
	 
	// Last Page
	printf('<a href="%s?page=%d%s">[Last Page&gt;&gt;]</a>', $_SERVER['PHP_SELF'], $total_pages-1, $items_per_page_qry);
}
 
print '<p>&nbsp;</p></body></html>';
?>