<?
/*
 * Copyright 2010-2012 Algorithms.io, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://api.algorithms.io/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

// Include the SDK
require_once 'sdk.class.php';

// Preparation
$algorithms = new Algorithms();

// Get File list
$file_list = $algorithms->getFileList();

// Decode the json
$file_list_json = json_decode( $file_list, true );

// Loop through the files and delete all the Movie_Lens_100k_data file
foreach( $file_list_json['data'] as $aFile ){

    if( $aFile['friendly_name'] == 'Movie_Lens_100k_data' ){

        $datasource_id_seq = $aFile['id_seq'];
        $outcome = $algorithms->deleteFile( $datasource_id_seq );

        echo 'Deleted: ' . $datasource_id_seq. "\n";
    }
}
