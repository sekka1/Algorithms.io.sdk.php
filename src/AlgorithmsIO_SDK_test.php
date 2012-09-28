<?php
include("AlgorithmsIO.class.php");

ob_implicit_flush(true); // Flush after every echo/print

// TEST - User/Pass Login
$authentication = new \AlgorithmsIO\Authentication(array(
		"authToken"		=>null,
		"username"		=>"mark@mark.org",
		"password"		=>"test",
));
assertTrue("Authentication with User/Pass", $authentication->authenticated(), ($authentication->authenticated() ? "true" : "false"));

// TEST - authToken
$authentication = new \AlgorithmsIO\Authentication(array(
		"authToken"		=>"541b393f52b097d3e589ea63ccdfd49e", // Default MRR Test Account
));
assertTrue("Authentication with Token", $authentication->authenticated(), $authentication->authToken());

// TEST - Authentication->credits()
$credits = $authentication->credits();
assertTrue("Authentication Credits", ($credits>1), ($credits.">1"));

// TEST - Upload a Data Source
$datasource = new \AlgorithmsIO\DataSource();
$datasource->authobj($authentication);
$datasource->filepath("./");// $datasource->filepath("/mnt/md0/sample_datasets/");
$datasource->filename("Movie_Lens_100k_data.csv");
$datasource->upload();
assertTrue("Datasource Upload", ($datasource->id()>0), $datasource->id().">0");

// TEST - List of Algorithms
$algo = new \AlgorithmsIO\Algorithm();
$algo->authobj($authentication);
$algo_list = $algo->listAll();
$algo->id(13); // Hardcoded for test, needed for mapper
assertTrue("Algorithms List", (count($algo_list) == 9), count($algo_list)."=9");

// TEST - Mapper
$mapper = new \AlgorithmsIO\Mapper();
$mapper->algorithm($algo);
$mapper->datasource($datasource);
$mapper->add(array(
		"field_user_id"		=>"user",
		"field_item_id"		=>"item",
		"field_preference"	=>"pref",
	));
assertTrue("Mapper", (count($mapper->mappings()) == 3), count($mapper->mappings())."=3");

// TODO: TEST - Run a Job
$job = new \AlgorithmsIO\Job();
$job->authobj($authentication);
$job->mapper($mapper);
$job->run();
$result_data = $job->data();
assertTrue("Job", ($result_data->mapping->TotalRows == 100000), ($result_data->mapping->TotalRows."=100000"));

// $job->job_list();

// TEST - Delete a Source
$deleted = $datasource->delete();
assertTrue("Datasource Delete", $deleted);



function assertTrue($test, $boolean, $optional_result = null) {
	$result = "[Test $test] ";
	if(isset($optional_result)) {
		$result .= "($optional_result)";
	}
	$result .= " - ";
	if ($boolean == true) {
		echo $result."PASSED<BR>\n";
		return true;
	} else {
		echo $result."FAILED<BR>\n";
		return false;
	}
}

?>
